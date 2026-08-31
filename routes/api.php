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
use App\Http\Controllers\Reportes\ReporteCajaController;
use App\Http\Controllers\Reportes\ReporteComprasController;
use App\Http\Controllers\Reportes\ReporteInventarioController;
use App\Http\Controllers\Reportes\ReporteVentas\ReporteComparativoVentasController;
use App\Http\Controllers\Reportes\ReporteVentas\ReporteDesempenoVentasController;
use App\Http\Controllers\Reportes\ReporteVentas\ReporteFinancieroVentasController;
use App\Http\Controllers\Reportes\ReporteVentas\ReporteVentasController;
use App\Http\Controllers\UnidadMedidaController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VentaController;
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

    Route::patch('usuarios/{id}/desactivar', [UserController::class, 'desactivarUsuario']);
    Route::apiResource('usuarios', UserController::class);
    Route::apiResource('codigosBarra', CodigoBarraController::class);
    Route::apiResource('presentaciones', PresentacionController::class)->only(['store', 'update', 'destroy']);
    Route::patch('/proveedores/{id}/desactivar', [ProveedorController::class, 'desactivarProveedor']);
    Route::get('/proveedor/proveedores', [ProveedorController::class, 'traerNombreProveedores']);
    Route::apiResource('proveedores', ProveedorController::class);

    Route::post('/caja/apertura', [CajaController::class, 'abrirCaja']);
    Route::get('/caja/estado', [CajaController::class, 'estadoCaja']); // RUTA AGREGADA VERIFICA EL ESTADO DE LA CAJA, ME AYUDA EN EL FRONTEN
    Route::post('/caja/venta/apertura', [CajaController::class, 'abrirVenta']);
    Route::post('/caja/venta/cuadre', [CajaController::class, 'cuadrarVenta']);
    Route::patch('/caja/venta/cierre', [CajaController::class, 'cerrarVentaCaja']);
    Route::patch('/caja/movimientos/{movimiento}/anular', [MovimientoExternoCajaController::class, 'anularMovimiento']);
    Route::apiResource('caja/movimientoExterno', MovimientoExternoCajaController::class)->only(['index', 'store', 'show']);
    Route::patch('alertas/{id}/marcar-leida', [AlertasController::class, 'marcarLeida']);
    Route::apiResource('alertas', AlertasController::class)->only('index');
    Route::apiResource('/unidades', UnidadMedidaController::class)->only('index');

    Route::post('/ajuste-inventario', AjusteInventarioController::class);

});

Route::get('/kardex/{producto}', KardexController::class);

Route::get('/reportes/ventas', [ReporteVentasController::class, 'ventas']);
Route::get('/reportes/ticket/{id}', [ReporteVentasController::class, 'ticket']);

Route::get('/reportes/flujo-compras-ventas', [ReporteFinancieroVentasController::class, 'flujoComprasVentas']);
Route::get('/reportes/margen-ganancia', [ReporteFinancieroVentasController::class, 'margenGanancia']);

Route::get('/reportes/resumen-ventas', [ReporteComparativoVentasController::class, 'resumenVentas']);
Route::get('/reportes/ventas-comparativa', [ReporteComparativoVentasController::class, 'ventasComparativa']);

Route::get('/reportes/ventas-por-usuario', [ReporteDesempenoVentasController::class, 'ventasPorUsuarioPdf']);
Route::get('/reportes/ventas-por-categoria', [ReporteDesempenoVentasController::class, 'ventasPorCategoria']);
Route::get('/reportes/productos-mas-vendidos', [ReporteDesempenoVentasController::class, 'productosMasVendidosPdf']);
Route::get('/reportes/productos-menos-vendidos', [ReporteDesempenoVentasController::class, 'productosMenosVendidos']);

Route::get('/reportes/inventario-valorizado', [ReporteInventarioController::class, 'inventarioValorizado']);
Route::get('/reportes/productos-por-vencer', [ReporteInventarioController::class, 'productosPorVencer']);

Route::get('/reportes/compras-por-proveedor', [ReporteComprasController::class, 'comprasPorProveedor']);

Route::get('/reportes/arqueo-caja/{apertura_venta_id}', [ReporteCajaController::class, 'arqueoCaja']);
