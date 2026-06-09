<?php

namespace App\Exports;

use Carbon\Carbon;

class HistorialExport
{
    protected $historial;

    // Recibimos la colección de baches ya filtrada por el controlador
    public function __construct($historial)
    {
        $this->historial = $historial;
    }

    /**
     * Se encarga de procesar el streaming de descarga limpio
     */
    public function descargar()
    {
        $fileName = "historial_obras_viales_" . date('d_m_Y_His') . ".csv";

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            
            // 💡 TRUCO DE COMPATIBILIDAD: UTF-8 BOM para que Excel lea tildes, eñes y símbolos de m³
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); 
            
            // 💡 Encabezados de las columnas (Agregamos el tercer parámetro ';' como delimitador)
            fputcsv($file, [
                'ID REPARACIÓN', 'FECHA FINALIZACIÓN', 'ZONA / UBICACIÓN', 
                'SEVERIDAD INICIAL', 'CUADRILLA ASIGNADA', 'MATERIAL UTILIZADO', 
                'VOLUMEN INYECTADO (m³)', 'EJE X (m)', 'EJE Y (m)', 'PROFUNDIDAD (cm)'
            ], ';');

            // Inyección de filas procesadas
            foreach ($this->historial as $obra) {
                $zonaNombre = 'La Paz';
                if ($obra->zona) {
                    $zonaNombre = is_object($obra->zona) ? ($obra->zona->nombre ?? 'La Paz') : $obra->zona;
                }

                // 💡 Cada fila de datos se escribe explícitamente separada por ';'
                fputcsv($file, [
                    $obra->id,
                    Carbon::parse($obra->updated_at)->format('d/m/Y H:i'),
                    $zonaNombre,
                    $obra->severidad,
                    $obra->cuadrilla ? $obra->cuadrilla->nombre : 'Cuadrilla Externa / General',
                    $obra->material ? $obra->material->nombre : 'No especificado',
                    $obra->volumen_m3 ? number_format((float)$obra->volumen_m3, 4) : '0.0000',
                    $obra->eje_x,
                    $obra->eje_y,
                    $obra->profundidad_cm
                ], ';');
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}