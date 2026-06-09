<?php

namespace App\Http\Controllers;

use App\Models\Vehiculo;
use App\Models\Sensor;
use App\Models\AuditoriaAcceso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class VehiculoController extends Controller
{
    /**
     * Listar flota de vehículos de la alcaldía con su respectivo hardware encendido
     */
    public function index()
    {
        $vehiculos = Vehiculo::with('sensor')->get();
        return response()->json($vehiculos);
    }

    /**
     * Registrar nueva unidad de patrullaje vial
     */
    public function store(Request $request)
    {
        $request->validate([
            'placa'     => 'required|string|max:15|unique:vehiculos,placa',
            'marca'     => 'required|string|max:50',
            'modelo'    => 'required|string|max:50',
            'tipo'      => 'required|in:Camioneta,Sedán,Compacto,Bus,Otro',
            'sensor_id' => 'nullable|integer|unique:vehiculos,sensor_id|exists:sensores,id'
        ]);

        $vehiculo = Vehiculo::create($request->all());

        // 🔥 Inteligencia de Sistema: Si se le instaló un sensor de entrada, lo marcamos ocupado
        if ($vehiculo->sensor_id) {
            Sensor::where('id', $vehiculo->sensor_id)->update(['estado' => 'Instalado']);
        }

        @AuditoriaAcceso::create([
            'user_id'          => Auth::id(),
            'accion'           => "Registró vehículo municipal Placa: {$vehiculo->placa} con Sensor ID: " . ($vehiculo->sensor_id ?? 'Ninguno'),
            'ip_origen'        => $request->ip(),
            'dispositivo_info' => $request->userAgent()
        ]);

        return response()->json([
            'res' => true,
            'message' => 'Vehículo registrado exitosamente en la flota de control viales.',
            'data' => $vehiculo->load('sensor')
        ], 201);
    }

    /**
     * Ver expediente de un vehículo específico
     */
    public function show($id)
    {
        $vehiculo = Vehiculo::with('sensor')->findOrFail($id);
        return response()->json($vehiculo);
    }

    /**
     * Modificar unidad y reestructurar hardware acoplado
     */
    public function update(Request $request, $id)
    {
        $vehiculo = Vehiculo::findOrFail($id);

        $request->validate([
            'placa'     => ['required', 'string', 'max:15', Rule::unique('vehiculos', 'placa')->ignore($id)],
            'marca'     => 'required|string|max:50',
            'modelo'    => 'required|string|max:50',
            'tipo'      => 'required|in:Camioneta,Sedán,Compacto,Bus,Otro',
            'sensor_id' => ['nullable', 'integer', Rule::unique('vehiculos', 'sensor_id')->ignore($id), 'exists:sensores,id']
        ]);

        $sensorAnterior = $vehiculo->sensor_id;
        $vehiculo->update($request->all());

        // 🔥 Gestión de hardware dinámica
        if ($sensorAnterior !== $vehiculo->sensor_id) {
            // Liberamos el sensor que tenía antes
            if ($sensorAnterior) {
                Sensor::where('id', $sensorAnterior)->update(['estado' => 'Disponible']);
            }
            // Bloqueamos el nuevo sensor asignado
            if ($vehiculo->sensor_id) {
                Sensor::where('id', $vehiculo->sensor_id)->update(['estado' => 'Instalado']);
            }
        }

        AuditoriaAcceso::create([
            'user_id'          => Auth::id(),
            'accion'           => "Modificó especificaciones del vehículo ID {$id} (Placa: {$request->placa})",
            'ip_origen'        => $request->ip(),
            'dispositivo_info' => $request->userAgent()
        ]);

        return response()->json([
            'res' => true,
            'message' => 'Información de la unidad modificada correctamente.',
            'data' => $vehiculo->load('sensor')
        ]);
    }

    /**
     * Eliminar vehículo y liberar automáticamente su sensor físico
     */
    public function destroy(Request $request, $id)
    {
        $vehiculo = Vehiculo::findOrFail($id);
        $sensorAnterior = $vehiculo->sensor_id;
        $placaEliminada = $vehiculo->placa;

        $vehiculo->delete();

        // 🔥 Si el vehículo tenía un sensor puesto al destruirse, el sensor vuelve a estar libre
        if ($sensorAnterior) {
            Sensor::where('id', $sensorAnterior)->update(['estado' => 'Disponible']);
        }

        AuditoriaAcceso::create([
            'user_id'          => Auth::id(),
            'accion'           => "Dio de baja el vehículo municipal Placa: {$placaEliminada} (ID: {$id})",
            'ip_origen'        => $request->ip(),
            'dispositivo_info' => $request->userAgent()
        ]);

        return response()->json([
            'res' => true,
            'message' => 'Vehículo eliminado del sistema. El hardware asociado ha sido liberado.'
        ]);
    }

    /**
     * Obtener la lista de baches capturados por un vehículo específico
     */
    public function getBachesAsociados($id)
    {
        $vehiculo = Vehiculo::findOrFail($id);
        $baches = $vehiculo->baches()->orderBy('created_at', 'desc')->get();
        
        return response()->json([
            'vehiculo' => $vehiculo->load('sensor'),
            'baches'   => $baches
        ]);
    }

    /**
     * 🔥 Exportar reporte PDF de baches detectados por un vehículo individual
     */
    public function exportarBachesPdf($id)
    {
        $vehiculo = Vehiculo::with(['sensor', 'baches' => function($query) {
            $query->orderBy('created_at', 'desc');
        }])->findOrFail($id);

        $fechaEmision = \Carbon\Carbon::now()->format('d/m/Y H:i:s');
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.reporte_individual', compact('vehiculo', 'fechaEmision'));
        return $pdf->download("reporte_telemetria_placa_{$vehiculo->placa}.pdf");
    }

    /**
     * 🔥 Exportar TODOS los vehículos de la flota a PDF institucional general
     */
    public function exportarTodosPdf()
    {
        $vehiculos = Vehiculo::with('sensor')->orderBy('placa', 'asc')->get();
        $fechaEmision = \Carbon\Carbon::now()->format('d/m/Y H:i:s');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.reporte_general', compact('vehiculos', 'fechaEmision'));
        return $pdf->download("reporte_general_flota_vehicular.pdf");
    }

    /**
     * Exportar la lista completa de la flota a un libro de Excel (.csv)
     */
    public function exportarTodosExcel()
    {
        $vehiculos = Vehiculo::with('sensor')->orderBy('placa', 'asc')->get();
        $fileName = "inventario_general_flota_" . date('d_m_Y_His') . ".csv";

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use($vehiculos) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['NÚMERO DE PLACA', 'MARCA', 'MODELO', 'TIPO DE UNIDAD', 'CÓDIGO HARDWARE IOT', 'MODELO SENSOR TELEMETRÍA']);

            foreach ($vehiculos as $v) {
                fputcsv($file, [
                    $v->placa,
                    $v->marca,
                    $v->modelo,
                    $v->tipo,
                    $v->sensor ? $v->sensor->codigo : 'Ninguno',
                    $v->sensor ? $v->sensor->modelo : 'Sin dispositivo asignado'
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * 🔥 ACTUALIZADO Y CORREGIDO: Analítica predictiva con discriminación de baches ya reparados
     */
    public function getSaludFlota()
    {
        // Obtenemos las unidades filtrando los baches para contar ÚNICAMENTE los que siguen 'Activos'
        $vehiculos = Vehiculo::withCount([
            'baches as total_baches' => function ($query) {
               $query->whereIn('estado', ['Activo', 'Pendiente']); // 👈 Filtro crucial: ignora los reparados
            },
            'baches as baches_criticos' => function ($query) {
                $query->whereIn('estado', ['Activo', 'Pendiente'])  // 👈 Filtro crucial: ignora los reparados
                      ->where('severidad', 'Alta');
            }
        ])->get();

        // Computación del desgaste de materiales por fatiga mecánica repetitiva
        $reporte = $vehiculos->map(function ($v) {
            $criticos = (int) ($v->baches_criticos ?? 0);
            $total = (int) ($v->total_baches ?? 0);
            $levesOModerados = max($total - $criticos, 0);

            // Coeficiente matemático de desgaste de amortiguación (3.5% crítico, 1.0% regular)
            $penalizacion = ($criticos * 3.5) + ($levesOModerados * 1.0);
            $saludSuspension = max(100 - $penalizacion, 0);

            // Diagnósticos predictivos automotrices de acuerdo al índice de fatiga
            if ($saludSuspension <= 55) {
                $estado = 'Crítico';
                $recomendacion = '🚨 ALERTA: Fatiga extrema de material detectada. Requiere inmediato ingreso a taller para cambio de amortiguadores and alineación completa.';
            } elseif ($saludSuspension <= 80) {
                $estado = 'Preventivo';
                $recomendacion = '⏳ SUGERENCIA: Desgaste moderado acumulado. Se recomienda inspección visual de bujes y prueba de rebote de amortiguación en el próximo cambio de aceite.';
            } else {
                $estado = 'Óptimo';
                $recomendacion = '✅ SISTEMA SEGURO: El sistema de suspensión opera dentro de las tolerancias normales de elasticidad y absorción.';
            }

            return [
                'id'               => $v->id,
                'placa'            => $v->placa,
                'marca'            => $v->marca,
                'modelo'           => $v->modelo,
                'tipo'             => $v->tipo,
                'total_baches'     => $total,
                'baches_criticos'  => $criticos,
                'salud_suspension' => $saludSuspension,
                'estado_salud'     => $estado,
                'recomendacion'    => $recomendacion
            ];
        });

        return response()->json($reporte);
    }

    /**
     * 🔥 NUEVO: Obtener la hoja de vida completa (Historial Clínico) de un vehículo
     * Versión estructurada lineal para garantizar compatibilidad con linters de VS Code
     */
    public function getHistorialMantenimientos($id)
    {
        $vehiculo = Vehiculo::findOrFail($id);

        // Extraemos las órdenes históricas incluyendo las eliminadas de forma lógica (finalizadas)
        $mantenimientos = \App\Models\Mantenimiento::withTrashed()
            ->with('tecnico')
            ->where('vehiculo_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        $historial = [];

        foreach ($mantenimientos as $mant) {
            
            // 1. Resolver el nombre del técnico
            $nombreTecnico = 'Técnico no asignado';
            if ($mant->tecnico) {
                $nombreTecnico = $mant->tecnico->nombres . ' ' . $mant->tecnico->apellido_paterno;
            }

            // 2. Determinar el estado operativo de la orden
            $estado = 'Concluido';
            if (is_null($mant->deleted_at)) {
                $estado = 'En Curso';
            }

            // 3. Formatear la fecha
            $fechaFormateada = date('d/m/Y H:i', strtotime($mant->created_at));

            // 4. Estructurar la fila limpia para el arreglo final
            $historial[] = [
                'id'          => $mant->id,
                'tipo'        => $mant->tipo,
                'descripcion' => $mant->descripcion,
                'fecha'       => $fechaFormateada,
                'estado'      => $estado,
                'tecnico'     => $nombreTecnico
            ];
        }

        return response()->json([
            'vehiculo'  => $vehiculo,
            'historial' => $historial
        ]);
    }

    public function exportarHistorialPdf($id)
    {
        $vehiculo = Vehiculo::findOrFail($id);

        $mantenimientos = \App\Models\Mantenimiento::withTrashed()
            ->with('tecnico')
            ->where('vehiculo_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        $historial = [];

        foreach ($mantenimientos as $mant) {
            $nombreTecnico = 'Técnico no asignado';
            if ($mant->tecnico) {
                $nombreTecnico = $mant->tecnico->nombres . ' ' . $mant->tecnico->apellido_paterno;
            }

            $estado = is_null($mant->deleted_at) ? 'En Curso' : 'Concluido';
            $fechaFormateada = date('d/m/Y H:i', strtotime($mant->created_at));

            $historial[] = [
                'tipo'        => $mant->tipo,
                'descripcion' => $mant->descripcion,
                'fecha'       => $fechaFormateada,
                'estado'      => $estado,
                'tecnico'     => $nombreTecnico
            ];
        }

        $fechaEmision = date('d/m/Y H:i:s');

        // 🔥 CAMBIO AQUÍ: Ahora apunta al nuevo archivo exclusivo para este reporte
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.expediente_mantenimientos', compact('vehiculo', 'historial', 'fechaEmision'));
        
        return $pdf->download("expediente_clinico_placa_{$vehiculo->placa}.pdf");
    }
}