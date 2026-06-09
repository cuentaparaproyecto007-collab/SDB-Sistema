<?php

namespace App\Http\Controllers;

use App\Models\Bache;
use App\Models\AuditoriaAcceso;
use App\Models\Material; 
use App\Models\CuadrillaMaterial; // 🔥 NUEVO: Importamos el modelo puente para controlar el camión en tránsito
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 
use Barryvdh\DomPDF\Facade\Pdf;

class BacheController extends Controller
{
    /**
     * 1. LISTAR BACHES (Para el Mapa de Vue)
     * Ahora incluye la relación con la cuadrilla asignada y el material usado.
     */
    public function index()
    {
        // Cargamos relaciones estructurales incluyendo el material por si ya fue reparado
        $baches = Bache::with(['zona', 'vehiculo', 'cuadrilla', 'material'])->get();
        
        return response()->json($baches);
    }

    /**
     * 2. REGISTRAR BACHE (Desde el sensor o App móvil)
     */
    public function store(Request $request)
    {
        $request->validate([
            'latitud'     => 'required|numeric',
            'longitud'    => 'required|numeric',
            'severidad'   => 'required|in:Baja,Media,Alta',
            'vehiculo_id' => 'required|exists:vehiculos,id',
            'zona_id'     => 'required|exists:zonas,id',
            'descripcion' => 'nullable|string'
        ]);

        $bache = Bache::create([
            'latitud'     => $request->latitud,
            'longitud'    => $request->longitud,
            'severidad'   => $request->severidad,
            'vehiculo_id' => $request->vehiculo_id,
            'zona_id'     => $request->zona_id,
            'descripcion' => $request->descripcion,
            'user_id'     => Auth::id(), 
            'estado'      => 'Pendiente'
        ]);

        AuditoriaAcceso::create([
            'user_id'          => Auth::id(),
            'accion'           => "Registro de nuevo bache ID: {$bache->id}",
            'ip_origen'        => $request->ip(),
            'dispositivo_info' => $request->userAgent()
        ]);

        return response()->json([
            'res' => true,
            'message' => 'Bache registered y auditado correctamente',
            'data' => $bache->load(['zona', 'vehiculo'])
        ], 201);
    }

    /**
     * 3. ASIGNAR CUADRILLA (Nuevo ajuste operativo)
     */
    public function asignarCuadrilla(Request $request, $id)
    {
        $request->validate([
            'cuadrilla_id' => 'required|exists:cuadrillas,id'
        ]);

        $bache = Bache::findOrFail($id);
        $bache->cuadrilla_id = $request->cuadrilla_id;
        $bache->estado = 'Asignado'; 
        $bache->save();

        AuditoriaAcceso::create([
            'user_id'          => Auth::id(),
            'accion'           => "Asignó cuadrilla ID: {$request->cuadrilla_id} al bache ID: {$id}",
            'ip_origen'        => $request->ip(),
            'dispositivo_info' => $request->userAgent()
        ]);

        return response()->json([
            'res' => true,
            'message' => 'Cuadrilla asignada correctamente',
            'data' => $bache->load('cuadrilla')
        ]);
    }

    public function getEstadisticas()
    {
        $porSeveridad = DB::table('baches')
            ->select('severidad', DB::raw('count(*) as total'))
            ->groupBy('severidad')
            ->get();

        $porZona = DB::table('baches')
            ->join('zonas', 'baches.zona_id', '=', 'zonas.id')
            ->select('zonas.nombre', DB::raw('count(*) as total'))
            ->groupBy('zonas.nombre')
            ->get();

        return response()->json([
            'severidad' => $porSeveridad,
            'zonas' => $porZona
        ]);
    }

    public function descargarReporte()
    {
        $baches = Bache::with(['zona', 'cuadrilla'])->get();
        
        $pdf = Pdf::loadView('reporte_baches', compact('baches'));

        AuditoriaAcceso::create([
            'user_id' => Auth::id(),
            'accion' => "Generó y descargó reporte general de baches en PDF",
            'ip_origen' => request()->ip(),
            'dispositivo_info' => request()->userAgent()
        ]);

        return $pdf->download('Reporte_Baches_SDB.pdf');
    }

