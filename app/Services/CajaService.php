<?php

namespace App\Services;

use App\Models\AperturaVenta;
use App\Models\Configuracion;
use App\Models\MovimientoExternoCaja;
use App\Models\Venta;

class CajaService
{
    private const ESCALA = 2;

    public const DENOMINACIONES = [
    'c1'   => 0.01,
    'c5'   => 0.05,
    'c10'  => 0.10,
    'c25'  => 0.25,
    'b1'   => 1.00,
    'b5'   => 5.00,
    'b10'  => 10.00,
    'b20'  => 20.00,
    'b50'  => 50.00,
    'b100' => 100.00,
];
    
    public function fondoFijo(): string
    {
        $valor = trim((string) Configuracion::obtener(Configuracion::FONDO_FIJO_CAJA));

        if (! preg_match('/^\d+(\.\d{1,2})?$/', $valor) || bccomp($valor, '0', self::ESCALA) <= 0) {
            return Configuracion::FONDO_FIJO_DEFECTO;
        }

        return bcadd($valor, '0', self::ESCALA);
    }

    
    public function turnoActivo(bool $bloquear = false): ?AperturaVenta
    {
        $query = AperturaVenta::query()->where('estado', 'ABIERTA');

        if ($bloquear) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    
    public function resumen(AperturaVenta $turno): array
    {
        $ventas = Venta::query()
            ->where('apertura_venta_id', $turno->id)
            ->where('estado', 'PROCESADA')
            ->selectRaw("
                COALESCE(SUM(CASE WHEN tipo_pago = 'EFECTIVO' THEN total ELSE 0 END), 0) AS efectivo,
                COALESCE(SUM(CASE WHEN tipo_pago = 'TARJETA' THEN total ELSE 0 END), 0) AS tarjeta,
                COALESCE(SUM(CASE WHEN tipo_pago = 'TRANSFERENCIA' THEN total ELSE 0 END), 0) AS transferencia
            ")
            ->first();

        $movimientos = MovimientoExternoCaja::query()
            ->where('apertura_venta_id', $turno->id)
            ->where('es_anulado', false)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN tipo_movimiento = 'ENTRADA' THEN monto ELSE 0 END), 0) AS entradas,
                COALESCE(SUM(CASE WHEN tipo_movimiento = 'SALIDA' THEN monto ELSE 0 END), 0) AS salidas
            ")
            ->first();

        $inicial = $this->decimal($turno->monto_inicial);
        $ventasEfectivo = $this->decimal($ventas->efectivo);
        $ventasTarjeta = $this->decimal($ventas->tarjeta);
        $ventasTransferencia = $this->decimal($ventas->transferencia);
        $entradas = $this->decimal($movimientos->entradas);
        $salidas = $this->decimal($movimientos->salidas);

        $efectivoDisponible = bcsub(
            bcadd(bcadd($inicial, $ventasEfectivo, self::ESCALA), $entradas, self::ESCALA),
            $salidas,
            self::ESCALA
        );

        $totalVentas = bcadd(
            bcadd($ventasEfectivo, $ventasTarjeta, self::ESCALA),
            $ventasTransferencia,
            self::ESCALA
        );

        return [
            'monto_inicial' => $inicial,
            'ventas_efectivo' => $ventasEfectivo,
            'ventas_tarjeta' => $ventasTarjeta,
            'ventas_transferencia' => $ventasTransferencia,
            'total_ventas' => $totalVentas,
            'total_entradas' => $entradas,
            'total_salidas' => $salidas,
            'efectivo_disponible' => $efectivoDisponible,
        ];
    }

    public function efectivoDisponible(AperturaVenta $turno): string
    {
        return $this->resumen($turno)['efectivo_disponible'];
    }

    
    public function permiteRetirar(AperturaVenta $turno, mixed $monto): bool
    {
        return bccomp($this->efectivoDisponible($turno), $this->monto($monto), self::ESCALA) >= 0;
    }

   
    public function saldoRetirable(AperturaVenta $turno): string
    {
        $fondo = $turno->fondo_fijo_referencia !== null
            ? $this->decimal($turno->fondo_fijo_referencia)
            : $this->fondoFijo();

        $retirable = bcsub($this->efectivoDisponible($turno), $fondo, self::ESCALA);

        return bccomp($retirable, '0', self::ESCALA) > 0 ? $retirable : '0.00';
    }

    
    public function evaluarApertura(mixed $montoContado): array
    {
        $contado = $this->monto($montoContado);
        $fondo = $this->fondoFijo();
        $comparacion = bccomp($contado, $fondo, self::ESCALA);

        return [
            'fondo_fijo' => $fondo,
            'monto_contado' => $contado,
            'diferencia' => bcsub($contado, $fondo, self::ESCALA),
            'tipo' => match ($comparacion) {
                1 => 'MAYOR',
                -1 => 'MENOR',
                default => 'EXACTO',
            },
            'requiere_justificacion' => $comparacion !== 0,
        ];
    }

    
    public function calcularCierre(AperturaVenta $turno, mixed $montoContado): array
    {
        $contado = $this->monto($montoContado);
        $fondo = $turno->fondo_fijo_referencia !== null
            ? $this->decimal($turno->fondo_fijo_referencia)
            : $this->fondoFijo();

        $resumen = $this->resumen($turno);
        $esperado = $resumen['efectivo_disponible'];
        $diferencia = bcsub($contado, $esperado, self::ESCALA);

        $tipoDiferencia = match (bccomp($diferencia, '0', self::ESCALA)) {
            1 => 'SOBRANTE',
            -1 => 'FALTANTE',
            default => 'CUADRADO',
        };

        if (bccomp($contado, $fondo, self::ESCALA) >= 0) {
            $escenario = 'A';
            $retiro = bcsub($contado, $fondo, self::ESCALA);
            $fondoSiguiente = $fondo;
        } else {
            $escenario = 'B';
            $retiro = '0.00';
            $fondoSiguiente = $contado;
        }

        return [
            ...$resumen,
            'fondo_fijo' => $fondo,
            'monto_esperado' => $esperado,
            'monto_contado' => $contado,
            'diferencia' => $diferencia,
            'tipo_diferencia' => $tipoDiferencia,
            'escenario' => $escenario,
            'retiro_efectivo' => $retiro,
            'fondo_siguiente_turno' => $fondoSiguiente,
        ];
    }

     
public function normalizarDenominaciones(array $denominaciones): array
{
    $resultado = [];

    foreach (self::DENOMINACIONES as $clave => $valor) {
        $cantidad = (int) ($denominaciones[$clave] ?? 0);
        $resultado[$clave] = max(0, $cantidad);
    }

    return $resultado;
}


public function totalDenominaciones(array $desglose): string
{
    $total = '0.00';

    foreach ($desglose as $clave => $cantidad) {
        $valorDenominacion = (string) (self::DENOMINACIONES[$clave] ?? '0');
        $subtotal = bcmul($valorDenominacion, (string) $cantidad, self::ESCALA);
        $total = bcadd($total, $subtotal, self::ESCALA);
    }

    return $total;
}
    /**
     * Normaliza un monto que viene del cliente (float/string) a string con 2 decimales.
     */
    public function monto(mixed $valor): string
    {
        return number_format(round((float) $valor, self::ESCALA), self::ESCALA, '.', '');
    }

    /**
     * Normaliza un valor que viene de la base de datos (numeric) a string con 2 decimales.
     */
    private function decimal(mixed $valor): string
    {
        return bcadd((string) ($valor ?? '0'), '0', self::ESCALA);
    }
}