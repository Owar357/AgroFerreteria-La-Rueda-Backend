<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Ticket de Venta</title>

<style>
@page{
    size: 80mm auto;
    margin-left: 10mm;
    margin-right: 10mm;
    margin-top: 5mm;
    margin-bottom: 5mm;
}

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family: DejaVu Sans, monospace;
    font-size:10px;
    color:#000;
}

.ticket{
    width: calc(100% - 8mm);
    margin: 0 auto;
}

.logo{
    text-align:center;
    margin-bottom:8px;
}

.logo img{
    width:45px;
    margin-bottom:4px;
}

.logo h2{
    font-size:15px;
    margin-bottom:2px;
}

.logo p{
    font-size:9px;
}

.info{
    line-height:14px;
    margin-bottom:6px;
}

hr{
    border:none;
    border-top:1px dashed #444;
    margin:6px 0;
}

.detalle{
    margin-bottom:7px;
}

.detalle strong{
    font-size:11px;
}

.detalle small{
    font-size:8px;
    color:#555;
}

.linea{
    overflow:hidden;
}

.izq{
    float:left;
}

.der{
    float:right;
}

table{
    width:100%;
    border-collapse:collapse;
}

td{
    padding:2px 0;
}

.right{
    text-align:right;
}

.total td{
    border-top:1px dashed #444;
    border-bottom:1px dashed #444;
    font-weight:bold;
    font-size:12px;
    padding:4px 0;
}

.footer{
    text-align:center;
    margin-top:10px;
    font-size:10px;
}
</style>
</head>

<body>

<div class="ticket">

<div class="logo">
    <img src="{{ public_path('img/logo.jpeg') }}">
    <h2>AGROFERRETERÍA LA RUEDA</h2>
    <p>Ferretería y Productos Agrícolas</p>
</div>

<div class="info">
<b>Factura:</b> {{ $venta->numero_factura }}<br>
<b>Fecha:</b> {{ $venta->created_at->format('d/m/Y h:i:s A') }}<br>
<b>Cliente:</b> {{ $venta->cliente->nombre }}<br>
<b>Atendió:</b> {{ $venta->vendidoPor->name }}
</div>

<hr>

@foreach($venta->detallesVenta as $detalle)

<div class="detalle">

<strong>{{ $detalle->nombre_producto }}</strong><br>

<small>{{ $detalle->presentacion }}</small>

<table style="width:100%; border:none; margin-top:2px;">
    <tr>
        <td style="border:none; width:70%;">
            {{ number_format($detalle->cantidad,2) }}
            x
            ${{ number_format($detalle->precio_unitario,2) }}
        </td>

        <td style="border:none; width:30%; text-align:right;">
            ${{ number_format($detalle->subtotal,2) }}
        </td>
    </tr>
</table>

@endforeach

<hr>

<table>

<tr>
<td>Descuento</td>
<td class="right">${{ number_format($venta->detallesVenta->sum('descuento_aplicado'),2) }}</td>
</tr>

<tr>
<td>Ventas Exentas</td>
<td class="right">${{ number_format($venta->exento,2) }}</td>
</tr>

<tr>
<td>Ventas Gravadas</td>
<td class="right">${{ number_format($venta->gravado,2) }}</td>
</tr>

<tr>
<td>Subtotal</td>
<td class="right">${{ number_format($venta->gravado + $venta->exento,2) }}</td>
</tr>

<tr>
<td>IVA</td>
<td class="right">${{ number_format($venta->iva,2) }}</td>
</tr>

<tr class="total">
<td>TOTAL</td>
<td class="right">${{ number_format($venta->total,2) }}</td>
</tr>

<tr>
<td>Efectivo</td>
<td class="right">${{ number_format($venta->efectivo_recibido ?? 0,2) }}</td>
</tr>

<tr>
<td>Cambio</td>
<td class="right">${{ number_format($venta->cambio,2) }}</td>
</tr>

</table>

<hr>

<div class="footer">
<strong>¡Gracias por preferirnos!</strong><br>
Agroferretería La Rueda
</div>

</div>

</body>
</html>
