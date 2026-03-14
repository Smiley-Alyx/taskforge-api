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
    Route::patch('workspaces/{workspace}/tasks/bulk', [\App\Http\Controllers\Api\V1\Task\TaskController::class, 'bulkUpdate']);
    Route::get('workspaces/{workspace}/tasks/{task}', [\App\Http\Controllers\Api\V1\Task\TaskController::class, 'show']);
    Route::patch('workspaces/{workspace}/tasks/{task}', [\App\Http\Controllers\Api\V1\Task\TaskController::class, 'update']);
    Route::delete('workspaces/{workspace}/tasks/{task}', [\App\Http\Controllers\Api\V1\Task\TaskController::class, 'destroy']);

    Route::get('workspaces/{workspace}/tasks/{task}/comments', [\App\Http\Controllers\Api\V1\Comment\CommentController::class, 'index']);
    Route::post('workspaces/{workspace}/tasks/{task}/comments', [\App\Http\Controllers\Api\V1\Comment\CommentController::class, 'store']);
    Route::patch('workspaces/{workspace}/comments/{comment}', [\App\Http\Controllers\Api\V1\Comment\CommentController::class, 'update']);
    Route::delete('workspaces/{workspace}/comments/{comment}', [\App\Http\Controllers\Api\V1\Comment\CommentController::class, 'destroy']);

    Route::get('workspaces/{workspace}/labels', [\App\Http\Controllers\Api\V1\Label\LabelController::class, 'index']);
    Route::post('workspaces/{workspace}/labels', [\App\Http\Controllers\Api\V1\Label\LabelController::class, 'store']);
    Route::patch('workspaces/{workspace}/labels/{label}', [\App\Http\Controllers\Api\V1\Label\LabelController::class, 'update']);
    Route::delete('workspaces/{workspace}/labels/{label}', [\App\Http\Controllers\Api\V1\Label\LabelController::class, 'destroy']);

    Route::get('workspaces/{workspace}/invitations', [\App\Http\Controllers\Api\V1\Invitation\InvitationController::class, 'index']);
    Route::post('workspaces/{workspace}/invitations', [\App\Http\Controllers\Api\V1\Invitation\InvitationController::class, 'store']);
    Route::delete('workspaces/{workspace}/invitations/{invitation}', [\App\Http\Controllers\Api\V1\Invitation\InvitationController::class, 'destroy']);
    Route::post('invitations/accept', [\App\Http\Controllers\Api\V1\Invitation\InvitationController::class, 'accept']);

    Route::get('workspaces/{workspace}/activity', [\App\Http\Controllers\Api\V1\Activity\ActivityController::class, 'index']);
});
