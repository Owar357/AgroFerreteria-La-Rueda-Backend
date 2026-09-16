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
    public function __invoke(StoreAjusteRequest $request, KardexService $kardexService)
    {
        try {
            DB::beginTransaction();

            $numeroAjuste = 'AJU-'.now()->format('YmdHis');

            $ajuste = AjusteInventario::create([
                'numero_ajuste' => $numeroAjuste,
                'tipo_ajuste' => $request->tipo_ajuste,
                'motivo' => $request->motivo,
                'observaciones' => $request->observaciones,
                'usuario_id' => auth()->id() ?? 1,
            ]);

            foreach ($request->detalles as $item) {
                $lote = Lote::lockForUpdate()->findOrFail($item['lote_id']);

                $cantidadSistema = (float) $lote->cantidad_actual;
                $costoAnterior = (float) $lote->costo_unitario_compra;

                if ($request->tipo_ajuste === 'REEVALUACION') {

                    $costoNuevo = (float) $item['costo_nuevo'];
                    $cantidadFisica = $cantidadSistema;
                    $diferencia = 0.0000;
                    $montoImpacto = ($costoNuevo - $costoAnterior) * $cantidadSistema;

                    $lote->costo_unitario_compra = $costoNuevo;
                    $lote->save();

                    $kardexService->registrarReevaluacion(
                        $lote,
                        $costoAnterior,
                        $costoNuevo,
                        $ajuste,
                        $numeroAjuste,
                        $request->motivo
                    );

                } else {

                    $cantidadFisica = (float) $item['cantidad_fisica'];
                    $diferencia = $cantidadFisica - $cantidadSistema;
                    $costoNuevo = $costoAnterior;
                    $montoImpacto = $diferencia * $costoAnterior;
                    $cantidadAjuste = abs($diferencia);

                    if ($request->tipo_ajuste === 'INCREMENTO') {

                        if ($diferencia <= 0) {
                            throw new Exception("En un INCREMENTO la cantidad física debe ser mayor a la del sistema (lote {$lote->lote_interno}).");
                        }

                        $lote->cantidad_actual = bcadd($lote->cantidad_actual, (string) $cantidadAjuste, 4);
                        if ($lote->estado === 'AGOTADO') {
                            $lote->estado = 'ACTIVO';
                        }
                        $lote->save();

                        $kardexService->registrarAjusteEntrada(
                            $lote, $cantidadAjuste, $ajuste, $numeroAjuste, $request->motivo
                        );

                    } else {

                        if ($diferencia >= 0) {
                            throw new Exception("En una DISMINUCION la cantidad física debe ser menor a la del sistema (lote {$lote->lote_interno}).");
                        }

                        if (bccomp((string) $cantidadAjuste, (string) $lote->cantidad_actual, 4) === 1) {
                            throw new Exception("La cantidad a descontar sobrepasa el stock disponible en el lote {$lote->lote_interno}.");
                        }

                        $lote->cantidad_actual = bcsub($lote->cantidad_actual, (string) $cantidadAjuste, 4);
                        if (bccomp($lote->cantidad_actual, '0', 4) === 0) {
                            $lote->estado = 'AGOTADO';
                        }
                        $lote->save();

                        $kardexService->registrarAjusteSalida(
                            $lote, $cantidadAjuste, $ajuste, $numeroAjuste, $request->motivo
                        );
                    }
                }

                DetalleAjusteInventario::create([
                    'ajuste_inventario_id' => $ajuste->id,
                    'lote_id' => $lote->id,
                    'cantidad_sistema' => $cantidadSistema,
                    'cantidad_fisica' => $cantidadFisica,
                    'diferencia' => $diferencia,
                    'costo_anterior' => $costoAnterior,
                    'costo_nuevo' => $costoNuevo,
                    'monto_impacto' => $montoImpacto,
                ]);
            }

            $ajuste->load('detalles.lote');

            DB::commit();

            return response()->json([
                'status' => 'ok',
                'message' => 'Ajuste de inventario procesado correctamente.',
                'data' => $ajuste,
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Error al procesar el ajuste de inventario.' . $e->getMessage(),
            ], 500);
        }
    }
}
