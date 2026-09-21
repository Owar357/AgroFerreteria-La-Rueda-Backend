
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Productos Próximos a Vencer - Agroferretería La Rueda</title>

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
            color: #c0392b;
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

        /* Clases de Semáforo de Alertas */
        .badge-rojo {
            color: #721c24;
            background-color: #f8d7da;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
        }

        .badge-amarillo {
            color: #856404;
            background-color: #fff3cd;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
        }

        .badge-verde {
            color: #155724;
            background-color: #d4edda;
            padding: 2px 6px;
            border-radius: 3px;
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

        <div class="subtitulo">Reporte de Productos Próximos a Vencer</div>

        <div class="apartado-fecha">
            <strong>Emisión:</strong> {{ $fechaEmision->format('d/m/Y h:i A') }}
            | <strong>Umbral evaluado:</strong> Próximos {{ $diasUmbral }} días
        </div>
    </div>

    <table class="datos-reporte">
        <thead>
            <tr>
                <th style="width: 5%;">N°</th>
                <th style="width: 12%;">SKU</th>
                <th style="width: 15%;">LOTE INTERNO</th>
                <th style="width: 32%;">PRODUCTO / PRESENTACIÓN</th>
                <th style="width: 12%;">FECHA VENC.</th>
                <th style="width: 12%;">DÍAS REST.</th>
                <th style="width: 12%;">STOCK ACTUAL</th>
            </tr>
        </thead>

        <tbody>
            @forelse($lotes as $lote)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="text-center"><code>{{ $lote->sku }}</code></td>
                    <td class="text-center"><code>{{ $lote->lote_interno }}</code></td>
                    <td class="text-left">
                        <strong>{{ $lote->producto_nombre }}</strong>
                        @if($lote->tipo_producto === 'GRANEL')
                            <br><small style="color: #666;">Pres: Unidad Base (Venta a Granel)</small>
                        @elseif($lote->presentacion_nombre)
                            <br><small style="color: #666;">Pres: {{ $lote->presentacion_nombre }}</small>
                        @endif
                    </td>
                    <td class="text-center">
                        {{ \Carbon\Carbon::parse($lote->fecha_vencimiento)->format('d/m/Y') }}
                    </td>
                    <td class="text-center">
                        @if($lote->dias_restantes <= 15)
                            <span class="badge-rojo">{{ $lote->dias_restantes }} día(s)</span>
                        @elseif($lote->dias_restantes <= 30)
                            <span class="badge-amarillo">{{ $lote->dias_restantes }} día(s)</span>
                        @else
                            <span class="badge-verde">{{ $lote->dias_restantes }} día(s)</span>
                        @endif
                    </td>
                    <td class="text-right">
                        {{ number_format($lote->cantidad_actual, 2) }} {{ $lote->unidad_medida }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px; color: #777;">
                        No se encontraron lotes activos con vencimiento en los próximos {{ $diasUmbral }} días.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>

</html>