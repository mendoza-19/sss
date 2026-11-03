<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <style>
        /**los siguientes son los estilos de la pagina */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Open Sans', sans-serif;
        }

        html,
        body {
            height: 100%;
        }

        .title-report {
            text-align: center;
            margin-bottom: 2rem;
            box-sizing: border-box;
        }

        .title-report h2 {
            color: #4a3525;
            padding-top: 2rem;
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .title-report p {
            line-height: 25px;
            color: #9f704b;
            font-size: 1.2rem;
        }

        .fecha-titulo {
            display: block;
            box-sizing: border-box;
        }

        .fecha-titulo p {
            font-size: 15px;
            font-weight: 600;
            text-align: center;
            color: grey;
        }

        .table-providers-container {
            box-sizing: border-box;
            margin-left: 20px;
            margin-right: 20px;

        }

        .table-providers-container table,
        tr,
        th,
        td {
            /*margin-top: 2rem;*/
            border-collapse: collapse;
            border: 2px solid #959595;
        }


        .title-container {
            padding-left: 10px;
            margin-top: 20px;
            height: 25px;
            border-left: 4px solid #8b5a2b;
            box-sizing: border-box;
            color: #4a3525;
        }


        .table-providers-container tbody {
            font-weight: 900;
        }

        /**fin estilos de pagina */
        /*Tabla*/

        .table-component {
            width: 100%;
            box-sizing: border-box;
        }

        .table-component thead {
            width: 100%;
            height: 50px;
            background-color: #c18c5d;
            box-sizing: border-box;
        }

        .table-component thead tr {
            width: 100%;
            height: 100%;
            box-sizing: border-box;
        }

        .table-component tbody tr:hover {
            background-color: #f7d2b7;
        }

        .table-component thead tr th {
            font-size: 20px;
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            color: #4a3525;
            box-sizing: border-box;
        }

        /*fin tabla*/
    </style>
    <div class="title-report">
        <h2>Panaderia "El buen sabor"</h2>
        <p>Reporte general de proveedores y recibos.</p>
        <div class="fecha-titulo">
            <p>Fecha de generacion: {{ date('d/m/Y') }} | Hora de generacion: {{ date('H:i:s') }}</p>
        </div>
    </div>
    <div class="table-providers-container">
        @foreach ($respuestas as $respuesta)
        <div class="title-container">
            {{ $respuesta['proveedor']->nombre }}
        </div>
        <table class="table-component table-providers">
            <thead>
                <tr>
                    <th>Mes</th>
                    <th>Total recibos</th>
                    <th>Valor total</th>
                    <th>Valor no pagado</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($respuesta['reporteFacturas'] as $reporteFactura)
                <tr>
                    <td>{{ $reporteFactura->mes_nombre }}</td>
                    <td>{{ $reporteFactura->facturas_emitidas }}</td>
                    <td>${{ number_format($reporteFactura->total_pagado, 2) }}</td>
                    <td>${{ number_format($reporteFactura->total_no_pagado, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endforeach
    </div>
</body>

</html>