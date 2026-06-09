<!DOCTYPE html>
<html>
<head>
    <title>Reporte de Baches S.D.B.</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .alta { color: red; font-weight: bold; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Sistema de Detección de Baches (S.D.B.)</h1>
        <h3>Reporte Técnico de Daños Viales</h3>
        <p>Fecha de emisión: {{ date('d/m/Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Zona</th>
                <th>Severidad</th>
                <th>Estado</th>
                <th>Descripción</th>
            </tr>
        </thead>
        <tbody>
            @foreach($baches as $bache)
            <tr>
                <td>{{ $bache->id }}</td>
                <td>{{ $bache->zona->nombre ?? 'N/A' }}</td>
                <td class="{{ strtolower($bache->severidad) == 'alta' ? 'alta' : '' }}">
                    {{ $bache->severidad }}
                </td>
                <td>{{ $bache->estado }}</td>
                <td>{{ $bache->descripcion }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Generado por: {{ Auth::user()->name ?? 'Sistema Automático' }}
    </div>
</body>
</html>