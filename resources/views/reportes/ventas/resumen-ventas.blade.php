<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte de Resumen de Ventas - Agroferretería La Rueda</title>

    <style>
        @page {
            margin: 1.5cm 1.5cm 2cm 1.5cm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 10px;
        }

        footer {
            position: fixed;
            bottom: -1cm;
            left: 0px;
            right: 0px;
            height: 0.8cm;
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            color: #333;
        }

        .encabezado {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            position: relative;
        }

        .fecha-emision-top {
            position: absolute;
            top: 0;
            right: 0;
            font-size: 11px;
            color: #555;
            text-align: right;
        }

        .logo {
            width: 90px;
            margin-bottom: 8px;
        }

        .titulo {
            font-size: 22px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .datos-empresa {
            font-size: 11px;
            color: #555;
            margin-top: 5px;
            line-height: 1.4;
        }

        .subtitulo {
            font-size: 14px;
            font-weight: bold;
            margin-top: 12px;
            color: #222;
            text-transform: uppercase;
        }

        .apartado-fechas {
            font-size: 12px;
            margin-top: 6px;
            background-color: #f5f5f5;
            padding: 5px 10px;
            display: inline-block;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .resumen {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .resumen td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: center;
        }

        .resumen-titulo {
            background-color: #eaeaea;
            font-size: 11px;
            font-weight: bold;
        }

        .resumen-valor {
            font-size: 16px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
        }

        th {
            background-color: #eaeaea;
            border: 1px solid #ccc;
            padding: 8px;
            text-align: center;
            font-size: 11px;
        }

        td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .page-number:before {
            content: counter(page);
        }
    </style>
</head>

<body>

    <footer>
        Página <span class="page-number"></span>
    </footer>

    <div class="encabezado">

        <div class="fecha-emision-top">
            <strong>Reporte emitido el:</strong>
            {{ \Carbon\Carbon::now()->format('d/m/Y h:i A') }}
        </div>

        <img src="{{ public_path('img/logo.jpeg') }}" class="logo">

        <div class="titulo">
            AGROFERRETERÍA LA RUEDA
        </div>

        <div class="datos-empresa">
            <p>lotificación San Rafael, Aguilares, polígono 22, lote 13 y 14</p>
        </div>

        <div class="subtitulo">
            Resumen General y Tendencias de Ventas
        </div>

        <div class="apartado-fechas">
            <strong>Período:</strong>
            Desde {{ \Carbon\Carbon::parse($fecha_inicio)->format('d/m/Y') }}
            Hasta {{ \Carbon\Carbon::parse($fecha_fin)->format('d/m/Y') }}
        </div>

    </div>


    <table class="resumen">

        <tr>
            <td class="resumen-titulo">
                TOTAL VENDIDO
            </td>

            <td class="resumen-titulo">
                NÚMERO DE VENTAS
            </td>

            <td class="resumen-titulo">
                Gasto Promedio Por Venta
            </td>
        </tr>

        <tr>
            <td class="resumen-valor">
                ${{ number_format($total_vendido, 2, '.', ',') }}
            </td>

            <td class="resumen-valor">
                {{ $numero_ventas }}
            </td>

            <td class="resumen-valor">
                ${{ number_format($ticket_promedio, 2, '.', ',') }}
            </td>
        </tr>

    </table>


    <table>

        <thead>
            <tr>
                <th style="width: 35%;">
                    PERÍODO
                </th>

                <th style="width: 30%;">
                    NÚMERO DE VENTAS
                </th>

                <th style="width: 35%;">
                    TOTAL VENDIDO
                </th>
            </tr>
        </thead>

        <tbody>

            @forelse($serie as $periodo)

                <tr>
                    <td>
                        @if($tipo_agrupacion == 'diaria')
                            {{ \Carbon\Carbon::parse($periodo['periodo'])->format('d/m/Y') }}
                        @else
                            Semana del {{ \Carbon\Carbon::parse($periodo['periodo'])->format('d/m/Y') }}
                        @endif
                    </td>

                    <td>
                        {{ $periodo['cantidad_ventas'] }}
                    </td>

                    <td class="text-right">
                        ${{ number_format($periodo['total'], 2, '.', ',') }}
                    </td>
                </tr>

            @empty

                <tr>
                    <td colspan="3" style="padding: 20px; color: #777;">
                        No se encontraron ventas en el período seleccionado.
                    </td>
                </tr>

            @endforelse

        </tbody>

    </table>

</body>

</html>