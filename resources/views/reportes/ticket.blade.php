<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Ticket de Venta</title>

<style>@page {
    margin: 0; 
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'DejaVu Sans', sans-serif;
    font-size: 9px;
    line-height: 1.2;
    color: #000;
    padding: 4mm 3mm;
}

.ticket {
    width: 100%;
}

.logo {
    text-align: center;
    margin-bottom: 6px;
}

.logo img {
    width: 40px;
    height: auto;
    margin-bottom: 2px;
}

.logo h2 {
    font-size: 11px;
    font-weight: bold;
    margin-bottom: 1px;
    text-transform: uppercase;
}

.logo p {
    font-size: 8px;
    color: #333;
}

.info {
    font-size: 8.5px;
    line-height: 12px;
    margin-bottom: 4px;
}

hr {
    border: none;
    border-top: 1px dashed #333;
    margin: 4px 0;
}

.detalle {
    margin-bottom: 5px;
}

.detalle strong {
    font-size: 9px;
    display: block;
    word-wrap: break-word;
}

.detalle small {
    font-size: 7.5px;
    color: #444;
}

table {
    width: 100%;
    border-collapse: collapse;
}

td {
    padding: 1.5px 0;
    vertical-align: top;
    font-size: 8.5px;
}

.right {
    text-align: right;
}

.total td {
    border-top: 1px dashed #000;
    border-bottom: 1px dashed #000;
    font-weight: bold;
    font-size: 10px;
    padding: 3px 0;
}

.footer {
    text-align: center;
    margin-top: 8px;
    font-size: 8px;
    line-height: 11px;
}
</style>
</head>

<body>

<div class="ticket">

    <div class="logo">
        <img src="{{ public_path('img/logo.jpeg') }}">
        <h2>Agroferretería La Rueda</h2>
        <p>Ferretería y Productos Agrícolas</p>
    </div>

    <div class="info">
        <b>Factura:</b> {{ $venta->numero_factura }}<br>
        <b>Fecha:</b> {{ $venta->created_at->format('d/m/Y h:i:s A') }}<br>
        <b>Cliente:</b> {{ $venta->cliente?->nombre ?? 'Consumidor Final' }}<br>
        <b>Tipo de Pago:</b> {{ $venta->tipo_pago }}<br>
        <b>Atendió:</b> {{ $venta->vendidoPor->name ?? 'Cajero' }}
    </div>

    <hr>

    @foreach($venta->detallesVenta as $detalle)
    <div class="detalle">
        <strong>{{ $detalle->nombre_producto }}</strong>
        <table style="margin-top:2px;">
            <tr>
                <td style="width: 65%;">
                    {{ number_format($detalle->cantidad, 2) }} x ${{ number_format($detalle->precio_unitario, 2) }}
                    @if(($detalle->descuento_aplicado ?? 0) > 0)
                        <br><small style="color:#555;">(Desc. -${{ number_format($detalle->descuento_aplicado, 2) }})</small>
                    @endif
                </td>
                <td style="width: 35%;" class="right">
                    ${{ number_format($detalle->subtotal, 2) }}
                </td>
            </tr>
        </table>
    </div>
    @endforeach

    <hr>

    <table>
        @if(($venta->detallesVenta->sum('descuento_aplicado') ?? 0) > 0)
        <tr>
            <td>Descuento Total</td>
            <td class="right">-${{ number_format($venta->detallesVenta->sum('descuento_aplicado'), 2) }}</td>
        </tr>
        @endif

        <tr>
            <td>Venta Gravada</td>
            <td class="right">${{ number_format($venta->gravado, 2) }}</td>
        </tr>

        <tr>
            <td>Venta Exenta</td>
            <td class="right">${{ number_format($venta->exento, 2) }}</td>
        </tr>

        <tr>
            <td>IVA (13%)</td>
            <td class="right">${{ number_format($venta->iva, 2) }}</td>
        </tr>

        <tr>
            <td>Subtotal</td>
            <td class="right">${{ number_format($venta->gravado + $venta->exento + $venta->iva, 2) }}</td>
        </tr>

        <tr class="total">
            <td>TOTAL A PAGAR</td>
            <td class="right">${{ number_format($venta->total, 2) }}</td>
        </tr>

        @if($venta->tipo_pago === 'EFECTIVO')
        <tr>
            <td>Efectivo Recibido</td>
            <td class="right">${{ number_format($venta->efectivo_recibido ?? 0, 2) }}</td>
        </tr>
        <tr>
            <td>Cambio</td>
            <td class="right">${{ number_format($venta->cambio ?? 0, 2) }}</td>
        </tr>
        @endif
    </table>

    <hr>

    <div class="footer">
        <strong>¡Gracias por su compra!</strong><br>
        Agroferretería La Rueda
    </div>

</div>

</body>
</html>