<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte de Inventario Valorizado - Agroferretería La Rueda</title>

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
            left: 0;
            right: 0;
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

        .apartado-fecha {
            font-size: 12px;
            margin-top: 6px;
            background-color: #f5f5f5;
            padding: 5px 10px;
            display: inline-block;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
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

        .totales {
            margin-top: 20px;
            width: 300px;
            float: right;
            page-break-inside: avoid;
        }

        .totales td {
            border: none;
            padding: 5px;
        }

        .page-number:before {
            content: counter(page);
        }

    </style>
</head>

<body>

    <footer>
        <span class="page-number"></span>
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
            Reporte de Inventario Valorizado
        </div>

        <div class="apartado-fecha">
            <strong>Corte de inventario:</strong>
            {{ \Carbon\Carbon::now()->format('d/m/Y h:i A') }}
        </div>

    </div>


    <table>

        <thead>

            <tr>

                <th style="width: 7%;">
                    N°
                </th>

                <th style="width: 28%;">
                    PRODUCTO
                </th>

                <th style="width: 15%;">
                    STOCK ACTUAL
                </th>

                <th style="width: 17%;">
                    COSTO PROMEDIO
                </th>

                <th style="width: 17%;">
                    VALOR A COSTO
                </th>

                <th style="width: 16%;">
                    VALOR A VENTA
                </th>

            </tr>

        </thead>

        <tbody>

            @forelse($resultado as $index => $producto)

                <tr>

                    <td>
                        {{ $index + 1 }}
                    </td>

                    <td>
                        <strong>
                            {{ $producto->nombre }}
                        </strong>
                    </td>

                    <td>
                        {{ number_format($producto->cantidad_stock, 3) }}
                    </td>

                    <td class="text-right">
                        ${{ number_format($producto->costo_promedio, 3) }}
                    </td>

                    <td class="text-right">
                        ${{ number_format($producto->valor_costo, 3) }}
                    </td>

                    <td class="text-right">
                        ${{ number_format($producto->valor_venta, 3) }}
                    </td>

                </tr>

            @empty

                <tr>

                    <td colspan="6" style="padding: 20px; color: #777;">
                        No se encontraron productos con stock disponible.
                    </td>

                </tr>

            @endforelse

        </tbody>

    </table>


    <table class="totales">

        <tr>

            <td class="text-right">
                <strong>Stock total:</strong>
            </td>

            <td class="text-right"
                style="width: 40%; border-bottom: 1px solid #ccc;">

                {{ number_format($totalStock, 3) }}

            </td>

        </tr>

        <tr>

            <td class="text-right">
                <strong>Valor a costo:</strong>
            </td>

            <td class="text-right"
                style="border-bottom: 1px solid #ccc;">

                ${{ number_format($totalCosto, 3) }}

            </td>

        </tr>

        <tr>

            <td class="text-right">
                <strong>Valor a venta:</strong>
            </td>

            <td class="text-right"
                style="border-bottom: 2px double #333; font-weight: bold;">

                ${{ number_format($totalVenta, 3) }}

            </td>

        </tr>

    </table>

</body>

</html>
