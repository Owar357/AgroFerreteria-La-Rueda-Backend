<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte de Compras por Proveedor</title>

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
        }

        .subtitulo {
            font-size: 14px;
            font-weight: bold;
            margin-top: 12px;
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
            Reporte de Compras por Proveedor
        </div>

        <div class="apartado-fechas">

            <strong>Período:</strong>

            Desde
            {{ \Carbon\Carbon::parse($fecha_inicio)->format('d/m/Y') }}

            Hasta
            {{ \Carbon\Carbon::parse($fecha_fin)->format('d/m/Y') }}

        </div>

    </div>

    <table>

        <thead>

            <tr>

                <th style="width: 8%;">
                    POSICIÓN
                </th>

                <th style="width: 32%;">
                    PROVEEDOR
                </th>

                <th style="width: 20%;">
                    MONTO TOTAL
                </th>

                <th style="width: 20%;">
                    NÚMERO DE COMPRAS
                </th>

                <th style="width: 20%;">
                    PRODUCTOS DISTINTOS
                </th>

            </tr>

        </thead>

        <tbody>

            @php
                $totalCompras = 0;
                $totalMonto = 0;
                $totalProductos = 0;
            @endphp

            @forelse($compras as $index => $compra)

                @php
                    $totalCompras += $compra->numero_compras;
                    $totalMonto += $compra->monto_total;
                    $totalProductos += $compra->productos_distintos;
                @endphp

                <tr>

                    <td>
                        <strong>
                            {{ $index + 1 }}
                        </strong>
                    </td>

                    <td>
                        <strong>
                            {{ $compra->nombre }}
                        </strong>
                    </td>

                    <td class="text-right">
                        ${{ number_format($compra->monto_total, 2) }}
                    </td>

                    <td>
                        {{ $compra->numero_compras }}
                    </td>

                    <td>
                        {{ $compra->productos_distintos }}
                    </td>

                </tr>

            @empty

                <tr>

                    <td colspan="5" style="padding: 20px; color: #777;">
                        No se encontraron compras en el período seleccionado.
                    </td>

                </tr>

            @endforelse

        </tbody>

    </table>

    <table class="totales">

        <tr>

            <td class="text-right">
                <strong>Total comprado:</strong>
            </td>

            <td class="text-right"
                style="width: 40%; border-bottom: 1px solid #ccc;">

                ${{ number_format($totalMonto, 2) }}

            </td>

        </tr>

        <tr>

            <td class="text-right">
                <strong>Número de compras:</strong>
            </td>

            <td class="text-right"
                style="border-bottom: 1px solid #ccc;">

                {{ $totalCompras }}

            </td>

        </tr>

        <tr>

            <td class="text-right">
                <strong>Productos distintos:</strong>
            </td>

            <td class="text-right"
                style="border-bottom: 2px double #333; font-weight: bold;">

                {{ $totalProductos }}

            </td>

        </tr>

    </table>

</body>

</html>
