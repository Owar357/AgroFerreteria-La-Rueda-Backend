<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte de Inventario Valorizado - Agroferretería La Rueda</title>

    <style>
        @page {
            margin: 1.2cm 1.2cm 1.8cm 1.2cm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
            margin: 0;
        }

        footer {
            position: fixed;
            bottom: -0.8cm;
            left: 0;
            right: 0;
            height: 0.8cm;
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            color: #555;
        }

        .encabezado {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 12px;
        }

        .logo {
            width: 80px;
            margin-bottom: 5px;
        }

        .titulo {
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .datos-empresa {
            font-size: 10px;
            color: #555;
            margin-top: 3px;
        }

        .subtitulo {
            font-size: 13px;
            font-weight: bold;
            margin-top: 8px;
            color: #222;
            text-transform: uppercase;
        }

        .apartado-fecha {
            font-size: 11px;
            margin-top: 6px;
            background-color: #f5f5f5;
            padding: 4px 10px;
            display: inline-block;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        table.datos-reporte {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table.datos-reporte th {
            background-color: #eaeaea;
            border: 1px solid #ccc;
            padding: 6px 4px;
            text-align: center;
            font-size: 10px;
        }

        table.datos-reporte td {
            border: 1px solid #ccc;
            padding: 6px 4px;
            font-size: 10.5px;
        }

        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }

        .totales-wrapper {
            margin-top: 15px;
            width: 320px;
            float: right;
            page-break-inside: avoid;
        }

        .totales-wrapper table {
            width: 100%;
            border-collapse: collapse;
        }

        .totales-wrapper td {
            padding: 4px;
            font-size: 11px;
        }

        .page-number:before {
            content: "Página " counter(page);
        }
    </style>
</head>

<body>

    <footer>
        <span class="page-number"></span>
    </footer>

    <div class="encabezado">
        <img src="{{ public_path('img/logo.jpeg') }}" class="logo">

        <div class="titulo">AGROFERRETERÍA LA RUEDA</div>

        <div class="datos-empresa">
            Lotificación San Rafael, Aguilares, polígono 22, lote 13 y 14
        </div>

        <div class="subtitulo">Reporte de Inventario Valorizado</div>

        <div class="apartado-fecha">
            <strong>Emisión y Corte:</strong> {{ $fechaEmision->format('d/m/Y h:i A') }}
            @if(isset($categoriaNombre) && $categoriaNombre)
                | <strong>Categoría:</strong> {{ $categoriaNombre }}
            @endif
        </div>
    </div>

    <table class="datos-reporte">
        <thead>
            <tr>
                <th style="width: 4%;">N°</th>
                <th style="width: 10%;">SKU</th>
                <th style="width: 28%;">PRODUCTO / PRESENTACIÓN</th>
                <th style="width: 12%;">STOCK ACTUAL</th>
                <th style="width: 10%;">COSTO PROM.</th>
                <th style="width: 10%;">VALOR COSTO</th>
                <th style="width: 10%;">PRECIO VTA.</th>
                <th style="width: 10%;">VALOR VENTA</th>
                <th style="width: 6%;">MARGEN (%)</th>
            </tr>
        </thead>

        <tbody>
            @forelse($productos as $producto)
                @php
                    $ganancia = $producto->valor_venta - $producto->valor_costo;
                    $margenPct = $producto->valor_venta > 0 ? ($ganancia / $producto->valor_venta) * 100 : 0;
                @endphp
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="text-center"><code>{{ $producto->codigo }}</code></td>
                    <td class="text-left">
                        <strong>{{ $producto->producto_nombre }}</strong>
                        @if($producto->presentacion_nombre)
                            <br><small style="color: #555;">Pres: {{ $producto->presentacion_nombre }}</small>
                        @endif
                    </td>
                    <td class="text-right">
                        {{ number_format($producto->cantidad_stock, 2) }} {{ $producto->unidad_medida }}
                    </td>
                    <td class="text-right">${{ number_format($producto->costo_promedio, 2) }}</td>
                    <td class="text-right">${{ number_format($producto->valor_costo, 2) }}</td>
                    <td class="text-right">${{ number_format($producto->precio_venta_unitario, 2) }}</td>
                    <td class="text-right">${{ number_format($producto->valor_venta, 2) }}</td>
                    <td class="text-right" style="{{ $margenPct < 0 ? 'color: red;' : '' }}">
                        {{ number_format($margenPct, 1) }}%
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="padding: 20px; color: #777;">
                        No se encontraron productos con stock disponible en esta categoría.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="totales-wrapper">
        <table>
            <tr>
                <td class="text-right"><strong>Stock total:</strong></td>
                <td class="text-right" style="border-bottom: 1px solid #ccc;">
                    {{ number_format($totalStock, 2) }}
                </td>
            </tr>
            <tr>
                <td class="text-right"><strong>Valor a costo:</strong></td>
                <td class="text-right" style="border-bottom: 1px solid #ccc;">
                    ${{ number_format($totalCosto, 2) }}
                </td>
            </tr>
            <tr>
                <td class="text-right"><strong>Valor a venta:</strong></td>
                <td class="text-right" style="border-bottom: 1px solid #ccc;">
                    ${{ number_format($totalVenta, 2) }}
                </td>
            </tr>
            <tr>
                <td class="text-right"><strong>Utilidad Estimada:</strong></td>
                <td class="text-right" style="border-bottom: 1px solid #ccc; color: green; font-weight: bold;">
                    ${{ number_format($totalGanancia, 2) }}
                </td>
            </tr>
            <tr>
                <td class="text-right"><strong>Margen Global:</strong></td>
                <td class="text-right" style="border-bottom: 2px double #333; font-weight: bold;">
                    {{ number_format($margenGlobal, 1) }}%
                </td>
            </tr>
        </table>
    </div>

</body>

</html>