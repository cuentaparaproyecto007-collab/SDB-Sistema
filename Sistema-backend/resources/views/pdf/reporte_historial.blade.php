<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte SDB</title>
    <style>
        body { font-family: "Helvetica Neue", "Helvetica", sans-serif; color: #2c3e50; padding: 5px; }
        .header-report { text-align: center; margin-bottom: 25px; background: #2c3e50; color: white; padding: 20px; border-radius: 8px; }
        .header-report h2 { margin: 0; font-size: 22px; letter-spacing: 0.5px; }
        .header-report h3 { margin: 6px 0 0 0; font-size: 13px; font-weight: normal; opacity: 0.85; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background: #f1f5f9; color: #475569; padding: 12px 10px; font-size: 11px; border-bottom: 2px solid #cbd5e1; text-transform: uppercase; text-align: left; font-weight: bold; }
        td { padding: 12px 10px; border-bottom: 1px solid #e2e8f0; font-size: 11px; color: #334155; line-height: 1.4; }
        tr:nth-child(even) { background-color: #f8fafc; }
        .badge { padding: 3px 8px; border-radius: 4px; font-size: 9px; font-weight: bold; text-transform: uppercase; display: inline-block; }
        .Alta { background: #fdedec; color: #e74c3c; }
        .Media { background: #fef9e7; color: #f39c12; }
        .Baja { background: #e8f8f5; color: #2ecc71; }
        .text-muted { color: #64748b; font-size: 10px; }
    </style>
</head>
<body>

    <div class="header-report">
        <h2>SISTEMA DE GESTIÓN VIAL S.D.B.</h2>
        <h3>Reporte Ejecutivo de Historial de Reparaciones Viales</h3>
        <p style="font-size: 11px; margin: 8px 0 0 0; opacity: 0.7;">Fecha de Emisión: {{ $fechaEmision }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Fecha Reparación</th>
                <th>Ubicación / Zona</th>
                <th>Severidad</th>
                <th>Cuadrilla Ejecutora</th>
                <th>Material Utilizado</th>
                <th>Volumen</th>
            </tr>
        </thead>
        <tbody>
            @foreach($historial as $obra)
                @php
                    // Mapeo seguro tolerando si zona es objeto o string
                    $zonaNombre = 'La Paz';
                    if ($obra->zona) {
                        $zonaNombre = is_object($obra->zona) ? ($obra->zona->nombre ?? 'La Paz') : $obra->zona;
                    }
                @endphp
                <tr>
                    <td style="font-weight: bold; color: #475569;">
                        {{ \Carbon\Carbon::parse($obra->updated_at)->format('d/m/Y H:i') }}
                    </td>
                    <td>
                        Zona: {{ $zonaNombre }} <br>
                        <span class="text-muted">ID Bache: #{{ $obra->id }}</span>
                    </td>
                    <td>
                        <span class="badge {{ $obra->severidad }}">{{ $obra->severidad }}</span>
                    </td>
                    <td>{{ $obra->cuadrilla ? $obra->cuadrilla->nombre : 'Cuadrilla Externa / General' }}</td>
                    <td>{{ $obra->material ? $obra->material->nombre : 'No especificado' }}</td>
                    <td style="font-weight: bold; color: #1e293b;">
                        {{ $obra->volumen_m3 ? number_format((float)$obra->volumen_m3, 4) : '0.0000' }} m³
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>