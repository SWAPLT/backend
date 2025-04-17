<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MensajeController;
use App\Http\Controllers\VehiculoImagenController;
use App\Http\Controllers\VehiculoReporteController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehiculoController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\FavoritoController;

//----------------------------------------   AUTH   ---------------------------------------------//
Route::post('register', [AuthController::class, 'register']);
Route::get('/verify-email/{verification_code}', [AuthController::class, 'verifyEmail'])->name('verification.verify');
Route::post('/verify-email/{verification_code}', [AuthController::class, 'verifyEmail']); // Soporte para verificación desde la app móvil
Route::post('login', [AuthController::class, 'login']);
Route::middleware('auth:api')->get('/me', [AuthController::class, 'me']);
Route::middleware('auth:api')->post('/logout', [AuthController::class, 'logout']);
Route::post('refresh', [AuthController::class, 'refreshToken']);
Route::post('password/reset-request', [AuthController::class, 'requestPasswordReset']); // Ruta para solicitar el restablecimiento de contraseña
Route::post('password/reset/{token}', [AuthController::class, 'resetPassword']); // Ruta para restablecer la contraseña
Route::put('profile', [AuthController::class, 'updateProfile'])->middleware('auth:api'); // Ruta para actualizar perfil

//----------------------------------------   USERS   ---------------------------------------------//
Route::get('/users', [UserController::class, 'index']); // Mostrar todos los usuarios
Route::get('/users/{id}', [UserController::class, 'show']); // Mostrar un usuario específico
Route::post('/users', [UserController::class, 'store']); // Crear un nuevo usuario
Route::put('/users/{id}', [UserController::class, 'update']); // Actualizar un usuario
Route::delete('/users/{id}', [UserController::class, 'destroy']); // Eliminar un usuario

//----------------------------------------   VEHICULOS   -----------------------------------------//
Route::get('vehiculos', [VehiculoController::class, 'index']);
Route::get('vehiculos/{id}', [VehiculoController::class, 'show']);
Route::post('vehiculos', [VehiculoController::class, 'store']);
Route::middleware('auth:api')->put('/vehiculos/{id}', [VehiculoController::class, 'update']);
Route::delete('vehiculos/{id}', [VehiculoController::class, 'destroy']);
Route::middleware('auth:api')->get('user/vehiculos', [VehiculoController::class, 'getUserVehicles']);

//-------------------------------------   CATEGORIAS   -------------------------------------------//
Route::get('categorias', [CategoriaController::class, 'index']);
Route::get('categorias/{id}', [CategoriaController::class, 'show']);
Route::post('categorias', [CategoriaController::class, 'store']);
Route::put('categorias/{id}', [CategoriaController::class, 'update']);
Route::delete('categorias/{id}', [CategoriaController::class, 'destroy']);


//-------------------------------------   FAVORITOS   -------------------------------------------//
Route::middleware(['auth:api'])->group(function () {
    Route::post('favoritos', [FavoritoController::class, 'store']); // Añadir a favoritos
    Route::get('favoritos', [FavoritoController::class, 'index']);  // Obtener favoritos del usuario autenticado
    Route::delete('favoritos/{vehiculo_id}', [FavoritoController::class, 'destroy']); // Eliminar favorito
});


//---------------------------------   REPORTE VEHICULOS   --------------------------------------//
Route::get('vehiculos/reporte/{id}', [VehiculoReporteController::class, 'generarReporte']);

//-------------------------------------   VEHICULO IMAGEN   -------------------------------------------//
Route::post('vehiculos/{vehiculoId}/imagenes', [VehiculoImagenController::class, 'store']);
Route::get('vehiculos/{vehiculoId}/imagenes', [VehiculoImagenController::class, 'show']);
Route::get('vehiculos/{vehiculoId}/primera-imagen', [VehiculoImagenController::class, 'primeraImagen']);
Route::get('vehiculos/imagenes/{id}', [VehiculoImagenController::class, 'mostrarImagen'])->name('vehiculo.imagen');
Route::delete('vehiculos/{vehiculoId}/imagenes/{imagenId}', [VehiculoImagenController::class, 'destroy']);
Route::get('vehiculos/{id}/todas-imagenes', [VehiculoImagenController::class, 'mostrarImagenesVehiculo'])->name('vehiculo.todas.imagenes');


//-------------------------------------   MENSAJES   -------------------------------------------//
Route::post('/mensajes', [MensajeController::class, 'store']);
Route::get('/mensajes/{emisor_id}/{receptor_id}', [MensajeController::class, 'index']);
Route::patch('/mensajes/{id}/leido', [MensajeController::class, 'marcarLeido']);
Route::delete('/mensajes/{id}', [MensajeController::class, 'destroy']);
