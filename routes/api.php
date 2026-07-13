<?php

use App\Http\Controllers\Api\PerfilesController;
use App\Http\Controllers\Api\PermisosController;
use App\Http\Controllers\Api\ProductosController;
use App\Http\Controllers\Api\UsuarioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::middleware('auth:api')->group(function () {
/*
|--------------------------------------------------------------------------
| Productos
|--------------------------------------------------------------------------
*/
    Route::get('/GetProductos', [ProductosController::class, 'GetProductos']);
    Route::get('/GetProducto/{id}', [ProductosController::class, 'GetProducto']);

    Route::post('/InsertProducto', [ProductosController::class, 'InsertProduct']);
    Route::put('/UpdateProducto/{id}', [ProductosController::class, 'UpdateProduct']);

    Route::delete('/DeleteProducto/{id}', [ProductosController::class, 'DeleteProduct']);
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
    /*
    |--------------------------------------------------------------------------
    | Usuarios
    |--------------------------------------------------------------------------
    */
    Route::get('/GetUsuarios', [UsuarioController::class, 'GetUsuarios']);
    Route::get('/GetUsuario/{id}', [UsuarioController::class, 'GetUsuario']);
    Route::post('/InsertUsuario', [UsuarioController::class, 'InsertUsuario']);
    Route::post('/UpdateUsuario/{id}', [UsuarioController::class, 'UpdateUsuario']);
    Route::delete('/DeleteUsuario/{id}', [UsuarioController::class, 'DeleteUsuario']);



});





/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/

Route::post('/Login', [AuthController::class, 'Login']);

Route::middleware('auth:api')->group(function () {

    Route::post('/Logout', [AuthController::class, 'Logout']);

});
Route::post('/RecuperarPassword', [AuthController::class, 'RecuperarPassword']);
Route::post('/CambiarPassword', [AuthController::class, 'CambiarPassword'])->middleware('auth:api');