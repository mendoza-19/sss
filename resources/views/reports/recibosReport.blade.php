<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte de Recibos y Gastos - El Buen Sabor</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        }

        body {
            padding: 30px;
            color: #333333;
            background-color: #ffffff;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #8b5a2b;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .header h1 {
            color: #4a3525;
            font-size: 24px;
            margin-bottom: 5px;
        }

        .header h2 {
            color: #8b5a2b;
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .header .meta {
            font-size: 12px;
            color: #777777;
        }

        .summary-boxes {
            width: 100%;
            margin-bottom: 25px;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
        }

        .summary-cell {
            padding: 12px;
            background-color: #f7ede2;
            border: 1px solid #d7ccc8;
            border-radius: 6px;
            width: 25%;
        }

        .summary-cell .label {
            font-size: 11px;
            text-transform: uppercase;
            color: #6d4c41;
            font-weight: bold;
        }

        .summary-cell .value {
            font-size: 18px;
            color: #4a3525;
            font-weight: bold;
            margin-top: 5px;
        }

        .section-title {
            color: #4a3525;
            font-size: 15px;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
            padding-left: 8px;
            border-left: 4px solid #8b5a2b;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            font-size: 12px;
        }

        .report-table th {
            background-color: #c18c5d;
            color: #ffffff;
            padding: 8px 10px;
            text-align: left;
            font-weight: bold;
            font-size: 12px;
            border: 1px solid #b37d4d;
        }

        .report-table td {
            padding: 7px 10px;
            border: 1px solid #e0e0e0;
            color: #444444;
        }

        .report-table tbody tr:nth-child(even) {
            background-color: #faf7f2;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .badge-pagado {
            display: inline-block;
            padding: 3px 6px;
            background-color: #e8f5e9;
            color: #2e7d32;
            border-radius: 4px;
            font-weight: bold;
            font-size: 10px;
        }

        .badge-pendiente {
            display: inline-block;
            padding: 3px 6px;
            background-color: #fff3e0;
            color: #e65100;
            border-radius: 4px;
            font-weight: bold;
            font-size: 10px;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #888888;
            border-top: 1px solid #eeeeee;
            padding-top: 10px;
        }

        .highlight-card {
            background-color: #fff8e1;
            border: 1px solid #ffe082;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .highlight-card strong {
            color: #ff6f00;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Panadería & Repostería "El Buen Sabor"</h1>
        <h2>Reporte General de Recibos y Gastos en Compras</h2>
        <div class="meta">
            <span>Fecha de Emisión: {{ date('d/m/Y') }}</span> |
            <span>Hora: {{ date('H:i:s') }}</span> |
            <span>Año analizado: {{ $currentYear }}</span>
        </div>
    </div>

    <!-- Cajas de Resumen -->
    <table class="summary-table">
        <tr>
            <td class="summary-cell">
                <div class="label">Total Recibos</div>
                <div class="value">{{ $totalRecibos }}</div>
            </td>
            <td class="summary-cell">
                <div class="label">Recibos Pagados</div>
                <div class="value" style="color: #2e7d32;">{{ $totalPagados }}</div>
            </td>
            <td class="summary-cell">
                <div class="label">Recibos Pendientes</div>
                <div class="value" style="color: #e65100;">{{ $totalPendientes }}</div>
            </td>
            <td class="summary-cell">
                <div class="label">Gasto Total Acumulado</div>
                <div class="value" style="color: #8b5a2b;">${{ number_format($sumaTotalGastos, 2) }}</div>
            </td>
        </tr>
    </table>

    <!-- Ingrediente más comprado -->
    <div class="section-title">Ingrediente Más Comprado</div>
    @if($ingredienteMasComprado)
    <div class="highlight-card">
        <strong>{{ $ingredienteMasComprado->nombre }}</strong> — Total adquirido:
        <b>{{ $ingredienteMasComprado->total_cantidad }} {{ $ingredienteMasComprado->medida }}</b>
        (Gasto acumulado: ${{ number_format($ingredienteMasComprado->gasto_total, 2) }})
    </div>
    @else
    <p style="font-size: 12px; color: #888; margin-bottom: 15px;">No hay registros de compras de ingredientes registrados aún.</p>
    @endif

    <!-- Ranking de Ingredientes -->
    @if(count($topIngredientes) > 0)
    <table class="report-table">
        <thead>
            <tr>
                <th>Ingrediente</th>
                <th class="text-center">Unidad de Medida</th>
                <th class="text-right">Cantidad Total</th>
                <th class="text-right">Gasto Estimado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($topIngredientes as $ing)
            <tr>
                <td>{{ $ing->nombre }}</td>
                <td class="text-center">{{ $ing->medida }}</td>
                <td class="text-right"><b>{{ $ing->total_cantidad }}</b></td>
                <td class="text-right">${{ number_format($ing->gasto_total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- Gastos por Mes -->
    <div class="section-title">Gastos por Mes (Año {{ $currentYear }})</div>
    <table class="report-table">
        <thead>
            <tr>
                <th>Mes</th>
                <th class="text-center">N° Facturas / Recibos</th>
                <th class="text-right">Total Gastado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($gastosPorMes as $gasto)
            <tr>
                <td>{{ $gasto->nombre_mes }}</td>
                <td class="text-center">{{ $gasto->cantidad_facturas }}</td>
                <td class="text-right"><b>${{ number_format($gasto->total_gastado, 2) }}</b></td>
            </tr>
            @empty
            <tr>
                <td colspan="3" class="text-center" style="color: #888;">No hay gastos registrados en el año {{ $currentYear }}</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Listado de Recibos Recientes -->
    <div class="section-title">Historial de Recibos y Facturas</div>
    <table class="report-table">
        <thead>
            <tr>
                <th>N°</th>
                <th>Fecha Emisión</th>
                <th>Fecha Pago / Estado</th>
                <th>Proveedor</th>
                <th class="text-right">Monto Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($facturas as $factura)
            <tr>
                <td>#{{ $factura->idfactura }}</td>
                <td>{{ \Carbon\Carbon::parse($factura->emicion)->format('d/m/Y') }}</td>
                <td>
                    @if($factura->fechapago)
                        <span class="badge-pagado">Pagado ({{ \Carbon\Carbon::parse($factura->fechapago)->format('d/m/Y') }})</span>
                    @else
                        <span class="badge-pendiente">Pendiente</span>
                    @endif
                </td>
                <td>{{ $factura->proveedors ? $factura->proveedors->nombre : 'Proveedor sin registrar' }}</td>
                <td class="text-right"><b>${{ number_format($factura->total, 2) }}</b></td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center" style="color: #888;">No hay facturas registradas en el sistema.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Documento generado automáticamente por PanaderíaSYS &copy; {{ date('Y') }}. Todos los derechos reservados.
    </div>
</body>

</html>
