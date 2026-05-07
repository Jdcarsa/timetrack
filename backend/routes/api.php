<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TimeRecordController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ScheduleController;

/*
|--------------------------------------------------------------------------
| Rutas públicas (sin autenticación)
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Rutas protegidas (requieren token Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // Empleado: registrar entrada/salida e historial
    Route::post('/clock-in',   [TimeRecordController::class, 'clockIn']);
    Route::post('/clock-out',  [TimeRecordController::class, 'clockOut']);
    Route::get('/records',     [TimeRecordController::class, 'myRecords']);
    Route::get('/status',      [TimeRecordController::class, 'status']);

    Route::get('/tasks',                    [TaskController::class, 'index']);
    Route::post('/tasks',                   [TaskController::class, 'store']);
    Route::put('/tasks/{task}',             [TaskController::class, 'update']);
    Route::patch('/tasks/{task}/status',    [TaskController::class, 'updateStatus']);
    Route::delete('/tasks/{task}',          [TaskController::class, 'destroy']);

    Route::get('/projects',          [ProjectController::class, 'index']);
    Route::get('/projects/{project}', [ProjectController::class, 'show']);

    Route::middleware('is_admin')->group(function () {
        Route::post('/projects',             [ProjectController::class, 'store']);
        Route::put('/projects/{project}',    [ProjectController::class, 'update']);
        Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);
    });

    Route::prefix('projects/{project}/schedules')->group(function () {
        Route::get('/',                                [ScheduleController::class, 'index']);
        Route::post('/',                               [ScheduleController::class, 'store']);
        Route::put('/{schedule}',                      [ScheduleController::class, 'update']);
        Route::delete('/{schedule}',                   [ScheduleController::class, 'destroy']);
        Route::post('/{schedule}/tasks',               [ScheduleController::class, 'addTask']);
        Route::delete('/{schedule}/tasks/{task}',      [ScheduleController::class, 'removeTask']);
        Route::get('/{schedule}/available-tasks',      [ScheduleController::class, 'availableTasks']);
    });

    /*
    |----------------------------------------------------------------------
    | Rutas solo para Admin
    |----------------------------------------------------------------------
    */
    Route::middleware('is_admin')->prefix('admin')->group(function () {
        Route::get('/employees',                              [AdminController::class, 'employees']);
        Route::put('/employees/{user}/hourly-rate',           [AdminController::class, 'updateHourlyRate']);
        Route::post('/employees',                             [AdminController::class, 'store']);
        Route::get('/records',                                [AdminController::class, 'allRecords']);
        Route::get('/summary',                                [AdminController::class, 'summary']);
        Route::get('/export',                                 [AdminController::class, 'export']);
        Route::get('/calendar',                               [TaskController::class, 'adminCalendar']);
    });
});
