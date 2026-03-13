<?php

use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [\App\Http\Controllers\Api\V1\Auth\AuthController::class, 'register']);
    Route::post('login', [\App\Http\Controllers\Api\V1\Auth\AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [\App\Http\Controllers\Api\V1\Auth\AuthController::class, 'me']);
        Route::post('logout', [\App\Http\Controllers\Api\V1\Auth\AuthController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('workspaces', \App\Http\Controllers\Api\V1\Workspace\WorkspaceController::class)
        ->only(['index', 'store', 'show', 'update', 'destroy']);

    Route::get('workspaces/{workspace}/members', [\App\Http\Controllers\Api\V1\Workspace\WorkspaceMemberController::class, 'index']);

    Route::get('workspaces/{workspace}/projects', [\App\Http\Controllers\Api\V1\Project\ProjectController::class, 'index']);
    Route::post('workspaces/{workspace}/projects', [\App\Http\Controllers\Api\V1\Project\ProjectController::class, 'store']);
    Route::get('workspaces/{workspace}/projects/{project}', [\App\Http\Controllers\Api\V1\Project\ProjectController::class, 'show']);
    Route::patch('workspaces/{workspace}/projects/{project}', [\App\Http\Controllers\Api\V1\Project\ProjectController::class, 'update']);
    Route::delete('workspaces/{workspace}/projects/{project}', [\App\Http\Controllers\Api\V1\Project\ProjectController::class, 'destroy']);
    Route::post('workspaces/{workspace}/projects/{project}/archive', [\App\Http\Controllers\Api\V1\Project\ProjectController::class, 'archive']);
    Route::post('workspaces/{workspace}/projects/{project}/unarchive', [\App\Http\Controllers\Api\V1\Project\ProjectController::class, 'unarchive']);

    Route::get('workspaces/{workspace}/projects/{project}/tasks', [\App\Http\Controllers\Api\V1\Task\TaskController::class, 'index']);
    Route::post('workspaces/{workspace}/projects/{project}/tasks', [\App\Http\Controllers\Api\V1\Task\TaskController::class, 'store']);
    Route::get('workspaces/{workspace}/tasks/{task}', [\App\Http\Controllers\Api\V1\Task\TaskController::class, 'show']);
    Route::patch('workspaces/{workspace}/tasks/{task}', [\App\Http\Controllers\Api\V1\Task\TaskController::class, 'update']);
    Route::delete('workspaces/{workspace}/tasks/{task}', [\App\Http\Controllers\Api\V1\Task\TaskController::class, 'destroy']);
});
