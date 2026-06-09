<?php

namespace App\Http\Controllers;

use App\Models\Bache;
use App\Models\Material;
use App\Models\Vehiculo;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * 📊 MÓDULO ANALÍTICO CORREGIDO: Recopilar estadísticas sin depender de 'stock_minimo'
     */
    public function getStats()
    {
        try {
            // 1. Contadores Estratégicos Globales
            $totalBaches = Bache::count();
            $reparados   = Bache::where('estado', 'Reparado')->count();
            $pendientes  = Bache::where('estado', 'Pendiente')->count();
            $enProceso   = Bache::whereIn('estado', ['Asignado', 'En proceso'])->count();

            // Computamos la tasa de efectividad municipal de la alcaldía
            $eficiencia  = $totalBaches > 0 ? round(($reparados / $totalBaches) * 100, 1) : 0;

            // 2. Conteo por Niveles de Criticidad (Para las barras analíticas)
            $alta  = Bache::where('severidad', 'Alta')->count();
            $media = Bache::where('severidad', 'Media')->count();
            $baja  = Bache::where('severidad', 'Baja')->count();

            // 3. 🔥 SOLUCIÓN ALMACÉN: Filtramos insumos con menos de 5.00 m³ y creamos la propiedad dinámicamente
            $materialesCriticos = Material::where('stock_actual', '<=', 5.00)
                ->select('nombre', 'stock_actual')
                ->get()
                ->map(function($material) {
                    // Inyectamos el valor esperado por Vue sin necesidad de la columna en la base de datos
                    $material->stock_minimo = 5.00; 
                    return $material;
                });

            // 4. Mapeo Estadístico por Macrodistritos o Zonas
            $bachesRaw = Bache::all();
            $distribucionZonas = [];
            
            foreach ($bachesRaw as $b) {
                $zonaNombre = is_object($b->zona) ? ($b->zona->nombre ?? 'Centro') : ($b->zona ?? 'Centro');
                
                if (!isset($distribucionZonas[$zonaNombre])) {
                    $distribucionZonas[$zonaNombre] = 0;
                }
                $distribucionZonas[$zonaNombre]++;
            }

            $zonasFormateadas = [];
            foreach ($distribucionZonas as $name => $count) {
                $zonasFormateadas[] = [
                    'zona'  => $name,
                    'total' => $count
                ];
            }

            // Retornamos el paquete unificado en un estado 200 limpio de éxito
            return response()->json([
                'res' => true,
                'contadores' => [
                    'total'      => $totalBaches,
                    'reparados'  => $reparados,
                    'pendientes' => $pendientes,
                    'en_proceso' => $enProceso,
                    'eficiencia' => $eficiencia
                ],
                'severidad' => [
                    'alta'  => $alta,
                    'media' => $media,
                    'baja'  => $baja
                ],
                'zonas' => $zonasFormateadas,
                'alertas_inventario' => $materialesCriticos
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'res'     => false,
                'message' => 'Error interno: ' . $e->getMessage()
            ], 500);
        }
    }
}