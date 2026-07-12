<?php

use App\Http\Controllers\Api\PerfilesController;
use App\Http\Controllers\Api\PermisosController;
use App\Http\Controllers\Api\ProductosController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| Productos
|--------------------------------------------------------------------------
*/
Route::get('/GetProductos', [ProductosController::class, 'GetProductos']);
Route::get('/GetProducto/{id}', [ProductosController::class, 'GetProducto']);
Route::post('/InsertProduct', [ProductosController::class, 'InsertProduct']);
Route::put('/UpdateProduct/{id}', [ProductosController::class, 'UpdateProduct']);
Route::delete('/DeleteProduct/{id}', [ProductosController::class, 'DeleteProduct']);

/*
|--------------------------------------------------------------------------
| Permisos
|--------------------------------------------------------------------------
*/
Route::get('/GetPermisos', [PermisosController::class, 'GetPermisos']);
Route::get('/GetPermiso/{id}', [PermisosController::class, 'GetPermiso']);
Route::post('/InsertPermiso', [PermisosController::class, 'InsertPermiso']);
Route::put('/UpdatePermiso/{id}', [PermisosController::class, 'UpdatePermiso']);
Route::delete('/DeletePermiso/{id}', [PermisosController::class, 'DeletePermiso']);


/*
|--------------------------------------------------------------------------
| Perfiles
|--------------------------------------------------------------------------
*/
Route::get('/GetPerfiles', [PerfilesController::class, 'GetPerfiles']);
Route::get('/GetPerfil/{id}', [PerfilesController::class, 'GetPerfil']);
Route::post('/InsertPerfil', [PerfilesController::class, 'InsertPerfil']);
Route::put('/UpdatePerfil/{id}', [PerfilesController::class, 'UpdatePerfil']);
Route::delete('/DeletePerfil/{id}', [PerfilesController::class, 'DeletePerfil']);