<?php

use App\Http\Controllers\TaskController;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/v1/register', [UserController::class, 'register']);
Route::post('/v1/login', [UserController::class, 'login']);

Route::middleware('jwt.verify')->group(function () {
    Route::get('/v1/users', [UserController::class, 'index']);
    Route::get('/v1/me', [UserController::class, 'getUser']);
    Route::post('/v1/logout', [UserController::class, 'logout']);

    Route::get('/v1/tasks/summary', [TaskController::class, 'summary']);
    Route::get('/v1/user/tasks/summary', [TaskController::class, 'user_summary']);
    Route::get('/v1/user/tasks', [TaskController::class, 'userTasks']);

    Route::get('/v1/tasks', [TaskController::class, 'index']);
    Route::get('/v1/task/{id}', [TaskController::class, 'show']);
    Route::post('/v1/tasks', [TaskController::class, 'store']);
    Route::put('/v1/task/{id}', [TaskController::class, 'update']);
    Route::delete('/v1/task/{id}', [TaskController::class, 'destroy']);

    Route::put('/v1/task/{taskId}/checklist/{cid}', [ChecklistController::class, 'toggleChecklistItem']);
});
