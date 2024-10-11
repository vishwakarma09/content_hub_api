<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FileFolderController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

// File and Folder routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('file-folders/get-root', [FileFolderController::class, 'getRootNode']);
    Route::post('file-folders/create-node', [FileFolderController::class, 'createNode']);
    Route::get('file-folders/{node_id}/ancestors', [FileFolderController::class, 'getAncestors']);
    Route::get('file-folders/{node_id}/children', [FileFolderController::class, 'getChildren']); // immediate descendents
    Route::get('file-folders/{node_id}/descendents', [FileFolderController::class, 'getDescendents']);
    // load resource routes
    Route::apiResource('file-folders', FileFolderController::class);
});