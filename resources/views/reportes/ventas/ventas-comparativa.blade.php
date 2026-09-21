<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte Comparativo de Ventas - Agroferretería La Rueda</title>

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

        /* Estilos para la tabla contenedora de los bloques lado a lado */
        .tabla-contenedor {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            border: none;
        }

        .celda-bloque {
            width: 48%;
            vertical-align: top;
            border: 1px solid #ccc;
            padding: 12px;
            background-color: #fafafa;
        }

        .celda-espacio {
            width: 4%;
            border: none;
        }

        .bloque-h3 {
            margin: 0 0 8px 0;
            font-size: 14px;
            text-align: center;
        }

        .fecha {
            text-align: center;
            color: #555;
            margin-bottom: 12px;
            font-size: 11px;
        }

        .dato {
            margin: 6px 0;
            font-size: 11px;
        }

        .dato strong {
            display: inline-block;
            width: 120px;
        }

        .variacion {
            border: 1px solid #ccc;
            padding: 12px;
            text-align: center;
            margin-top: 20px;
            margin-bottom: 25px;
        }

        .variacion h3 {
            margin: 0 0 8px 0;
            font-size: 14px;
        }

        .porcentaje {
            font-size: 18px;
            font-weight: bold;
        }

        .titulo-tabla {
            font-size: 14px;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
        }

        table.tabla-datos {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table.tabla-datos th {
            background-color: #eaeaea;
            border: 1px solid #ccc;
            padding: 8px;
            text-align: center;
            font-size: 11px;
        }

        table.tabla-datos td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: center;
        }

        table.tabla-datos td:first-child {
            text-align: left;
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
            Reporte Comparativo de Ventas
        </div>
    </div>

  
    <table class="tabla-contenedor">
        <tr>
          
            <td class="celda-bloque">
                <h3 class="bloque-h3"> Período Anterior</h3>
                <div class="fecha">
                    {{ $inicio1->format('d/m/Y') }} - {{ $fin1->format('d/m/Y') }}
                </div>
                <div class="dato">
                    <strong>Días:</strong> {{ round($dias1) }}
                </div>
                <div class="dato">
                    <strong>Total vendido:</strong> ${{ number_format($total1, 2, '.', ',') }}
                </div>
                <div class="dato">
                    <strong>Número de ventas:</strong> {{ $cantidad1 }}
                </div>
                <div class="dato">
                    <strong>Promedio por venta:</strong> ${{ number_format($promedio1, 2, '.', ',') }}
                </div>
            </td>

    
            <td class="celda-espacio"></td>

        
            <td class="celda-bloque">
                <h3 class="bloque-h3">Período Actual</h3>
                <div class="fecha">
                    {{ $inicio2->format('d/m/Y') }} - {{ $fin2->format('d/m/Y') }}
                </div>
                <div class="dato">
                    <strong>Días:</strong> {{ round($dias2) }}
                </div>
                <div class="dato">
                    <strong>Total vendido:</strong> ${{ number_format($total2, 2, '.', ',') }}
                </div>
                <div class="dato">
                    <strong>Número de ventas:</strong> {{ $cantidad2 }}
                </div>
                <div class="dato">
                    <strong>Promedio por venta:</strong> ${{ number_format($promedio2, 2, '.', ',') }}
                </div>
            </td>
        </tr>
    </table>

    <div class="variacion">
        <h3>Variación de ventas</h3>
        <div class="porcentaje">
            {{ number_format($variacion, 2, '.', ',') }}%
        </div>
    </div>

    <div class="titulo-tabla">
        Comparación de resultados
    </div>

    <table class="tabla-datos">
        <thead>
            <tr>
                <th style="width: 40%;">CONCEPTO</th>
                <th style="width: 30%;">Período Anterior</th>
                <th style="width: 30%;">Período Actual</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Total vendido</strong></td>
                <td class="text-right">${{ number_format($total1, 2, '.', ',') }}</td>
                <td class="text-right">${{ number_format($total2, 2, '.', ',') }}</td>
            </tr>
            <tr>
                <td><strong>Número de ventas</strong></td>
                <td>{{ $cantidad1 }}</td>
                <td>{{ $cantidad2 }}</td>
            </tr>
            <tr>
                <td><strong>Promedio por venta</strong></td>
                <td class="text-right">${{ number_format($promedio1, 2, '.', ',') }}</td>
                <td class="text-right">${{ number_format($promedio2, 2, '.', ',') }}</td>
            </tr>
        </tbody>
    </table>

</body>

</html>