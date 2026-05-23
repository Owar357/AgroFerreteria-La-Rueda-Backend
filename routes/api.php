<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CodigoBarraController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('auth')->group(function(){
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function(){
        Route::get('me',[AuthController::class, 'me']);
        Route::post('logout',[AuthController::class, 'logout']);
        Route::post('refresh',[AuthController::class, 'refresh']);
    });
});


//Cracion de rutas para law apiex

Route::middleware('auth:api')->group(function(){
    Route::apiResource('compras', CompraController::class);
    Route::apiResource('clientes',ClienteController::class);
    Route::apiResource('categorias', CategoriaController::class);
    Route::apiResource('producto', ProductoController::class);
    Route::apiResource('user', UserController::class);
    Route::apiResource('CodigoBarra', CodigoBarraController::class);
});


