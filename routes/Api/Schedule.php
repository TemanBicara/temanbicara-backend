<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ScheduleController;


Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('schedule', [ScheduleController::class, 'getSchedule']);
    Route::get('schedule/{id}/get', [ScheduleController::class, 'getScheduleByID']);
    Route::get('available-schedule', [ScheduleController::class, 'getAvailableSchedule']);
    Route::get('available-schedule/{id}', [ScheduleController::class, 'getAvailableScheduleByID']);
    Route::get('schedule/{id}', [ScheduleController::class, 'updateScheduleStatus']);
    Route::post('schedule', [ScheduleController::class, 'createSchedule']);
});
