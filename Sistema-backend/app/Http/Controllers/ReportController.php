<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * 📊 REPORTEADOR CENTRAL S.D.B.
     * Ejecuta JOINs explícitos basados en el esquema físico actual de la base de datos.
     */
    public function obtenerDatosReporte(Request $request)
    {
        try {
            $tipo = $request->query('tipo'); // 'criticidad', 'cuadrillas', 'almacen', 'telemetria', 'usuarios'
            $fechaInicio = $request->query('fecha_inicio');
            $fechaFin = $request->query('fecha_fin');

            // Rango de fechas dinámico controlado con Carbon
            $inicio = $fechaInicio ? Carbon::parse($fechaInicio)->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
            $fin = $fechaFin ? Carbon::parse($fechaFin)->endOfDay() : Carbon::now()->endOfDay();

            switch ($tipo) {
                
                // 1. Reporte de Criticidad y Análisis Geográfico (Múltiple JOIN: Baches + Zonas + Distritos)
                case 'criticidad':
                    $data = DB::table('baches')
                        ->join('zonas', 'baches.zona_id', '=', 'zonas.id')
                        ->join('distritos', 'zonas.distrito_id', '=', 'distritos.id')
                        ->select(
                            'baches.id as bache_id',
                            'baches.severidad',
                            'baches.estado',
                            'zonas.nombre as zona',
                            'distritos.nombre as distrito',
                            'baches.profundidad_cm',
                            'baches.updated_at as fecha'
                        )
                        ->whereBetween('baches.updated_at', [$inicio, $fin])
                        ->orderBy('baches.updated_at', 'desc')
                        ->get();
                    break;

                // 2. Reporte de Rendimiento Operativo de Cuadrillas (JOIN: Cuadrillas + Baches)
                case 'cuadrillas':
                    $data = DB::table('cuadrillas')
                        ->join('baches', 'cuadrillas.id', '=', 'baches.cuadrilla_id')
                        ->select(
                            'cuadrillas.nombre as cuadrilla',
                            'baches.id as bache_id',
                            'baches.estado',
                            'baches.volumen_m3',
                            'baches.updated_at as fecha_reparacion'
                        )
                        ->where('baches.estado', '=', 'Reparado')
                        ->whereBetween('baches.updated_at', [$inicio, $fin])
                        ->orderBy('baches.updated_at', 'desc')
                        ->get();
                    break;

                // 3. Reporte de Logística de Almacén y Consumos (JOIN: Baches + Materiales)
                case 'almacen':
                    $data = DB::table('baches')
                        ->join('materiales', 'baches.material_id', '=', 'materiales.id')
                        ->select(
                            'materiales.nombre as material',
                            'baches.id as bache_id',
                            'baches.volumen_m3 as volumen_inyectado',
                            'materiales.stock_actual',
                            'materiales.unidad_medida',
                            'baches.updated_at as fecha_consumo'
                        )
                        ->where('baches.estado', '=', 'Reparado')
                        ->whereBetween('baches.updated_at', [$inicio, $fin])
                        ->orderBy('baches.updated_at', 'desc')
                        ->get();
                    break;

                // 4. Reporte de Telemetría e Impactos de la Flota (JOIN: Baches + Vehiculos)
                case 'telemetria':
                    $data = DB::table('baches')
                        ->join('vehiculos', 'baches.vehiculo_id', '=', 'vehiculos.id')
                        ->select(
                            'vehiculos.placa',
                            'vehiculos.marca',
                            'vehiculos.modelo',
                            'baches.id as bache_id',
                            'baches.severidad',
                            'baches.latitud',
                            'baches.longitud',
                            'baches.created_at as fecha_deteccion'
                        )
                        ->whereBetween('baches.created_at', [$inicio, $fin])
                        ->orderBy('baches.created_at', 'desc')
                        ->get();
                    break;

                // 5. Reporte de Auditoría de Usuarios y Seguridad (JOIN: Users + Roles)
                case 'usuarios':
                    $data = DB::table('users')
                        ->join('roles', 'users.role_id', '=', 'roles.id')
                        ->select(
                            'users.id as usuario_id',
                            DB::raw("CONCAT(users.nombres, ' ', users.apellido_paterno, ' ', users.apellido_materno) as nombre_completo"),
                            'users.email',
                            'users.celular',
                            'roles.nombre as rol_asignado',
                            'users.created_at as fecha_registro'
                        )
                        ->get();
                    break;

                default:
                    return response()->json(['res' => false, 'message' => 'Tipo de reporte no válido.'], 400);
            }

            return response()->json([
                'res' => true,
                'datos' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'res' => false,
                'message' => 'Error en consulta relacional (JOIN): ' . $e->getMessage()
            ], 500);
        }
    }
}
