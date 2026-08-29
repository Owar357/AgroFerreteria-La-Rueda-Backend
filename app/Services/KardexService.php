<?php

namespace App\Services;

use App\Models\Kardex;
use App\Models\Lote;
use App\Models\Presentacion;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Model;

/**
 * Class KardexService
 */
class KardexService
{
    /**
     * Registrar una Salida en el Kardex
     */
    public function registrarSalida(
        Presentacion $presentacion,
        Lote $lote,
        float $cantidadTomada,
        Model $origenModel,
        ?string $numeroDocumento = null,
        ?string $concepto = null
    ): Kardex {

        $producto = $presentacion->producto;

        // Verificamos si el producto es granel o unidad fija
        $factorConversion = ($producto->tipo_producto === 'GRANEL') ? (float) ($presentacion->factor_conversion ?? 1.0000) : 1.0000;

        // Convertir la cantidad que va salir y la multiplicamos por el valor del factor de conversión para obtener el mejor resultado
        $cantidadSalidaBase = $cantidadTomada * $factorConversion;

        // Luego buscamos La  Saldo Registrado en el Kardex(La cantidad de producto y su monto anterior)
        $ultimoSaldoRegistradoKardex = Kardex::where('producto_id', $producto->id)
            ->when($producto->tipo_producto === 'UNIDAD FIJA', function ($query) use ($presentacion) {
                return $query->where('presentacion_id', $presentacion->id);
            })
            ->latest('id')
            ->lockForUpdate()
            ->first();

        // Verificamos gracias a la funcion anterior si habia algun SaldoRegistrado para el la cantidad de producto y si no se toma como primer transaccion
        $saldoCantidadAnterior = $ultimoSaldoRegistradoKardex ? (float) $ultimoSaldoRegistradoKardex->cantidad_saldo : 0.0000;

        // Verificamos gracias a la funcion anterior si habia algun SaldoRegistrado para el monto en dinero y si no se toma como primer transaccion
        $saldoMontoAnterior = $ultimoSaldoRegistradoKardex ? (float) $ultimoSaldoRegistradoKardex->monto_saldo : 0.00;

        // Traemos el costo_unitario  que se calcula previamente en la compra
        $costoUnitarioLote = (float) $lote->costo_unitario_compra;

        // Verificamos cuando valor en dinero  sale
        $montoSaliente = $cantidadSalidaBase * $costoUnitarioLote;

        // Actualizamos la cantidad de  producto que quedara
        $nuevoSaldoCantidad = $saldoCantidadAnterior - $cantidadSalidaBase;

        // Actualizamos la cantidad en dinero que quedara
        $nuevoSaldoMonto = $saldoMontoAnterior - $montoSaliente;

        // Calculamos el Costo promedio Ponderado.
        $cpp = $nuevoSaldoCantidad > 0 ? ($nuevoSaldoMonto / $nuevoSaldoCantidad) : 0.0000;

        return Kardex::create([
            'producto_id' => $producto->id,
            'presentacion_id' => $presentacion->id,
            'lote_id' => $lote->id,
            'usuario_id' => auth()->id() ?? $origen->usuario_id ?? $origen->vendido_por ?? 1,
            'tipo_movimiento' => 'SALIDA_VENTA',
            'origen_id' => $origenModel->id,
            'origen_type' => get_class($origenModel),
            'numero_documento' => $numeroDocumento,
            'concepto' => $concepto ?? 'Salida por Venta',
            'factor_conversion' => $factorConversion,
            'cantidad_entrada' => 0.0000,
            'cantidad_salida' => $cantidadSalidaBase,
            'cantidad_saldo' => $nuevoSaldoCantidad,
            'costo_unitario' => $costoUnitarioLote,
            'costo_promedio_ponderado' => $cpp,
            'monto_entrante' => 0.00,
            'monto_saliente' => $montoSaliente,
            'monto_saldo' => $nuevoSaldoMonto,
        ]);
    }

