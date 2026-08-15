<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::post('/cadastro.php', [AuthController::class, 'register']);
Route::post('/login.php', [AuthController::class, 'login']);

Route::middleware('jwt')->group(function (): void {
    Route::get('/perfil.php', [AuthController::class, 'profile']);
    Route::get('/usuarios.php', [AuthController::class, 'users']);

    Route::prefix('dashboard')->group(function (): void {
        Route::get('/resumo.php', [DashboardController::class, 'summary']);
        Route::get('/projetos-ativos.php', [DashboardController::class, 'activeProjects']);
        Route::get('/tempo-por-projeto.php', [DashboardController::class, 'timeByProject']);
        Route::get('/atividades.php', [DashboardController::class, 'activities']);
        Route::get('/roadmap-ia.php', [DashboardController::class, 'aiRoadmap']);
        Route::get('/resumo-ia.php', [DashboardController::class, 'aiSummary']);
        Route::get('/timer-atual.php', [DashboardController::class, 'currentTimer']);
    });
});
