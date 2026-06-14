<?php

namespace App\Http\Controllers;

use App\Models\Obrero;
use App\Models\Cuadrilla;
use App\Models\AuditoriaAcceso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule; // 🔥 NUEVO: Para exclusión de bajas en validaciones

class ObreroController extends Controller
{
    /**
     * 1. LISTAR OBREROS (Solo activos - Filtro inteligente por Rol)
     */
    public function index()
    {
        $user = Auth::user();
        
        // Iniciamos la consulta base cargando la relación de su cuadrilla
        $query = Obrero::with('cuadrilla');

        // 💡 CANDADO DE OPERACIONES: Si es Jefe de Cuadrilla, restringimos la lista a su propio equipo
        if ($user && $user->role && $user->role->nombre === 'Jefe de Cuadrilla') {
            
            // Buscamos la cuadrilla que lidera este usuario mediante la columna real 'jefe_id'
            $cuadrilla = Cuadrilla::where('jefe_id', $user->id)->first();

            if ($cuadrilla) {
                // Filtramos únicamente los obreros que pertenecen a su ID de cuadrilla
                $query->where('cuadrilla_id', $cuadrilla->id);
            } else {
                // Si el usuario es jefe pero no tiene cuadrilla asociada, devolvemos un array vacío seguro
                return response()->json([]);
            }
        }

        // Eloquent automáticamente filtra y excluye a los que tienen 'deleted_at' != null
        $obreros = $query->get();
        return response()->json($obreros);
    }

    /**
     * 2. REGISTRAR OBRERO
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombres'          => 'required|string|max:255',
            'apellido_paterno' => 'required|string|max:255',
            'apellido_materno' => 'nullable|string|max:255',
            // 🔥 CONTROL DE INTEGRIDAD: El CI es único solo entre obreros activos
            'ci'               => [
                'required',
                'string',
                Rule::unique('obreros', 'ci')->whereNull('deleted_at')
            ],
            'genero'           => 'required|in:Masculino,Femenino,Otro',
            'direccion'        => 'nullable|string|max:500',
            'especialidad'     => 'required|string',
            'cuadrilla_id'     => 'nullable|exists:cuadrillas,id'
        ]);

        $obrero = Obrero::create([
            'nombres'          => $request->nombres,
            'apellido_paterno' => $request->apellido_paterno,
            'apellido_materno' => $request->apellido_materno,
            'ci'               => $request->ci,
            'genero'           => $request->genero,
            'direccion'        => $request->direccion,
            'especialidad'     => $request->especialidad,
            'cuadrilla_id'     => $request->cuadrilla_id,
        ]);

        $nombreCompleto = trim("{$obrero->nombres} {$obrero->apellido_paterno} {$obrero->apellido_materno}");

        AuditoriaAcceso::create([
            'user_id'          => Auth::id(),
            'accion'           => "Registro de nuevo obrero: {$nombreCompleto} (CI: {$obrero->ci})",
            'ip_origen'        => $request->ip(),
            'dispositivo_info' => $request->userAgent()
        ]);

        return response()->json([
            'res' => true,
            'message' => 'El obrero ha sido registrado correctamente.',
            'data' => $obrero
        ], 201);
    }

    /**
     * 3. SUGERIR CUADRILLA
     */
    public function sugerirCuadrilla()
    {
        $cuadrillaSugerida = Cuadrilla::withCount('obreros')
            ->orderBy('obreros_count', 'asc')
            ->first();

        if (!$cuadrillaSugerida) {
            return response()->json([
                'res' => false,
                'mensaje' => 'No existen cuadrillas registradas en el sistema.'
            ], 404);
        }

        return response()->json([
            'sugerencia' => $cuadrillaSugerida,
            'mensaje' => "Se recomienda asignar a: {$cuadrillaSugerida->nombre} (actualmente con {$cuadrillaSugerida->obreros_count} miembros)."
        ]);
    }

    /**
     * 4. VER DETALLE DE UN OBRERO ESPECÍFICO
     */
    public function show($id)
    {
        $obrero = Obrero::with('cuadrilla')->findOrFail($id);
        return response()->json($obrero);
    }