    /**
     * Registrar un entrada en el KARDEX(Recepción de Compra)
     */
    public function registrarEntrada(
        Presentacion $presentacion,
        Lote $lote,
        float $cantidadFisica,
        Model $origenModel,
        ?string $numeroDocumento = null,
        ?string $concepto = null
    ) {
        $producto = $presentacion->producto;

        // Verifcamos si es Granel o UNIDAD FIJA
        $factorConversion = ($producto->tipo_producto === 'GRANEL')
          ? (float) ($presentacion->factor_conversion ?? 1.0000) : 1.0000;

        // Convertimos a cuanto va entrar basando en la unidad de medida base
        $cantidadEntradaBase = $cantidadFisica * $factorConversion;

        $ultimoRegistroKardex = Kardex::where('producto_id', $producto->id)
            ->when($producto->tipo_producto === 'UNIDAD FIJA', function ($query) use ($presentacion) {
                return $query->where('presentacion_id', $presentacion->id);
            })->latest('id')
            ->lockForUpdate()
            ->first();

        $saldoCantidadAnterior = $ultimoRegistroKardex ? (float) ($ultimoRegistroKardex->cantidad_saldo) : 0.0000;
        $saldoMontoAnterior = $ultimoRegistroKardex ? (float) ($ultimoRegistroKardex->monto_saldo) : 0.00;

        $costoUnitarioReal = $lote->costo_unitario_compra;
        $montoEntrante = $cantidadEntradaBase * $costoUnitarioReal;

        $nuevoSaldoCantidad = $saldoCantidadAnterior + $cantidadEntradaBase;
        $nuevoSaldoMonto = $saldoMontoAnterior + $montoEntrante;

        // Calculamos el Costo promedio Ponderado.
        $cpp = $nuevoSaldoCantidad > 0 ? ($nuevoSaldoMonto / $nuevoSaldoCantidad) : 0.0000;

        return Kardex::create([
            'producto_id' => $producto->id,
            'presentacion_id' => $presentacion->id,
            'lote_id' => $lote->id,
            'usuario_id' => auth()->id() ?? $origen->usuario_id ?? $origen->vendido_por ?? 1,
            'tipo_movimiento' => 'ENTRADA_COMPRA',
            'origen_id' => $origenModel->id,
            'origen_type' => get_class($origenModel),
            'numero_documento' => $numeroDocumento,
            'concepto' => $concepto ?? 'Entrada por Compra',
            'factor_conversion' => $factorConversion,
            'cantidad_entrada' => $cantidadEntradaBase,
            'cantidad_salida' => 0.0000,
            'cantidad_saldo' => $nuevoSaldoCantidad,
            'costo_unitario' => $costoUnitarioReal,
            'costo_promedio_ponderado' => $cpp,
            'monto_entrante' => $montoEntrante,
            'monto_saliente' => 0.00,
            'monto_saldo' => $nuevoSaldoMonto,
        ]);
    }

    public function registrarAnulacionCompra(
        Presentacion $presentacion,
        Lote $lote,
        float $cantidadFisicaAnulada,
        Model $origenModel,
        ?string $numeroDocumento = null,
        ?string $concepto = null

    ): Kardex {

        $producto = $presentacion->producto;

        $factorConversion = ($producto->tipo_producto === 'GRANEL') ? (float) ($presentacion->factor_conversion ?? 1.0000) : 1.0000;

        $cantidadSalidaBase = $cantidadFisicaAnulada * $factorConversion;

        $ultimoRegistroKardex = Kardex::where('producto_id', $producto->id)
            ->when($producto->tipo_producto === 'UNIDAD FIJA', function ($query) use ($presentacion) {
                return $query->where('presentacion_id', $presentacion->id);
            })
            ->latest('id')
            ->lockForUpdate()
            ->first();

        $saldoCantidadAnterior = $ultimoRegistroKardex ? (float) $ultimoRegistroKardex->cantidad_saldo : 0.0000;
        $saldoMontoAnterior = $ultimoRegistroKardex ? (float) $ultimoRegistroKardex->monto_saldo : 0.00;

        $costoUnitarioReal = (float) $lote->costo_unitario_compra;

        $costoAplicado = $ultimoRegistroKardex && $ultimoRegistroKardex->costo_promedio_ponderado > 0
            ? (float) $ultimoRegistroKardex->costo_promedio_ponderado
            : (float) $lote->costo_unitario_compra;

        $montoSaliente = $cantidadSalidaBase * $costoAplicado;

        $nuevoSaldoCantidad = $saldoCantidadAnterior - $cantidadSalidaBase;
        $nuevoSaldoMonto = $saldoMontoAnterior - $montoSaliente;

        $cpp = $nuevoSaldoCantidad > 0 ? ($nuevoSaldoMonto / $nuevoSaldoCantidad) : 0.0000;

        return Kardex::create([
            'producto_id' => $producto->id,
            'presentacion_id' => $presentacion->id,
            'lote_id' => $lote->id,
            'usuario_id' => auth()->id() ?? $origen->usuario_id ?? $origen->vendido_por ?? 1,
            'tipo_movimiento' => 'ANULACION_COMPRA',
            'origen_id' => $origenModel->id,
            'origen_type' => get_class($origenModel),
            'numero_documento' => $numeroDocumento,
            'concepto' => $concepto ?? 'Reversión por Anulación de Compra',
            'factor_conversion' => $factorConversion,
            'cantidad_entrada' => 0.0000,
            'cantidad_salida' => $cantidadSalidaBase,
            'cantidad_saldo' => $nuevoSaldoCantidad,
            'costo_unitario' => $costoUnitarioReal,
            'costo_promedio_ponderado' => $cpp,
            'monto_entrante' => 0.00,
            'monto_saliente' => $montoSaliente,
            'monto_saldo' => $nuevoSaldoMonto,
        ]);
    }

}
