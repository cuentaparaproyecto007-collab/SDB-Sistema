<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CuadrillaMaterial;
use App\Models\Material; // Para consultar y restar el stock global
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth; // 🔥 NUEVO: Importamos el Facade de Auth para validar las sesiones

class CuadrillaMaterialController extends Controller
{
    /**
     * FASE 1: Despacho de material por la mañana (Almacén -> Camión de la Cuadrilla)
     */
    public function despacharMaterial(Request $request)
    {
        // 1. Validar los datos que vienen desde el formulario de Vue
        $request->validate([
            'cuadrilla_id' => 'required|integer',
            'material_id'  => 'required|integer',
            'cantidad'     => 'required|numeric|min:0.01',
        ]);

        // Usamos una transacción para asegurar que ambas operaciones ocurran juntas
        return DB::transaction(function () use ($request) {
            
            // 2. Buscar el material en el almacén central y verificar stock
            $materialGlobal = Material::findOrFail($request->material_id);

            if ($materialGlobal->stock_actual < $request->cantidad) {
                return response()->json([
                    'res' => false,
                    'message' => "Stock insuficiente en almacén. Solo quedan {$materialGlobal->stock_actual} m³ disponibles."
                ], 422);
            }

            // 3. RESTAR del almacén central (Stock Global)
            $materialGlobal->decrement('stock_actual', $request->cantidad);

            // 4. CREAR el registro en tránsito para la cuadrilla (Hoja de ruta del día)
            $despacho = CuadrillaMaterial::create([
                'cuadrilla_id'        => $request->cuadrilla_id,
                'material_id'         => $request->material_id,
                'cantidad_despachada' => $request->cantidad,
                'amount_actual'       => $request->cantidad, // Al iniciar la mañana, el saldo actual es igual al despachado
                'cantidad_actual'     => $request->cantidad, 
                'fecha'               => now()->toDateString(), // Fecha del día de hoy
                'estado'              => 'Activo',
            ]);

            return response()->json([
                'res' => true,
                'message' => 'Despacho diario registrado con éxito. El material ya está asignado al camión de la cuadrilla.',
                'data' => $despacho
            ], 201);
        });
    }

    /**
     * FASE 2: Restar material en tránsito (Camión -> Bache Reparado)
     */
    public function restarMaterialTransito(Request $request)
    {
        // 1. Validar los datos de la reparación
        $request->validate([
            'cuadrilla_id' => 'required|integer',
            'material_id'  => 'required|integer',
            'volumen_m3'   => 'required|numeric|min:0.001', // El volumen calculado (Largo x Ancho x Profundidad)
        ]);

        return DB::transaction(function () use ($request) {
            
            // 2. Buscar la hoja de ruta ACTIVA de la cuadrilla para el día de hoy
            $materialTransito = CuadrillaMaterial::where('cuadrilla_id', $request->cuadrilla_id)
                ->where('material_id', $request->material_id)
                ->where('estado', 'Activo')
                ->whereDate('fecha', now()->toDateString())
                ->first();

            // 3. Si no hay un despacho activo para hoy, significa que salieron sin registrar material
            if (!$materialTransito) {
                return response()->json([
                    'res' => false,
                    'message' => 'Error: Esta cuadrilla no tiene asfalto asignado en tránsito para la jornada de hoy.'
                ], 422);
            }

            // 4. Verificar si el camión tiene suficiente asfalto para este bache
            if ($materialTransito->cantidad_actual < $request->volumen_m3) {
                return response()->json([
                    'res' => false,
                    'message' => "Alerta: El camión se ha quedado sin material suficiente. Intenta consumir {$request->volumen_m3} m³ pero solo le quedan {$materialTransito->cantidad_actual} m³."
                ], 422);
            }

            // 5. RESTAR del saldo actual del camión (No toca el almacén central)
            $materialTransito->decrement('cantidad_actual', $request->volumen_m3);

            return response()->json([
                'res' => true,
                'message' => "Material descontado del camión con éxito. Saldo restante en tránsito: {$materialTransito->cantidad_actual} m³.",
                'cantidad_actual_camion' => $materialTransito->cantidad_actual
            ], 200);
        });
    }

    /**
     * FASE 3: Cierre de Jornada por la noche (Camión -> Retorno al Almacén Central)
     */
    public function cerrarJornada(Request $request)
    {
        // 1. Validar la cuadrilla y el material a cerrar
        $request->validate([
            'cuadrilla_id' => 'required|integer',
            'material_id'  => 'required|integer',
        ]);

        return DB::transaction(function () use ($request) {
            
            // 2. Buscar la hoja de ruta ACTIVA de hoy para esa cuadrilla
            $materialTransito = CuadrillaMaterial::where('cuadrilla_id', $request->cuadrilla_id)
                ->where('material_id', $request->material_id)
                ->where('estado', 'Activo')
                ->whereDate('fecha', now()->toDateString())
                ->first();

            if (!$materialTransito) {
                return response()->json([
                    'res' => false,
                    'message' => 'No se encontró una jornada activa para esta cuadrilla el día de hoy.'
                ], 404);
            }

            // 3. Identificar el sobrante que quedó en el camión
            $sobrante = $materialTransito->cantidad_actual;

            // 4. Si el sobrante es mayor a 0, se lo REINTEGRA al almacén central
            if ($sobrante > 0) {
                $materialGlobal = Material::findOrFail($request->material_id);
                $materialGlobal->increment('stock_actual', $sobrante);
            }

            // 5. Cambiar el estado de la asignación a 'Cerrado' para concluir el día
            $materialTransito->update([
                'estado' => 'Cerrado'
            ]);

            return response()->json([
                'res' => true,
                'message' => "Jornada cerrada con éxito. Se reincorporaron {$sobrante} m³ de asfalto al almacén central.",
                'sobrante_devuelto' => $sobrante
            ], 200);
        });
    }

    /**
     * Monitoreo: Listar los despachos que están actualmente activos en la calle hoy
     * ✅ DETERMINADO CON ELOQUENT: Filtro exacto usando la columna 'jefe_id'
     */
    public function obtenerDespachosActivos()
    {
        $user = Auth::user();
        
        // Iniciamos la consulta base cargando las relaciones estructurales
        $query = CuadrillaMaterial::with(['cuadrilla', 'material'])
            ->where('estado', 'Activo')
            ->whereDate('fecha', now()->toDateString());

        // 💡 CONTROL DE ACCESO OPERATIVO: Si es Jefe de Cuadrilla, filtramos directo por su clave foránea oficial
        if ($user && $user->role && $user->role->nombre === 'Jefe de Cuadrilla') {
            
            // Consultamos usando el modelo Cuadrilla y la columna exacta que confirmamos 'jefe_id'
            $cuadrilla = \App\Models\Cuadrilla::where('jefe_id', $user->id)->first();

            if ($cuadrilla) {
                // El jefe únicamente puede auditar el saldo físico de su propio camión
                $query->where('cuadrilla_id', $cuadrilla->id);
            } else {
                // Si el usuario es jefe pero aún no se le ha creado su cuadrilla, retornamos vacío seguro
                return response()->json([], 200);
            }
        }

        // El Administrador y Técnico siguen obteniendo el monitoreo de toda la flota municipal
        $activos = $query->get();
        return response()->json($activos, 200);
    }
}
