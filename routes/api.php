<?php

use App\Http\Controllers\Api\CorpusController;
use App\Http\Controllers\Api\DialogController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        $user = $request->user()->load('roles');
        $user->setRelation('permissions', $user->getAllPermissions());
        return $user;
    });

    Route::get('/users', [UserController::class, 'index'])->middleware('can:view-users');
    Route::post('/users', [UserController::class, 'store'])->middleware('can:create-users');
    Route::put('/users/{id}', [UserController::class, 'update'])->middleware('can:edit-users');
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->middleware('can:delete-users');
    Route::get('/roles-list', [UserController::class, 'roles'])->middleware('can:view-users');

    Route::get('/roles', [RoleController::class, 'index'])->middleware('can:view-roles');
    Route::post('/roles', [RoleController::class, 'store'])->middleware('can:manage-roles');
    Route::put('/roles/{id}', [RoleController::class, 'update'])->middleware('can:manage-roles');
    Route::delete('/roles/{id}', [RoleController::class, 'destroy'])->middleware('can:manage-roles');
    Route::get('/permissions', [RoleController::class, 'permissions'])->middleware('can:manage-roles');

    Route::get('/corpora', [CorpusController::class, 'index'])->middleware('can:view-corpora');
    Route::post('/corpora', [CorpusController::class, 'store'])->middleware('can:manage-corpora');
    Route::get('/corpora/{id}', [CorpusController::class, 'show'])->middleware('can:view-corpora');
    Route::put('/corpora/{id}', [CorpusController::class, 'update'])->middleware('can:manage-corpora');
    Route::delete('/corpora/{id}', [CorpusController::class, 'destroy'])->middleware('can:manage-corpora');
    Route::get('/dialogs', [DialogController::class, 'index'])->middleware('can:view-dialogs');
    Route::get('/dialogs/{id}', [DialogController::class, 'show'])->middleware('can:view-dialogs');
    Route::post('/dialogs', [DialogController::class, 'store'])->middleware('can:manage-dialogs');
    Route::put('/dialogs/{id}', [DialogController::class, 'update'])->middleware('can:manage-dialogs');
    Route::delete('/dialogs/{id}', [DialogController::class, 'destroy'])->middleware('can:manage-dialogs');
});
