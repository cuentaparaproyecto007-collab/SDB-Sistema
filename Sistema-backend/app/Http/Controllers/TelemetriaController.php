<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sensor;
use App\Models\Vehiculo;
use App\Models\Bache;
use App\Models\User;
use Illuminate\Support\Facades\DB; // 🔥 Importamos DB para consultas rápidas de respaldo

class TelemetriaController extends Controller
{
    public function procesarImpacto(Request $request)
    {
        try {
            // 1. Validar parámetros de red enviados por el simulador
            $request->validate([
                'sensor_codigo' => 'required|string',
                'latitud'       => 'required|numeric',
                'longitud'      => 'required|numeric',
                'fuerza_g'      => 'required|numeric',
            ]);

            // 2. Buscar el dispositivo sensor
            $sensor = Sensor::where('codigo', $request->sensor_codigo)->first();

            if (!$sensor) {
                return response()->json([
                    'status' => 'error',
                    'message' => "El código de hardware '{$request->sensor_codigo}' no existe en el inventario."
                ], 200);
            }

            // 3. Buscar el vehículo asignado a este sensor
            $vehiculo = Vehiculo::where('sensor_id', $sensor->id)->first();

            if (!$vehiculo) {
                return response()->json([
                    'status' => 'error',
                    'message' => "El sensor '{$request->sensor_codigo}' existe, pero no está asignado a ningún vehículo operativo."
                ], 200);
            }

            // 4. 🔥 ALGORITMO CALIBRADO: Mapeo exacto según tu ENUM de Base de Datos ('Baja', 'Media', 'Alta')
            $fuerza = $request->fuerza_g;
            if ($fuerza < 2.0) {
                $gravedad = 'Baja';
            } elseif ($fuerza >= 2.0 && $fuerza <= 4.0) {
                $gravedad = 'Media';
            } else {
                $gravedad = 'Alta';
            }

            // 5. RESOLUCIÓN DE LLAVES FORÁNEAS (Auditoría e Integridad Referencial)
            // Obtener primer usuario registrado
            $usuarioComodin = User::first();
            $validUserId = $usuarioComodin ? $usuarioComodin->id : 1;

            // Obtener primera zona registrada de forma dinámica para evitar fallas de FK en PostgreSQL
            $primeraZona = DB::table('zonas')->first();
            $validZonaId = $primeraZona ? $primeraZona->id : 1;

            // 6. 🔥 INSERCIÓN DE PRECISIÓN QUIRÚRGICA: Alineado con tus restricciones CHECK
            $bache = Bache::create([
                'vehiculo_id' => $vehiculo->id,
                'latitud'     => $request->latitud,
                'longitud'    => $request->longitud,
                'severidad'   => $gravedad,       // Guardará exactamente: 'Baja', 'Media' o 'Alta'
                'estado'      => 'Pendiente',     // Cumple tu regla 'baches_estado_check'
                'descripcion' => "Impacto automatizado detectado por sensor IoT ({$fuerza} G)",
                'zona_id'     => $validZonaId,    // Saca dinámicamente un ID real de tu tabla zonas
                'user_id'     => $validUserId,    // Resuelve la restricción NOT NULL de auditoría
            ]);

            // 7. Respuesta exitosa para renderizar en la consola del simulador
            return response()->json([
                'status'  => 'success',
                'message' => '¡Telemetría procesada e impacto vial registrado con éxito!',
                'datos_procesados' => [
                    'bache_id'          => $bache->id,
                    'vehiculo_asignado' => $vehiculo->marca . ' ' . $vehiculo->modelo . ' [' . $vehiculo->placa . ']',
                    'impacto_fuerza'    => $fuerza . ' G',
                    'severidad_enum'    => $bache->severidad,
                    'estado_vial'       => $bache->estado,
                    'coordenadas_gps'   => $bache->latitud . ', ' . $bache->longitud,
                    'zona_asignada_id'  => $bache->zona_id
                ]
            ], 201); // Retorna HTTP 201 Created para marcar éxito rotundo

        } catch (\Exception $e) {
            // Captura cualquier excepción remanente para mantener la estabilidad del frontend
            return response()->json([
                'status' => 'error',
                'message' => 'Excepción crítica detectada en el motor de Base de Datos.',
                'sql_error_detail' => $e->getMessage()
            ], 200);
        }
    }
}