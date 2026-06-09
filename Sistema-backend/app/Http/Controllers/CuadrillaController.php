<?php

namespace App\Http\Controllers;

use App\Models\Cuadrilla;
use App\Models\AuditoriaAcceso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule; // 🔥 Requerido para exclusión de bajas en validaciones
use Barryvdh\DomPDF\Facade\Pdf; // 🔥 NUEVO: Importación de la librería PDF estándar de Laravel

class CuadrillaController extends Controller
{
    /**
     * 1. LISTAR CUADRILLAS
     * Muestra todas las cuadrillas operativas con su jefe y el conteo de obreros.
     */
    public function index()
    {
        // Eloquent automáticamente filtra y excluye las que tengan 'deleted_at' != null
        $cuadrillas = Cuadrilla::with('jefe')
            ->withCount('obreros')
            ->get();
        return response()->json($cuadrillas);
    }

    /**
     * 2. REGISTRAR CUADRILLA
     */
    public function store(Request $request)
    {
        $request->validate([
            // 🔥 CONTROL DE INTEGRIDAD: El nombre es único solo entre cuadrillas activas
            'nombre'  => [
                'required',
                'string',
                Rule::unique('cuadrillas', 'nombre')->whereNull('deleted_at')
            ],
            'jefe_id' => 'required|exists:users,id', 
        ]);

        $cuadrilla = Cuadrilla::create([
            'nombre'  => $request->nombre,
            'jefe_id' => $request->jefe_id,
        ]);

        // AUDITORÍA
        AuditoriaAcceso::create([
            'user_id'          => Auth::id(),
            'accion'           => "Creó la cuadrilla: {$cuadrilla->nombre} con Jefe ID: {$request->jefe_id}",
            'ip_origen'        => $request->ip(),
            'dispositivo_info' => $request->userAgent()
        ]);

        return response()->json([
            'res' => true,
            'message' => 'Cuadrilla creada exitosamente',
            'data' => $cuadrilla->load('jefe')
        ], 201);
    }

    /**
     * 3. VER DETALLE DE UNA CUADRILLA
     */
    public function show($id)
    {
        $cuadrilla = Cuadrilla::with(['jefe', 'obreros'])->findOrFail($id);
        return response()->json($cuadrilla);
    }

    /**
     * 4. ACTUALIZAR CUADRILLA
     */
    public function update(Request $request, $id)
    {
        $cuadrilla = Cuadrilla::findOrFail($id);

        $request->validate([
            // 🔥 CONTROL DE INTEGRIDAD: Ignora el ID actual pero valida duplicidad contra activas
            'nombre'  => [
                'required',
                'string',
                Rule::unique('cuadrillas', 'nombre')->ignore($id)->whereNull('deleted_at')
            ],
            'jefe_id' => 'required|exists:users,id',
        ]);

        $nombreAnterior = $cuadrilla->nombre;
        $jefeAnterior = $cuadrilla->jefe_id;

        $cuadrilla->update([
            'nombre'  => $request->nombre,
            'jefe_id' => $request->jefe_id,
        ]);

        // AUDITORÍA
        AuditoriaAcceso::create([
            'user_id'          => Auth::id(),
            'accion'           => "Modificó cuadrilla ID {$id}: Nombre de '{$nombreAnterior}' a '{$request->nombre}', Jefe ID de '{$jefeAnterior}' a '{$request->jefe_id}'",
            'ip_origen'        => $request->ip(),
            'dispositivo_info' => $request->userAgent()
        ]);

        return response()->json([
            'res' => true,
            'message' => 'Cuadrilla actualizada exitosamente',
            'data' => $cuadrilla->load('jefe')
        ]);
    }

    /**
     * 5. ELIMINAR CUADRILLA (Baja Lógica)
     * 🔥 SOLUCIÓN: Se inyectó 'Request $request' en los parámetros para corregir el error Undefined Variable
     */
    public function destroy(Request $request, $id)
    {
        $cuadrilla = Cuadrilla::findOrFail($id);

        // Regla de Negocio S.D.B: Validar estados activos en la relación baches
        $tieneTrabajoPendiente = $cuadrilla->baches()
            ->whereIn('estado', ['Asignado', 'En proceso'])
            ->exists();

        if ($tieneTrabajoPendiente) {
            return response()->json([
                'res' => false,
                'message' => 'Operación rechazada: La cuadrilla tiene órdenes de trabajo activas o pendientes en el mapa.'
            ], 400);
        }

        $nombreEliminado = $cuadrilla->nombre;
        
        // Al tener SoftDeletes en el modelo, esto ejecuta un UPDATE seteando la fecha en 'deleted_at'
        $cuadrilla->delete();

        // AUDITORÍA
        AuditoriaAcceso::create([
            'user_id'          => Auth::id(),
            'accion'           => "Dio de baja lógicamente la cuadrilla: {$nombreEliminado} (ID: {$id})",
            'ip_origen'        => $request->ip(),
            'dispositivo_info' => $request->userAgent()
        ]);

        return response()->json([
            'res' => true,
            'message' => 'La cuadrilla ha sido dada de baja lógicamente del sistema correctamente.'
        ]);
    }

    /**
     * 6. LISTAR CUADRILLAS ELIMINADAS (Para la Papelera Centralizada)
     */
    public function getTrashed()
    {
        $trashed = Cuadrilla::onlyTrashed()->with('jefe')->get();
        return response()->json($trashed);
    }

    /**
     * 7. RESTAURAR UNA CUADRILLA ELIMINADA
     */
    public function restore(Request $request, $id)
    {
        $cuadrilla = Cuadrilla::onlyTrashed()->findOrFail($id);
        
        $cuadrilla->restore(); // Limpia el campo 'deleted_at' volviéndolo NULL

        // AUDITORÍA DE RESTAURACIÓN
        AuditoriaAcceso::create([
            'user_id'          => Auth::id(),
            'accion'           => "Restauró la cuadrilla operativa: {$cuadrilla->nombre} (ID: {$id})",
            'ip_origen'        => $request->ip(),
            'dispositivo_info' => $request->userAgent()
        ]);

        return response()->json([
            'res' => true,
            'message' => "La cuadrilla '{$cuadrilla->nombre}' ha sido reactivada con éxito en S.D.B."
        ]);
    }

    /**
     * 8. 🔥 NUEVO: EXPORTAR PERSONAL DE UNA CUADRILLA ESPECÍFICA A PDF
     */
    public function exportarPersonalPdf(Request $request, $id)
    {
        // Buscamos la cuadrilla incluyendo explícitamente sus relaciones estructuradas
        $cuadrilla = Cuadrilla::with(['jefe', 'obreros'])->findOrFail($id);

        // REGISTRO EN EL MOTOR DE AUDITORÍA
        AuditoriaAcceso::create([
            'user_id'          => Auth::id(),
            'accion'           => "Exportó reporte PDF del personal asignado a la cuadrilla: {$cuadrilla->nombre} (ID: {$id})",
            'ip_origen'        => $request->ip(),
            'dispositivo_info' => $request->userAgent()
        ]);

        // Compilar la vista Blade en un archivo PDF binario en memoria
        $pdf = Pdf::loadView('cuadrilla_personal', compact('cuadrilla'));

        // Retornar la descarga directa con codificación limpia para el navegador
        return $pdf->download("SDB_Cuadrilla_N{$id}_Personal.pdf");
    }
}