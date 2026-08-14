<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/cadastro.php', [AuthController::class, 'register']);
Route::post('/login.php', [AuthController::class, 'login']);

Route::middleware('jwt')->group(function (): void {
    Route::get('/perfil.php', [AuthController::class, 'profile']);
    Route::get('/usuarios.php', [AuthController::class, 'users']);
});