    /**
     * 4. ACTUALIZAR ESTADO (🔥 REFACTORIZADO: LOGÍSTICA EN TRÁNSITO DE CUADRILLAS)
     */
    public function actualizarEstado(Request $request, $id)
    {
        $request->validate([
            'estado' => 'required|in:Pendiente,Asignado,En proceso,Reparado'
        ]);

        $bache = Bache::findOrFail($id);
        $estadoAnterior = $bache->estado;

        // 🔥 REGLA DE NEGOCIO ERP VIAL: Si pasa a 'Reparado', el asfalto se descuenta del camión asignado (Fase 2)
        if ($request->estado === 'Reparado') {
            $request->validate([
                'eje_x'          => 'required|numeric|min:0.01',
                'eje_y'          => 'required|numeric|min:0.01',
                'profundidad_cm' => 'required|numeric|min:0.01', 
                'material_id'    => 'required|exists:materiales,id'
            ]);

            // Fórmula Matemática: Eje X (m) * Eje Y (m) * (Profundidad (cm) / 100) -> Resultado en m³
            $volumen_m3 = $request->eje_x * $request->eje_y * ($request->profundidad_cm / 100);

            try {
                // Ejecutamos una Transacción Atómica para asegurar consistencia absoluta en PostgreSQL
                DB::transaction(function () use ($request, $bache, $volumen_m3) {
                    
                    // 1. Validar que el bache tenga asignada una cuadrilla operando
                    if (!$bache->cuadrilla_id) {
                        throw new \Exception("No se puede cerrar la obra. Este bache no tiene asignada ninguna Cuadrilla de mantenimiento viales.");
                    }

                    // 2. Bloquear la fila del material en tránsito de la cuadrilla para el día de hoy (Concurrencia segura)
                    $materialTransito = CuadrillaMaterial::lockForUpdate()
                        ->where('cuadrilla_id', $bache->cuadrilla_id)
                        ->where('material_id', $request->material_id)
                        ->where('estado', 'Activo')
                        ->whereDate('fecha', now()->toDateString())
                        ->first();

                    // 3. Si el camión salió a la calle sin registrar su hoja de despacho matutina
                    if (!$materialTransito) {
                        throw new \Exception("Operación abortada: La cuadrilla asignada no cuenta con un registro de asfalto activo en tránsito para el día de hoy.");
                    }

                    // 4. Control Logístico: Verificar que en el camión queden suficientes m³ para rellenar las dimensiones
                    if ($materialTransito->cantidad_actual < $volumen_m3) {
                        throw new \Exception("Stock insuficiente en el camión. La calculadora requiere " . round($volumen_m3, 3) . " m³ de asfalto, pero la cuadrilla solo dispone de {$materialTransito->cantidad_actual} m³ en tránsito.");
                    }

                    // ACCIÓN A: Restamos de forma exacta el volumen consumido al stock que transporta el camión
                    $materialTransito->cantidad_actual -= $volumen_m3;
                    $materialTransito->save();

                    // ACCIÓN B: Almacenamos la geometría métrica en el bache y actualizamos su estado
                    $bache->update([
                        'estado'         => 'Reparado',
                        'eje_x'          => $request->eje_x,
                        'eje_y'          => $request->eje_y,
                        'profundidad_cm' => $request->profundidad_cm,
                        'volumen_m3'     => $volumen_m3,
                        'material_id'    => $request->material_id
                    ]);
                });
            } catch (\Exception $e) {
                // Retornamos un HTTP 422 (Entidad No Procesable) con el error lógico para avisarle al Frontend
                return response()->json([
                    'res' => false,
                    'message' => $e->getMessage()
                ], 422);
            }
        } else {
            // Flujo básico tradicional para estados iniciales (Pendiente, Asignado, En proceso)
            $bache->estado = $request->estado;
            $bache->save();
        }

        // Mantenemos intacto el registro estricto de auditoría original
        AuditoriaAcceso::create([
            'user_id'          => Auth::id(),
            'accion'           => "Cambio de estado bache ID: {$id} de '{$estadoAnterior}' a '{$request->estado}'",
            'ip_origen'        => $request->ip(),
            'dispositivo_info' => $request->userAgent()
        ]);

        return response()->json([
            'res' => true,
            'message' => "El bache ahora está en estado: {$request->estado}",
            'data' => $bache->load(['zona', 'cuadrilla', 'material'])
        ]);
    }

