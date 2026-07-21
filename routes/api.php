<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CodigoBarraController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\PresentacionController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VentaController;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\patch;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });
});

Route::middleware('auth:api')->group(function () {
    Route::patch('compras/{id}/anular',[CompraController::class, 'anularCompra']);
    Route::apiResource('compras', CompraController::class);
    Route::get('/clientes/buscar', [ClienteController::class, 'buscarPorDocumento']);
    Route::apiResource('clientes', ClienteController::class);
    Route::apiResource('ventas', VentaController::class);
    Route::apiResource('categorias', CategoriaController::class);

    Route::get('productos/buscar-venta', [ProductoController::class, 'buscarVenta']);
    Route::get('productos/buscar-producto/compra', [ProductoController::class, 'busquedaParaCompra']);
    Route::apiResource('productos', ProductoController::class);

    Route::patch('usuarios/{id}/desactivar', [UserController::class, 'desactivarUsuario']);
    Route::apiResource('usuarios', UserController::class);
    Route::apiResource('codigosBarra', CodigoBarraController::class);
    Route::apiResource('presentaciones', PresentacionController::class);
    Route::patch('/proveedores/{id}/desactivar',[ProveedorController::class,'desactivarProveedor' ]);
    Route::get('/proveedor/proveedores', [ProveedorController::class, 'traerNombreProveedores']);
    Route::apiResource('proveedores', ProveedorController::class);

    Route::post('/caja/apertura',[CajaController::class,'abrirCaja']);
});

Route::get('/reportes/ventas', [ReporteController::class, 'ventas']);
