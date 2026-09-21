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

        .logo-container {
            text-align: center;
            margin-bottom: 5px;
        }

        .logo {
            width: 80px;
            height: auto;
            display: inline-block;
        }

        .titulo {
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 1px;
            margin-top: 5px;
        }

        .datos-empresa {
            font-size: 11px;
            color: #555;
            margin-top: 4px;
        }

        .subtitulo {
            font-size: 13px;
            font-weight: bold;
            margin-top: 10px;
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

        /* Sección de Firmas */
        .firmas-container {
            width: 100%;
            margin-top: 60px;
            border-collapse: collapse;
        }

        .firmas-container td {
            width: 50%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 30px;
        }

        .linea-firma {
            border-top: 1px solid #333;
            margin-bottom: 5px;
        }

        .cargo-firma {
            font-size: 11px;
            font-weight: bold;
            color: #555;
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
            Reporte de Cierre / Arqueo de Caja
        </div>

    </div>


    <table class="datos">

        <tr>
            <td>Cajero</td>
            <td>{{ $resultado['cajero'] ?? 'N/A' }}</td>
        </tr>

        <tr>
            <td>Fecha y hora de apertura</td>
            <td>
                @if(!empty($resultado['fecha_apertura']))
                    {{ \Carbon\Carbon::parse($resultado['fecha_apertura'])->format('d/m/Y h:i A') }}
                @else
                    Sin fecha registrada
                @endif
            </td>
        </tr>

        <tr>
            <td>Fecha y hora de cierre</td>
            <td>
                @if(!empty($resultado['fecha_cierre']))
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
                    ${{ number_format($resultado['monto_inicial'] ?? 0, 2) }}
                </td>
            </tr>

            <tr>
                <td>Ventas en efectivo</td>
                <td class="text-right">
                    ${{ number_format($resultado['ventas_efectivo'] ?? 0, 2) }}
                </td>
            </tr>

            <tr>
                <td><strong>Monto esperado</strong></td>
                <td class="text-right">
                    <strong>
                        ${{ number_format($resultado['monto_esperado'] ?? 0, 2) }}
                    </strong>
                </td>
            </tr>

            <tr>
                <td>Monto contado</td>
                <td class="text-right">
                    ${{ number_format($resultado['monto_contado'] ?? 0, 2) }}
                </td>
            </tr>

        </tbody>

    </table>


    <div class="resultado">

        <div class="estado">

            @if(($resultado['estado_arqueo'] ?? '') === 'SOBRANTE')
                SOBRANTE
            @elseif(($resultado['estado_arqueo'] ?? '') === 'FALTANTE')
                FALTANTE
            @else
                CUADRE EXACTO
            @endif

        </div>

        <div class="diferencia">

            Diferencia:
            ${{ number_format(abs($resultado['diferencia'] ?? 0), 2) }}

        </div>

    </div>

    <!-- Bloque de Firmas -->
    <table class="firmas-container">
        <tr>
            <td>
                <div class="linea-firma"></div>
                <strong>{{ $resultado['cajero'] ?? 'Firma del Cajero' }}</strong><br>
                <span class="cargo-firma">Cajero Responsable</span>
            </td>
            <td>
                <div class="linea-firma"></div>
                <strong>Firma y Sello</strong><br>
                <span class="cargo-firma">Supervisor / Administrador</span>
            </td>
        </tr>
    </table>

</body>

</html>