    /**
     * 5. ACTUALIZAR DATOS DE UN OBRERO EXISTENTE
     */
    public function update(Request $request, $id)
    {
        $obrero = Obrero::findOrFail($id);

        $request->validate([
            'nombres'          => 'required|string|max:255',
            'apellido_paterno' => 'required|string|max:255',
            'apellido_materno' => 'nullable|string|max:255',
            // 🔥 CONTROL DE INTEGRIDAD: Ignora el ID actual pero valida duplicidad contra activos
            'ci'               => [
                'required',
                'string',
                Rule::unique('obreros', 'ci')->ignore($id)->whereNull('deleted_at')
            ],
            'genero'           => 'required|in:Masculino,Femenino,Otro',
            'direccion'        => 'nullable|string|max:500',
            'especialidad'     => 'required|string',
            'cuadrilla_id'     => 'nullable|exists:cuadrillas,id'
        ]);

        $obrero->update([
            'nombres'          => $request->nombres,
            'apellido_paterno' => $request->apellido_paterno,
            'apellido_materno' => $request->apellido_materno,
            'ci'               => $request->ci,
            'genero'           => $request->genero,
            'direccion'        => $request->direccion,
            'especialidad'     => $request->especialidad,
            'cuadrilla_id'     => $request->cuadrilla_id,
        ]);

        $nombreCompleto = trim("{$obrero->nombres} {$obrero->apellido_paterno} {$obrero->apellido_materno}");

        // AUDITORÍA DE ACTUALIZACIÓN
        AuditoriaAcceso::create([
            'user_id'          => Auth::id(),
            'accion'           => "Actualizó los datos del obrero: {$nombreCompleto} (ID: {$id})",
            'ip_origen'        => $request->ip(),
            'dispositivo_info' => $request->userAgent()
        ]);

        return response()->json([
            'res' => true,
            'message' => 'Datos del obrero actualizados correctamente.',
            'data' => $obrero->load('cuadrilla')
        ]);
    }

    /**
     * 6. ELIMINAR OBRERO (Baja Lógica)
     */
    public function destroy(Request $request, $id)
    {
        $obrero = Obrero::findOrFail($id);
        $nombreCompleto = trim("{$obrero->nombres} {$obrero->apellido_paterno} {$obrero->apellido_materno}");

        // Guardamos los cambios lógicos (coloca fecha en deleted_at)
        $obrero->delete();

        AuditoriaAcceso::create([
            'user_id'          => Auth::id(),
            'accion'           => "Dio de baja lógicamente al obrero: {$nombreCompleto} (ID: {$id})",
            'ip_origen'        => $request->ip(),
            'dispositivo_info' => $request->userAgent()
        ]);

        return response()->json([
            'res' => true,
            'message' => 'El obrero ha sido dado de baja del sistema correctamente.'
        ]);
    }

    /**
     * 7. LISTAR OBREROS ELIMINADOS (Para la Papelera Centralizada)
     */
    public function getTrashed()
    {
        $trashed = Obrero::onlyTrashed()->with('cuadrilla')->get();
        return response()->json($trashed);
    }

    /**
     * 8. RESTAURAR OBRERO
     */
    public function restore(Request $request, $id)
    {
        $obrero = Obrero::onlyTrashed()->findOrFail($id);
        $obrero->restore(); // Limpia el campo deleted_at de PostgreSQL

        $nombreCompleto = trim("{$obrero->nombres} {$obrero->apellido_paterno} {$obrero->apellido_materno}");

        AuditoriaAcceso::create([
            'user_id'          => Auth::id(),
            'accion'           => "Restauró la operatividad del obrero: {$nombreCompleto} (ID: {$id})",
            'ip_origen'        => $request->ip(),
            'dispositivo_info' => $request->userAgent()
        ]);

        return response()->json([
            'res' => true,
            'message' => "El obrero '{$nombreCompleto}' ha sido reactivado con éxito en S.D.B."
        ]);
    }
}