<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SensorController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MedicionGeneralController;
use App\Http\Controllers\ParcelaController;
use App\Http\Controllers\MedicionParcelaController;
use App\Http\Controllers\DataConsumerController;


Route::get('/ping', function () {
    return response()->json(['message' => 'pong']);
});

// Rutas de autenticación con JWT
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api');
Route::get('/user', [AuthController::class, 'obtenerUsuario'])->middleware('auth:api');

// Rutas de sensores (CRUD)
Route::apiResource('sensores', SensorController::class);

// Rutas de mediciones generales (CRUD)
Route::apiResource('mediciones/generales', MedicionGeneralController::class);

// Rutas de parcelas (CRUD)
Route::apiResource('parcelas', ParcelaController::class);

// Rutas de mediciones de parcelas (CRUD)
Route::apiResource('mediciones/parcelas', MedicionParcelaController::class);

// Ruta para consumir la API externa y almacenar datos
Route::get('/consumir-datos', [DataConsumerController::class, 'consumirYAlmacenarDatos']);

// Rutas adicionales para obtener las últimas mediciones
Route::get('/mediciones/ultimas-general', [MedicionGeneralController::class, 'ultimasMediciones']);
Route::get('/mediciones/ultimas-parcela', [MedicionParcelaController::class, 'ultimasMedicionesParcelas']);

Route::get('/test', function() {
    return response()->json(['message' => 'Ruta de prueba funciona']);
});

