<?php

namespace App\Http\Controllers;

use App\Models\Mantenimiento;
use App\Models\PersonalTaller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MantenimientoController extends Controller
{
    /**
     * 1. LISTAR MANTENIMIENTOS (GET)
     */
    public function index()
    {
        $mantenimientos = Mantenimiento::with(['vehiculo', 'tecnico'])->get();
        return response()->json($mantenimientos, 200);
    }

    /**
     * 2. REGISTRAR UN MANTENIMIENTO (POST)
     */
    public function store(Request $request)
    {
        // 🧼 FILTRO LOGÍSTICO: Limpiamos el texto de Vue antes de validar para no romper el ENUM de la base de datos
        if ($request->has('tipo')) {
            $tipoRaw = $request->input('tipo');
            if (str_contains($tipoRaw, 'Electrónico') || str_contains($tipoRaw, 'Electronico')) {
                $request->merge(['tipo' => 'Electrónico']);
            } elseif (str_contains($tipoRaw, 'Mecánico') || str_contains($tipoRaw, 'Mecanico')) {
                $request->merge(['tipo' => 'Mecánico']);
            }
        }

        // Volvemos a usar la validación 'in' para garantizar la integridad
        $validador = Validator::make($request->all(), [
            'vehiculo_id'        => 'required|exists:vehiculos,id',
            'personal_taller_id' => 'required|exists:personal_taller,id',
            'descripcion'        => 'required|string',
            'tipo'               => 'required|in:Mecánico,Electrónico', 
        ]);

        if ($validador->fails()) {
            return response()->json([
                'res' => false,
                'message' => 'Error de validación',
                'errors' => $validador->errors()
            ], 422);
        }

        // Creamos el registro del mantenimiento con el tipo ya normalizado
        $mantenimiento = Mantenimiento::create($request->all());

        // Si se asigna un mantenimiento, pasamos el estado del técnico a 'Ocupado'
        $tecnico = PersonalTaller::find($request->personal_taller_id);
        if ($tecnico && $tecnico->estado === 'Disponible') {
            $tecnico->update(['estado' => 'Ocupado']);
        }

        return response()->json([
            'res' => true,
            'message' => 'Mantenimiento registrado con éxito en el sistema.',
            'data' => $mantenimiento
        ], 201);
    }

    /**
     * 3. ACTUALIZAR UN MANTENIMIENTO (PUT)
     */
    public function update(Request $request, $id)
    {
        $mantenimiento = Mantenimiento::find($id);

        if (!$mantenimiento) {
            return response()->json([
                'res' => false,
                'message' => 'Registro de mantenimiento no encontrado.'
            ], 404);
        }

        // 🧼 FILTRO LOGÍSTICO: Limpiamos también en la edición
        if ($request->has('tipo')) {
            $tipoRaw = $request->input('tipo');
            if (str_contains($tipoRaw, 'Electrónico') || str_contains($tipoRaw, 'Electronico')) {
                $request->merge(['tipo' => 'Electrónico']);
            } elseif (str_contains($tipoRaw, 'Mecánico') || str_contains($tipoRaw, 'Mecanico')) {
                $request->merge(['tipo' => 'Mecánico']);
            }
        }

        $validador = Validator::make($request->all(), [
            'vehiculo_id'        => 'required|exists:vehiculos,id',
            'personal_taller_id' => 'required|exists:personal_taller,id',
            'descripcion'        => 'required|string',
            'tipo'               => 'required|in:Mecánico,Electrónico',
        ]);

        if ($validador->fails()) {
            return response()->json([
                'res' => false,
                'errors' => $validador->errors()
            ], 422);
        }

        $mantenimiento->update($request->all());

        return response()->json([
            'res' => true,
            'message' => 'Historial de mantenimiento actualizado correctamente.'
        ], 200);
    }

    /**
     * 4. ELIMINAR / ENVIAR A LA PAPELERA (DELETE)
     */
    public function destroy($id)
    {
        $mantenimiento = Mantenimiento::find($id);

        if (!$mantenimiento) {
            return response()->json([
                'res' => false,
                'message' => 'El registro no existe o ya fue eliminado.'
            ], 404);
        }

        $mantenimiento->delete();

        return response()->json([
            'res' => true,
            'message' => 'El registro de mantenimiento fue trasladado a la papelera.'
        ], 200);
    }

    /**
     * 5. FINALIZAR MANTENIMIENTO (PUT)
     */
    public function finalizar($id)
    {
        $mantenimiento = Mantenimiento::find($id);

        if (!$mantenimiento) {
            return response()->json([
                'res' => false,
                'message' => 'El registro de mantenimiento no existe.'
            ], 404);
        }

        $tecnico = PersonalTaller::find($mantenimiento->personal_taller_id);
        if ($tecnico) {
            $tecnico->update(['estado' => 'Disponible']);
        }

        \DB::table('baches')
            ->where('vehiculo_id', $mantenimiento->vehiculo_id)
            ->whereIn('estado', ['Activo', 'Pendiente']) 
            ->update(['estado' => 'Reparado']);

        $mantenimiento->delete();

        return response()->json([
            'res' => true,
            'message' => '¡Mantenimiento concluido con éxito! Técnico liberado y telemetría de suspensión restaurada al 100%.'
        ], 200);
    }
}