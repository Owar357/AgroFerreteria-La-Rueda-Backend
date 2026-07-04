<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas - Agroferretería La Rueda</title>

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
            width: 280px;
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
            <strong>Reporte emitido el:</strong> {{ \Carbon\Carbon::now()->format('d/m/Y h:i A') }}
        </div>

        <img src="{{ public_path('img/logo.jpeg') }}" class="logo">

        <div class="titulo">AGROFERRETERÍA LA RUEDA</div>

        <div class="datos-empresa">
            <p>lotificación San Rafael, Aguilares, polígono 22, lote 13 y 14</p>
        </div>

        <div class="subtitulo">Reporte de Ventas</div>

        <div class="apartado-fechas">
            @if(isset($fecha_desde) && isset($fecha_hasta) && $fecha_desde && $fecha_hasta)
                <strong>Período:</strong> Desde {{ \Carbon\Carbon::parse($fecha_desde)->format('d/m/Y') }} Hasta {{ \Carbon\Carbon::parse($fecha_hasta)->format('d/m/Y') }}
            @else
                <strong>Período:</strong> Historial General de Ventas
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">N°</th>
                <th style="width: 20%;">FACTURA</th>
                <th style="width: 20%;">FECHA Y HORA</th>
                <th style="width: 15%;">TIPO PAGO</th>
                <th style="width: 13%;">SUBTOTAL</th>
                <th style="width: 12%;">IVA</th>
                <th style="width: 15%;">TOTAL</th>
            </tr>
        </thead>
        <tbody>

            @php
                $subtotalGeneral = 0;
                $ivaGeneral = 0;
                $totalGeneral = 0;
            @endphp

            @forelse($ventas as $index => $venta)
                @php
                    $subtotalGeneral += $venta->subtotal;
                    $ivaGeneral += $venta->iva;
                    $totalGeneral += $venta->total;
                @endphp

                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><strong>{{ $venta->numero_factura }}</strong></td>
                    <td>{{ \Carbon\Carbon::parse($venta->created_at)->format('d/m/Y h:i A') }}</td>
                    <td>{{ $venta->tipo_pago }}</td>
                    <td class="text-right">${{ number_format($venta->subtotal, 2) }}</td>
                    <td class="text-right">${{ number_format($venta->iva, 2) }}</td>
                    <td class="text-right" style="font-weight: bold;">${{ number_format($venta->total, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="padding: 20px; color: #777;">
                        No se encontraron registros de ventas en el rango de fechas seleccionado.
                    </td>
                </tr>
            @endforelse

        </tbody>
    </table>

    <table class="totales">
        <tr>
            <td class="text-right"><strong>Subtotal General:</strong></td>
            <td class="text-right" style="width: 40%; border-bottom: 1px solid #ccc;">
                ${{ number_format($subtotalGeneral, 2) }}
            </td>
        </tr>
        <tr>
            <td class="text-right"><strong>IVA General:</strong></td>
            <td class="text-right" style="border-bottom: 1px solid #ccc;">
                ${{ number_format($ivaGeneral, 2) }}
            </td>
        </tr>
        <tr>
            <td class="text-right" style="font-size: 13px;"><strong>Total General:</strong></td>
            <td class="text-right" style="font-size: 13px; font-weight: bold; color: #000; border-bottom: 2px double #333;">
                ${{ number_format($totalGeneral, 2) }}
            </td>
        </tr>
    </table>

</body>

</html>
