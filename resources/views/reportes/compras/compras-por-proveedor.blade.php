<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte Detallado de Compras por Proveedor</title>

    <style>
        @page {
            margin: 1cm 1.5cm 1.5cm 1.5cm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
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
            font-size: 10px;
            font-weight: bold;
        }

        .encabezado {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 12px;
            position: relative;
        }

        .fecha-emision-top {
            position: absolute;
            top: 0;
            right: 0;
            font-size: 10px;
            color: #555;
            text-align: right;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 5px;
        }

        .logo {
            width: 75px;
            height: auto;
        }

        .titulo {
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .datos-empresa {
            font-size: 10px;
            color: #555;
            margin-top: 3px;
        }

        .subtitulo {
            font-size: 12px;
            font-weight: bold;
            margin-top: 8px;
            text-transform: uppercase;
        }

        .apartado-fechas {
            font-size: 10px;
            margin-top: 6px;
            background-color: #f5f5f5;
            padding: 4px 8px;
            display: inline-block;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .proveedor-card {
            margin-top: 20px;
            border: 1px solid #ccc;
            padding: 10px;
            background-color: #fafafa;
        }

        .proveedor-header {
            font-size: 13px;
            font-weight: bold;
            color: #111;
            border-bottom: 1px solid #bbb;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }

        .compra-info {
            font-size: 11px;
            margin-top: 8px;
            margin-bottom: 5px;
            font-weight: bold;
            color: #444;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            background-color: #fff;
        }

        table.data-table th {
            background-color: #eaeaea;
            border: 1px solid #ccc;
            padding: 6px;
            text-align: center;
            font-size: 10px;
        }

        table.data-table td {
            border: 1px solid #ccc;
            padding: 6px;
            text-align: center;
            font-size: 10px;
        }

        .text-left { text-align: left; }
        .text-right { text-align: right; }

        .totales-wrapper {
            width: 100%;
            margin-top: 20px;
        }

        .totales {
            width: 300px;
            margin-left: auto;
            border-collapse: collapse;
        }

        .totales td {
            border: none;
            padding: 4px;
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
            <strong>Reporte emitido el:</strong><br>
            {{ \Carbon\Carbon::now()->format('d/m/Y h:i A') }}
        </div>

        <div class="logo-container">
            <img src="{{ public_path('img/logo.jpeg') }}" class="logo">
        </div>

        <div class="titulo">
            AGROFERRETERÍA LA RUEDA
        </div>

        <div class="datos-empresa">
            Lotificación San Rafael, Aguilares, polígono 22, lote 13 y 14
        </div>

        <div class="subtitulo">
            Reporte Detallado de Compras por Proveedor
        </div>

        <div class="apartado-fechas">
            <strong>Período:</strong>
            Desde {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }}
            Hasta {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}
        </div>

    </div>

    @php $montoGeneral = 0; $totalComprasGeneral = 0; @endphp

    @forelse($proveedores as $proveedor)

        <div class="proveedor-card">
            
            <div class="proveedor-header">
                PROVEEDOR: {{ $proveedor->nombre }}
            </div>

            @foreach($proveedor->compras as $compra)
                @php 
                    $montoGeneral += $compra->monto_total; 
                    $totalComprasGeneral++;
                @endphp

                <div class="compra-info">
                    Doc: {{ $compra->numero_documento ?? 'S/N' }} | 
                    Fecha: {{ \Carbon\Carbon::parse($compra->fecha_emision)->format('d/m/Y') }} | 
                    Monto: ${{ number_format($compra->monto_total, 2) }}
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 40%;">PRODUCTO / PRESENTACIÓN</th>
                            <th style="width: 15%;">CANT. FACTURADA</th>
                            <th style="width: 15%;">CANT. BONIFICADA</th>
                            <th style="width: 15%;">PRECIO UNIT.</th>
                            <th style="width: 15%;">SUBTOTAL</th>
                        </tr>
                    </thead>
                    @foreach($compra->detallesCompra as $detalle)
    <tr>
        <td class="text-left">
            <strong>{{ $detalle->lote->producto->nombre ?? 'N/A' }}</strong>
            <br>
            <small>Pres: {{ $detalle->lote->presentacion->nombre ?? 'N/A' }}</small>
        </td>
        <td>{{ number_format($detalle->cantidad_facturada, 2) }}</td>
        <td>{{ number_format($detalle->cantidad_bonificada, 2) }}</td>
        <td class="text-right">${{ number_format($detalle->precio_unitario_factura, 2) }}</td>
        <td class="text-right">${{ number_format($detalle->sub_total, 2) }}</td>
    </tr>
@endforeach
                </table>
            @endforeach

        </div>

    @empty

        <div style="text-align: center; padding: 30px; color: #777;">
            No se encontraron compras en el período seleccionado.
        </div>

    @endforelse

    <div class="totales-wrapper">
        <table class="totales">
            <tr>
                <td class="text-right"><strong>Total de Compras:</strong></td>
                <td class="text-right" style="width: 40%; border-bottom: 1px solid #ccc;">
                    {{ $totalComprasGeneral }}
                </td>
            </tr>
            <tr>
                <td class="text-right"><strong>Inversión Total:</strong></td>
                <td class="text-right" style="border-bottom: 2px double #333; font-weight: bold;">
                    ${{ number_format($montoGeneral, 2) }}
                </td>
            </tr>
        </table>
    </div>

</body>

</html>