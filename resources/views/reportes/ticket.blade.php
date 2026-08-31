<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Ticket de Venta</title>

<style>
@page {
    size: 80mm auto;
    margin-left: 5mm;
    margin-right: 5mm;
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
    width: 100%;
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
    font-size:14px;
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
    font-size:10px;
}

.detalle small{
    font-size:8px;
    color:#333;
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
    font-size:9px;
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
<b>Cliente:</b> {{ $venta->cliente?->nombre ?? 'Consumidor Final' }}<br>
<b>Tipo de Pago:</b> {{ $venta->tipo_pago }}<br>
<b>Atendió:</b> {{ $venta->vendidoPor->name ?? 'Cajero' }}
</div>

<hr>

@foreach($venta->detallesVenta as $detalle)
<div class="detalle">
    <strong>{{ $detalle->nombre_producto }}</strong><br>
    
    <small>
        Presentación: {{ $detalle->presentacion }} 
        @if(isset($detalle->unidad_medida))
            ({{ $detalle->unidad_medida }})
        @endif
    </small>

    <table style="width:100%; border:none; margin-top:2px;">
        <tr>
            <td style="border:none; width:70%;">
                {{ number_format($detalle->cantidad, 2) }} x ${{ number_format($detalle->precio_unitario, 2) }}
                @if(($detalle->descuento_aplicado ?? 0) > 0)
                    <br><small style="color:#555;">(Desc. -${{ number_format($detalle->descuento_aplicado, 2) }})</small>
                @endif
            </td>
            <td style="border:none; width:30%; text-align:right; vertical-align:top;">
                ${{ number_format($detalle->subtotal, 2) }}
            </td>
        </tr>
    </table>
</div>
@endforeach

<hr>

<table>

{{-- 1. DESCUENTO GLOBAL (SI EXISTE) --}}
@if(($venta->detallesVenta->sum('descuento_aplicado') ?? 0) > 0)
<tr>
    <td>Descuento Total</td>
    <td class="right">-${{ number_format($venta->detallesVenta->sum('descuento_aplicado'), 2) }}</td>
</tr>
@endif

{{-- 2. VENTA GRAVADA (BASE IMPONIBLE SIN IVA) --}}
<tr>
    <td>Venta Gravada</td>
    <td class="right">${{ number_format($venta->gravado, 2) }}</td>
</tr>

{{-- 3. VENTA EXENTA (PRODUCTOS SIN IVA, EJ. MEDICAMENTOS O LEYES ESPECIALES) --}}
<tr>
    <td>Venta Exenta</td>
    <td class="right">${{ number_format($venta->exento, 2) }}</td>
</tr>

{{-- 4. MONTO DE IVA (13%) --}}
<tr>
    <td>IVA (13%)</td>
    <td class="right">${{ number_format($venta->iva, 2) }}</td>
</tr>

{{-- 5. SUB-TOTAL FISCAL --}}
<tr>
    <td>Subtotal</td>
    <td class="right">${{ number_format($venta->gravado + $venta->exento + $venta->iva, 2) }}</td>
</tr>

{{-- 6. TOTAL FINAL A PAGAR --}}
<tr class="total">
    <td>TOTAL A PAGAR</td>
    <td class="right">${{ number_format($venta->total, 2) }}</td>
</tr>

{{-- 7. DETALLE DE PAGO (SOLO SI ES EN EFECTIVO) --}}
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
    <strong>¡Gracias por preferirnos!</strong><br>
    Agroferretería La Rueda
</div>

</div>

</body>
</html>