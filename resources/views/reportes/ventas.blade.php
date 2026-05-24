<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <style>

        body{
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
        }

        .encabezado{
            text-align: center;
            margin-bottom: 20px;
        }
        .logo{
            width: 90px;
            margin-bottom: 10px;
        }
        .titulo{
            font-size: 20px;
            font-weight: bold;
        }
        .subtitulo{
            font-size: 14px;
            margin-top: 5px;
        }
        table{
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th{
            background-color: #eaeaea;
            border: 1px solid #ccc;
            padding: 8px;
            text-align: center;
        }
        td{
            border: 1px solid #ccc;
            padding: 8px;
            text-align: center;
        }

        .totales{
            margin-top: 20px;
            width: 300px;
            float: right;
        }

        .totales td{
            border: none;
            text-align: right;
            padding: 5px;
        }

    </style>
</head>
<body>

    <div class="encabezado">
        <img
            src="{{ public_path('img/logo.jpeg') }}"
            class="logo"
        >
        <div class="titulo">
            AGROFERRETERÍA LA RUEDA
        </div>

        <div class="subtitulo">
            REPORTE DE VENTAS
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th>N°</th>
                <th>FACTURA</th>
                <th>FECHA Y HORA</th>
                <th>TIPO PAGO</th>
                <th>SUBTOTAL</th>
                <th>IVA</th>
                <th>TOTAL</th>
            </tr>
        </thead>
        <tbody>

            @php
                $subtotalGeneral = 0;
                $ivaGeneral = 0;
                $totalGeneral = 0;
            @endphp

            @foreach($ventas as $index => $venta)

                @php
                    $subtotalGeneral += $venta->subtotal;
                    $ivaGeneral += $venta->iva;
                    $totalGeneral += $venta->total;
                @endphp

                <tr>

                    <td>
                        {{ $index + 1 }}
                    </td>

                    <td>
                        {{ $venta->numero_factura }}
                    </td>

                    <td>
                        {{ $venta->created_at }}
                    </td>

                    <td>
                        {{ $venta->tipo_pago }}
                    </td>

                    <td>
                        ${{ number_format($venta->subtotal, 2) }}
                    </td>

                    <td>
                        ${{ number_format($venta->iva, 2) }}
                    </td>

                    <td>
                        ${{ number_format($venta->total, 2) }}
                    </td>

                </tr>

            @endforeach

        </tbody>

    </table>

    <table class="totales">

        <tr>
            <td>
                <strong>Subtotal:</strong>
            </td>

            <td>
                ${{ number_format($subtotalGeneral, 2) }}
            </td>
        </tr>

        <tr>
            <td>
                <strong>IVA:</strong>
            </td>

            <td>
                ${{ number_format($ivaGeneral, 2) }}
            </td>
        </tr>

        <tr>
            <td>
                <strong>Total:</strong>
            </td>

            <td>
                <strong>
                    ${{ number_format($totalGeneral, 2) }}
                </strong>
            </td>
        </tr>

    </table>

</body>
</html>
