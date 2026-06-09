<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Expediente Clínico Automotriz - S.D.B.</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #1e293b; margin: 0; padding: 0; font-size: 12px; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .title-area h2 { margin: 0; font-size: 11px; color: #64748b; letter-spacing: 0.5px; }
        .title-area h1 { margin: 4px 0; font-size: 18px; color: #0f172a; font-weight: bold; }
        .title-area p { margin: 2px 0; color: #475569; font-size: 11px; }
        .divider { border: none; border-top: 2px solid #1e293b; margin-top: 5px; margin-bottom: 20px; }
        
        /* Ficha del Vehículo */
        .specs-title { font-size: 13px; font-weight: bold; color: #1e293b; margin-bottom: 8px; text-transform: uppercase; }
        .specs-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; background-color: #f8fafc; border: 1px solid #e2e8f0; }
        .specs-table td { padding: 10px 14px; border: 1px solid #e2e8f0; }
        .label { font-weight: bold; color: #475569; width: 18%; }
        
        /* Tabla del Historial */
        .main-table { width: 100%; border-collapse: collapse; text-align: left; }
        .main-table th { background-color: #0f172a; color: #ffffff; font-weight: bold; padding: 10px 12px; font-size: 11px; text-transform: uppercase; }
        .main-table td { padding: 10px 12px; border-bottom: 1px solid #e2e8f0; vertical-align: top; font-size: 11px; }
        .desc-text { color: #334155; line-height: 1.4; }
        
        /* Badges */
        .badge { padding: 3px 7px; border-radius: 4px; font-weight: bold; font-size: 10px; }
        .status-progress { background-color: #fff7ed; color: #c2410c; }
        .status-done { background-color: #ecfdf5; color: #047857; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td class="title-area">
                <h2>SISTEMA DE GESTIÓN VIAL | S.D.B.</h2>
                <h1>EXPEDIENTE CLÍNICO DE REPARACIONES Y SOPORTE</h1>
                <p><strong>Fecha de Emisión:</strong> {{ $fechaEmision }}</p>
                <p><strong>Generado por:</strong> Administrador del Taller Municipal</p>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <div class="specs-title">Especificaciones Técnicas del Activo</div>
    <table class="specs-table">
        <tr>
            <td class="label">🚗 Marca:</td>
            <td>{{ $vehiculo->marca }}</td>
            <td class="label">📋 Modelo:</td>
            <td>{{ $vehiculo->modelo }}</td>
        </tr>
        <tr>
            <td class="label">🔑 Número Placa:</td>
            <td style="font-family: monospace; font-weight: bold;">{{ $vehiculo->placa }}</td>
            <td class="label">🛞 Tipo Unidad:</td>
            <td>{{ $vehiculo->tipo }}</td>
        </tr>
    </table>

    <div class="specs-title">Historial de Intervenciones Técnicas</div>
    <table class="main-table">
        <thead>
            <tr>
                <th style="width: 15%;">Fecha</th>
                <th style="width: 15%;">Clasificación</th>
                <th style="width: 40%;">Trabajo Realizado</th>
                <th style="width: 20%;">Encargado</th>
                <th style="width: 10%;">Estado</th>
            </tr>
        </thead>
        <tbody>
            @if(count($historial) === 0)
                <tr>
                    <td colspan="5" style="text-align: center; color: #94a3b8; font-style: italic; padding: 30px;">
                        Este vehículo no registra ingresos previos al taller mecánico.
                    </td>
                </tr>
            @else
                @foreach($historial as $item)
                    <tr>
                        <td style="font-weight: bold;">{{ $item['fecha'] }}</td>
                        <td>{{ $item['tipo'] }}</td>
                        <td class="desc-text">{{ $item['descripcion'] }}</td>
                        <td style="color: #475569;">{{ $item['tecnico'] }}</td>
                        <td>
                            <span class="badge {{ $item['estado'] === 'En Curso' ? 'status-progress' : 'status-done' }}">
                                {{ $item['estado'] }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>

</body>
</html>