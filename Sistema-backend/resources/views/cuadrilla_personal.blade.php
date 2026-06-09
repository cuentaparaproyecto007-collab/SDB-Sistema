<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Personal por Cuadrilla</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333; font-size: 12px; line-height: 1.4; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #2c3e50; padding-bottom: 10px; }
        .header h2 { margin: 5px 0; color: #2c3e50; text-transform: uppercase; }
        .header p { margin: 2px 0; color: #7f8c8d; font-size: 10px; }
        .meta-box { background: #f8fafc; padding: 15px; border-radius: 6px; margin-bottom: 25px; border-left: 5px solid #42b983; }
        .meta-box p { margin: 4px 0; font-size: 13px; }
        .custom-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .custom-table th { background: #34495e; color: white; padding: 10px; text-align: left; font-size: 11px; text-transform: uppercase; }
        .custom-table td { padding: 10px; border-bottom: 1px solid #edf2f7; font-size: 12px; }
        .custom-table tr:nth-child(even) { background-color: #f9f9f9; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 9px; color: #a0aec0; border-top: 1px solid #edf2f7; padding-top: 5px; }
    </style>
</head>
<body>

    <div class="header">
        <h2>S.D.B. Sistema de Detección de Baches</h2>
        <p>Reporte Oficial de Asignación de Personal Operativo — Gobierno Autónomo Municipal de La Paz</p>
    </div>

    <div class="meta-box">
        <p><strong>Equipo de Trabajo:</strong> {{ $cuadrilla->nombre }}</p>
        <p><strong>Código de Registro:</strong> #{{ $cuadrilla->id }}</p>
        <p><strong>Jefe Responsable:</strong> {{ $cuadrilla->jefe ? $cuadrilla->jefe->name : 'No Asignado' }}</p>
        <p><strong>Fecha de Emisión:</strong> {{ date('d/m/Y H:i:s') }}</p>
    </div>

    <h3>Lista de Personal Obrero Asignado</h3>
    <table class="custom-table">
        <thead>
            <tr>
                <th style="width: 15%;">Nro.</th>
                <th style="width: 55%;">Nombres y Apellidos</th>
                <th style="width: 30%;">Cédula de Identidad (CI)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($cuadrilla->obreros as $index => $obrero)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $obrero->nombres }} {{ $obrero->apellido_paterno }} {{ $obrero->apellido_materno }}</td>
                    <td><strong>{{ $obrero->ci }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align: center; color: #a0aec0; font-style: italic; padding: 20px;">
                        No se registran obreros dados de alta en esta cuadrilla actualmente.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Documento generado automáticamente por el Módulo de Control de Personal S.D.B.
    </div>

</body>
</html>