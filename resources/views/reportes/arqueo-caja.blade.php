<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <title>Reporte de Arqueo de Caja - Agroferretería La Rueda</title>

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

        .fecha-emision {
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

        .datos {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }

        .datos td {
            border: 1px solid #ccc;
            padding: 8px;
        }

        .datos td:first-child {
            font-weight: bold;
            background-color: #eaeaea;
            width: 35%;
        }

        .montos {
            width: 100%;
            margin-top: 25px;
            border-collapse: collapse;
        }

        .montos th {
            background-color: #eaeaea;
            border: 1px solid #ccc;
            padding: 8px;
            text-align: center;
        }

        .montos td {
            border: 1px solid #ccc;
            padding: 9px;
        }

        .text-right {
            text-align: right;
        }

        .resultado {
            margin-top: 25px;
            text-align: center;
            border: 2px solid #333;
            padding: 15px;
        }

        .estado {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .diferencia {
            font-size: 20px;
            font-weight: bold;
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

        <div class="fecha-emision">
            <strong>Reporte emitido el:</strong><br>
            {{ \Carbon\Carbon::now()->format('d/m/Y h:i A') }}
        </div>

        <img src="{{ public_path('img/logo.jpeg') }}" class="logo">

        <div class="titulo">
            AGROFERRETERÍA LA RUEDA
        </div>

        <div class="datos-empresa">
            Lotificación San Rafael, Aguilares, polígono 22, lote 13 y 14
        </div>

        <div class="subtitulo">
            Reporte de Cierre / Arqueo de Caja
        </div>

    </div>


    <table class="datos">

        <tr>
            <td>Cajero</td>
            <td>{{ $resultado['cajero'] }}</td>
        </tr>

        <tr>
            <td>Fecha y hora de apertura</td>
            <td>
                {{ \Carbon\Carbon::parse($resultado['fecha_apertura'])->format('d/m/Y h:i A') }}
            </td>
        </tr>

        <tr>
            <td>Fecha y hora de cierre</td>
            <td>
                @if($resultado['fecha_cierre'])
                    {{ \Carbon\Carbon::parse($resultado['fecha_cierre'])->format('d/m/Y h:i A') }}
                @else
                    Sin cierre registrado
                @endif
            </td>
        </tr>

    </table>


    <table class="montos">

        <thead>

            <tr>
                <th>CONCEPTO</th>
                <th>MONTO</th>
            </tr>

        </thead>

        <tbody>

            <tr>
                <td>Monto inicial</td>
                <td class="text-right">
                    ${{ number_format($resultado['monto_inicial'], 2) }}
                </td>
            </tr>

            <tr>
                <td>Ventas en efectivo</td>
                <td class="text-right">
                    ${{ number_format($resultado['ventas_efectivo'], 2) }}
                </td>
            </tr>

            <tr>
                <td><strong>Monto esperado</strong></td>
                <td class="text-right">
                    <strong>
                        ${{ number_format($resultado['monto_esperado'], 2) }}
                    </strong>
                </td>
            </tr>

            <tr>
                <td>Monto contado</td>
                <td class="text-right">
                    ${{ number_format($resultado['monto_contado'], 2) }}
                </td>
            </tr>

        </tbody>

    </table>


    <div class="resultado">

        <div class="estado">

            @if($resultado['estado_arqueo'] == 'SOBRANTE')
                SOBRANTE
            @elseif($resultado['estado_arqueo'] == 'FALTANTE')
                FALTANTE
            @else
                CUADRE EXACTO
            @endif

        </div>

        <div class="diferencia">

            Diferencia:
            ${{ number_format(abs($resultado['diferencia']), 2) }}

        </div>

    </div>

</body>

</html>
