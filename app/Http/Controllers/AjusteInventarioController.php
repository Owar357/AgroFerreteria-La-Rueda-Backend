<?php

namespace App\Http\Controllers;

use App\Http\Requests\Inventario\StoreAjusteRequest;
use App\Models\AjusteInventario;
use App\Models\DetalleAjusteInventario;
use App\Models\Lote;
use App\Services\KardexService;
use Exception;
use Illuminate\Support\Facades\DB;

class AjusteInventarioController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(StoreAjusteRequest $request, KardexService $kardexService)
    {
        try {

            DB::beginTransaction();

            $numeroAjuste = 'AJU-' . now()->format('YmdHis');

            $ajuste = AjusteInventario::create([
                'numero_ajuste' => $numeroAjuste,
                'tipo_ajuste'   => $request->tipo_ajuste,
                'motivo'        => $request->motivo,
                'observaciones' => $request->observaciones,
                'usuario_id'    => auth()->id() ?? 1,
            ]);

            foreach ($request->detalles as $item) {
                $lote = Lote::with('presentacion')->lockForUpdate()->findOrFail($item['lote_id']);
                $presentacion = $lote->presentacion;

                $cantidadSistema = (float) $lote->cantidad_actual;
                $costoAnterior   = (float) $lote->costo_unitario_compra;

                if ($request->tipo_ajuste === 'REEVALUACION') {

                    // --- CASO 1: REEVALUACION DE COSTO ---
                    $costoNuevo = (float) $item['costo_nuevo'];
                    $cantidadFisica = $cantidadSistema;
                    $diferencia = 0.0000;
                    $montoImpacto = ($costoNuevo - $costoAnterior) * $cantidadSistema;

                    // Actualizar el costo del lote
                    $lote->costo_unitario_compra = $costoNuevo;
                    $lote->save();

                    // Registrar en Kardex
                    $kardexService->registrarReevaluacion(
                        $presentacion,
                        $lote,
                        $costoNuevo,
                        $ajuste,
                        $numeroAjuste,
                        $request->motivo
                    );

                } else {

                    // --- CASO 2: INCREMENTO O DISMINUCION FISICA ---
                    $cantidadFisica = (float) $item['cantidad_fisica'];
                    $diferencia = $cantidadFisica - $cantidadSistema;
                    $costoNuevo = $costoAnterior;
                    $montoImpacto = $diferencia * $costoAnterior;

                    if ($request->tipo_ajuste === 'INCREMENTO') {
                        $cantidadAjuste = abs($diferencia);
                        $lote->cantidad_actual = bcadd($lote->cantidad_actual, $cantidadAjuste, 4);
                        if ($lote->cantidad_actual > 0 && $lote->estado === 'AGOTADO') {
                            $lote->estado = 'ACTIVO';
                        }
                        $lote->save();

                        $kardexService->registrarAjusteEntrada(
                            $presentacion,
                            $lote,
                            $cantidadAjuste,
                            $ajuste,
                            $numeroAjuste,
                            $request->motivo
                        );

                    } else {

                        // DISMINUCION
                        $cantidadAjuste = abs($diferencia);

                        if ($cantidadAjuste > $lote->cantidad_actual) {
                            throw new Exception("La cantidad a descontar sobrepasa el stock disponible en el lote {$lote->lote_interno}.");
                        }

                        $lote->cantidad_actual = bcsub($lote->cantidad_actual, $cantidadAjuste, 4);
                        if ($lote->cantidad_actual == 0) {
                            $lote->estado = 'AGOTADO';
                        }
                        $lote->save();

                        $kardexService->registrarAjusteSalida(
                            $presentacion,
                            $lote,
                            $cantidadAjuste,
                            $ajuste,
                            $numeroAjuste,
                            $request->motivo
                        );
                    }
                }

                // Guardar línea de detalle del ajuste
                DetalleAjusteInventario::create([
                    'ajuste_inventario_id' => $ajuste->id,
                    'lote_id'              => $lote->id,
                    'cantidad_sistema'     => $cantidadSistema,
                    'cantidad_fisica'      => $cantidadFisica,
                    'diferencia'           => $diferencia,
                    'costo_anterior'       => $costoAnterior,
                    'costo_nuevo'          => $costoNuevo,
                    'monto_impacto'        => $montoImpacto,
                ]);
            }

            DB::commit();

            return response()->json([
                'status'  => 'ok',
                'message' => 'Ajuste de inventario procesado correctamente.',
                'data'    => $ajuste->load('detalles.lote'),
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => 'Error al procesar el ajuste de inventario:' 
            ], 500);
        }
    }
}