    /**
     * 🔥 NUEVO: Obtener historial de obras finalizadas con filtros dinámicos de fechas
     */
    public function getHistorialReparaciones(\Illuminate\Http\Request $request)
    {
        // Consultamos solo baches reparados trayendo sus relaciones relacionales de auditoría
        $query = \App\Models\Bache::where('estado', 'Reparado')
            ->with(['cuadrilla', 'material', 'vehiculo']);

        // Filtro por rango de fechas (basado en la fecha de finalización de obra 'updated_at')
        if ($request->has('fecha_inicio') && $request->fecha_inicio) {
            $query->whereDate('updated_at', '>=', $request->fecha_inicio);
        }
        
        if ($request->has('fecha_fin') && $request->fecha_fin) {
            $query->whereDate('updated_at', '<=', $request->fecha_fin);
        }

        $historial = $query->orderBy('updated_at', 'desc')->get();
        return response()->json($historial);
    }

    /**
     * 📄 ENFOQUE ARQUITECTÓNICO: Exportar historial filtrado usando la vista estructurada en Blade
     */
    public function exportarHistorialPdf(\Illuminate\Http\Request $request)
    {
        try {
            $query = \App\Models\Bache::where('estado', 'Reparado')->with(['cuadrilla', 'material', 'zona']);

            if ($request->filled('fecha_inicio')) {
                $query->whereDate('updated_at', '>=', $request->fecha_inicio);
            }
            if ($request->filled('fecha_fin')) {
                $query->whereDate('updated_at', '<=', $request->fecha_fin);
            }

            $historial = $query->orderBy('updated_at', 'desc')->get();
            $fechaEmision = date('d/m/Y H:i:s');

            // 🚀 CONEXIÓN ESTÁNDAR LARAVEL: Cargamos la vista y le inyectamos los datos con compact()
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.reporte_historial', compact('historial', 'fechaEmision'));
            
            return $pdf->download("reporte_historial_SDB_".date('d_m_Y').".pdf");

        } catch (\Exception $e) {
            return response()->json([
                'res' => false,
                'message' => '🚨 Error en Servidor (PDF): ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🟢 REFACTORIZADO: Exportar historial a Excel usando una clase especializada de extracción
     */
    public function exportarHistorialExcel(\Illuminate\Http\Request $request)
    {
        try {
            $query = \App\Models\Bache::where('estado', 'Reparado')->with(['cuadrilla', 'material', 'zona']);

            if ($request->filled('fecha_inicio')) {
                $query->whereDate('updated_at', '>=', $request->fecha_inicio);
            }
            if ($request->filled('fecha_fin')) {
                $query->whereDate('updated_at', '<=', $request->fecha_fin);
            }

            $historial = $query->orderBy('updated_at', 'desc')->get();

            // 🚀 INSTANCIA DE ARQUITECTURA LIMPIA: Pasamos los datos a nuestra clase dedicada
            $export = new \App\Exports\HistorialExport($historial);
            return $export->descargar();

        } catch (\Exception $e) {
            return response()->json([
                'res' => false,
                'message' => '🚨 Error en Servidor (Excel): ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🔥 NUEVO: Obtener solo los materiales que una cuadrilla específica tiene en tránsito hoy
     * Esto filtra el selector del mapa para que no elijan insumos que no cargan en el camión.
     */
    public function obtenerMaterialesActivosCuadrilla($cuadrilla_id)
    {
        $materialesTransito = \App\Models\CuadrillaMaterial::with('material')
            ->where('cuadrilla_id', $cuadrilla_id)
            ->where('estado', 'Activo')
            ->whereDate('fecha', now()->toDateString())
            ->get()
            ->map(function ($item) {
                return [
                    'id'           => $item->material_id,
                    'nombre'       => $item->material->nombre,
                    'stock_actual' => $item->cantidad_actual // 👈 Enviamos el saldo real del camión en tránsito
                ];
            });

        return response()->json($materialesTransito, 200);
    }
}