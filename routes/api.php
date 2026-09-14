<?php

use App\Http\Controllers\AjusteInventarioController;
use App\Http\Controllers\AlertasController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CodigoBarraController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\KardexController;
use App\Http\Controllers\LoteController;
use App\Http\Controllers\MovimientoExternoCajaController;
use App\Http\Controllers\PresentacionController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\Reportes\Caja\ArqueoCajaReporteController;
use App\Http\Controllers\Reportes\Compras\ComprasPorProveedorReporteController;
use App\Http\Controllers\Reportes\Financieros\FlujoComprasVentasReporteController;
use App\Http\Controllers\Reportes\Financieros\MargenGananciaReporteController;
use App\Http\Controllers\Reportes\Inventario\InventarioValorizadoReporteController;
use App\Http\Controllers\Reportes\Inventario\ProductosPorVencerReporteController;
use App\Http\Controllers\Reportes\ReporteVentas\ReporteFinancieroVentasController;
use App\Http\Controllers\Reportes\Ventas\ProductosMasVendidosController;
use App\Http\Controllers\Reportes\Ventas\ReporteVentasController;
use App\Http\Controllers\Reportes\Ventas\ResumenVentasReportController;
use App\Http\Controllers\Reportes\Ventas\VentasComparativaReportController;
use App\Http\Controllers\Reportes\Ventas\VentasPorCategoriaController;
use App\Http\Controllers\Reportes\Ventas\VentasPorUsuarioController;
use App\Http\Controllers\UnidadMedidaController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });
});

Route::middleware('auth:api')->group(function () {
    Route::patch('compras/{id}/anular', [CompraController::class, 'anularCompra']);
    Route::apiResource('compras', CompraController::class);
    Route::get('/clientes/buscar', [ClienteController::class, 'buscarPorDocumento']);
    Route::apiResource('clientes', ClienteController::class);
    Route::apiResource('ventas', VentaController::class);
    Route::apiResource('categorias', CategoriaController::class);

    Route::patch('/lotes/{id}/descuento', [LoteController::class, 'actualizarDescuento']);
    Route::apiResource('lotes', LoteController::class)->only('index');

    Route::get('productos/buscar-venta', [ProductoController::class, 'buscarVenta']);
    Route::get('productos/buscar-producto/compra', [ProductoController::class, 'busquedaParaCompra']);
    Route::apiResource('productos', ProductoController::class);

    Route::get('usuarios/cajeros', [UserController::class, 'ListarUsuariosRolCajero']);
    Route::patch('usuarios/{id}/desactivar', [UserController::class, 'desactivarUsuario']);
    Route::apiResource('usuarios', UserController::class);

    Route::apiResource('codigosBarra', CodigoBarraController::class);
    Route::post('/presentaciones/actualizar-precios-masivo', [PresentacionController::class, 'actualizarPreciosMasivo']);
    Route::apiResource('presentaciones', PresentacionController::class)->only(['store', 'update', 'destroy']);
    Route::patch('/proveedores/{id}/desactivar', [ProveedorController::class, 'desactivarProveedor']);
    Route::get('/proveedor/proveedores', [ProveedorController::class, 'traerNombreProveedores']);
    Route::apiResource('proveedores', ProveedorController::class);

    Route::post('/caja/apertura', [CajaController::class, 'abrirCaja']);
    Route::get('/caja/estado', [CajaController::class, 'estadoCaja']); // RUTA AGREGADA VERIFICA EL ESTADO DE LA CAJA, ME AYUDA EN EL FRONTEN
    Route::post('/caja/venta/apertura', [CajaController::class, 'abrirVenta']);
    Route::post('/caja/venta/cuadre', [CajaController::class, 'cuadrarVenta']);
    Route::get('/caja/resumen-turno', [CajaController::class, 'resumenTurno']); // RUTA AGREGADA
    Route::patch('/caja/venta/cierre', [CajaController::class, 'cerrarVentaCaja']);
    Route::patch('/caja/movimientos/{movimiento}/anular', [MovimientoExternoCajaController::class, 'anularMovimiento']);
    Route::apiResource('caja/movimientoExterno', MovimientoExternoCajaController::class)->only(['index', 'store', 'show']);
    Route::patch('alertas/{id}/marcar-leida', [AlertasController::class, 'marcarLeida']);
    Route::apiResource('alertas', AlertasController::class)->only('index');
    Route::apiResource('/unidades', UnidadMedidaController::class)->only('index');

    Route::post('/ajuste-inventario', AjusteInventarioController::class);

});

Route::get('/kardex/{producto}', KardexController::class);

Route::get('/reportes/ticket/{id}', [ReporteVentasController::class, 'ticket']);


Route::prefix('/reportes')->group(function () {

    Route::get('/inventario/valorizado', InventarioValorizadoReporteController::class);
    Route::get('/productos-por-vencer', ProductosPorVencerReporteController::class);

    Route::get('/compras/por-proveedor', ComprasPorProveedorReporteController::class);

    Route::get('/caja/arqueo', ArqueoCajaReporteController::class);

    Route::get('/ventas/resumen', ResumenVentasReportController::class);

    Route::get('/ventas/resumen/comparativa', VentasComparativaReportController::class);

    Route::get('ventas/usuarios', VentasPorUsuarioController::class);

    Route::get('ventas/categorias', VentasPorCategoriaController::class);

    Route::get('ventas/producto-mas-vendidos', ProductosMasVendidosController::class);

    Route::get('/ventas', ReporteVentasController::class);

    Route::prefix('/financieros')->group(function () {

        Route::get('/flujo', FlujoComprasVentasReporteController::class);

        Route::get('/margen', MargenGananciaReporteController::class);
    });

});
