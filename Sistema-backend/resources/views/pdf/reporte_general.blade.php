<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte General de Flota Vehicular</title>
    <style>
        body { font-family: "Helvetica", Arial, sans-serif; color: #2c3e50; margin: 20px; }
        .header { border-bottom: 3px solid #42b983; padding-bottom: 10px; margin-bottom: 25px; }
        .title { font-size: 20px; font-weight: bold; color: #2c3e50; text-transform: uppercase; }
        .meta-info { font-size: 11px; color: #7f8c8d; text-align: right; margin-top: -20px; }
        .custom-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 12px; }
        .custom-table th { background: #34495e; color: white; padding: 12px 10px; text-align: left; text-transform: uppercase; font-size: 11px; border-bottom: 3px solid #42b983; }
        .custom-table td { padding: 12px 10px; border-bottom: 1px solid #edf2f7; color: #2d3748; }
        .badge-hardware { background: #e2f8ff; color: #2b6cb0; padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; display: inline-block; }
        .no-hardware { background: #fff5f5; color: #c53030; }
        .total-box { margin-top: 25px; text-align: right; font-weight: bold; color: #2c3e50; font-size: 13px; }
    </style>
</head>
<body>

    <div class="header">
        <div class="title">S.D.B. - Reporte General de Flota Vehicular</div>
        <div class="meta-info">Emitido: {{ $fechaEmision }}<br>Gobierno Autónomo Municipal de La Paz</div>
    </div>

    <table class="custom-table">
        <thead>
            <tr>
                <th width="15%">Nro. Placa</th>
                <th width="25%">Marca / Fabricante</th>
                <th width="25%">Modelo de Unidad</th>
                <th width="15%">Tipo</th>
                <th width="20%">Hardware IoT Acoplado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($vehiculos as $v)
            <tr>
                <td style="font-family: monospace; font-size: 13px; font-weight: bold;">{{ $v->placa }}</td>
                <td>{{ $v->marca }}</td>
                <td>{{ $v->modelo }}</td>
                <td>{{ $v->tipo }}</td>
                <td>
                    @if($v->sensor)
                        <span class="badge-hardware">📟 {{ $v->sensor->codigo }}</span>
                    @else
                        <span class="badge-hardware no-hardware">❌ Sin Hardware</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-box">
        Total de Unidades Operativas Registradas: {{ $vehiculos->count() }}
    </div>

</body>
</html>