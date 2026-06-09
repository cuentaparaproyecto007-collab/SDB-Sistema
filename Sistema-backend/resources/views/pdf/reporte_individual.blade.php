<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Telemetría Vial</title>
    <style>
        body { font-family: "Helvetica", Arial, sans-serif; color: #2c3e50; margin: 20px; }
        .header { border-bottom: 3px solid #42b983; padding-bottom: 10px; margin-bottom: 20px; }
        .title { font-size: 20px; font-weight: bold; color: #2c3e50; text-transform: uppercase; }
        .meta-info { font-size: 11px; color: #7f8c8d; text-align: right; margin-top: -20px; }
        .card-vehiculo { background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px; margin-bottom: 25px; }
        .card-vehiculo table { width: 100%; font-size: 13px; }
        .badge-hardware { background: #bee3f8; color: #2b6cb0; padding: 2px 8px; border-radius: 4px; font-weight: bold; }
        .custom-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 12px; }
        .custom-table th { background: #34495e; color: white; padding: 10px; text-align: left; text-transform: uppercase; font-size: 11px; }
        .custom-table td { padding: 10px; border-bottom: 1px solid #edf2f7; }
        .badge-status { padding: 3px 8px; border-radius: 10px; font-size: 10px; font-weight: bold; }
        .status-critico { background: #fed7d7; color: #9b2c2c; }
        .status-moderado { background: #feebc8; color: #9c4221; }
        .status-leve { background: #c6f6d5; color: #22543d; }
        .total-box { margin-top: 20px; text-align: right; font-weight: bold; color: #2c3e50; font-size: 14px; }
    </style>
</head>
<body>

    <div class="header">
        <div class="title">S.D.B. - Reporte de Telemetría Vial</div>
        <div class="meta-info">Emitido: {{ $fechaEmision }}<br>Gobierno Autónomo Municipal</div>
    </div>

    <div class="card-vehiculo">
        <table>
            <tr>
                <td><strong>Vehículo Patrulla:</strong> {{ $vehiculo->marca }} {{ $vehiculo->modelo }}</td>
                <td><strong>Número de Placa:</strong> {{ $vehiculo->placa }}</td>
            </tr>
            <tr>
                <td><strong>Tipo de Unidad:</strong> {{ $vehiculo->tipo }}</td>
                <td><strong>Hardware IoT Acoplado:</strong> 
                    @if($vehiculo->sensor)
                        <span class="badge-hardware">{{ $vehiculo->sensor->codigo }} ({{ $vehiculo->sensor->modelo }})</span>
                    @else
                        Sin sensor asignado
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <h3>Historial de Baches Detectados Automáticamente</h3>
    <table class="custom-table">
        <thead>
            <tr>
                <th width="15%">Fecha/Hora</th>
                <th width="20%">Coordenadas (Lat, Lng)</th>
                <th width="45%">Descripción del Desgaste</th>
                <th width="20%" style="text-align: center;">Gravedad</th>
            </tr>
        </thead>
        <tbody>
            @if($vehiculo->baches->count() == 0)
                <tr>
                    <td colspan="4" style="text-align: center; color: #a0aec0; font-style: italic; padding: 30px;">
                        Este vehículo no registra detecciones automáticas de baches en la base de datos de viabilidad.
                    </td>
                </tr>
            @else
                @foreach($vehiculo->baches as $b)
                    @php
                        $claseStatus = 'status-leve';
                        if($b->estado === 'Crítico' || $b->estado === 'Critico') $claseStatus = 'status-critico';
                        if($b->estado === 'Moderado') $claseStatus = 'status-moderado';
                    @endphp
                    <tr>
                        <td>{{ $b->created_at->format('d/m/Y H:i') }}</td>
                        <td style="font-family: monospace;">{{ $b->latitud }}, {{ $b->longitud }}</td>
                        <td>{{ $b->descripcion ?? 'Detección por sensor de impacto viales.' }}</td>
                        <td style="text-align: center;">
                            <span class="badge-status {{ $claseStatus }}">{{ $b->estado }}</span>
                        </td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>

    <div class="total-box">
        Total Baches Detectados por la Unidad: {{ $vehiculo->baches->count() }}
    </div>

</body>
</html>