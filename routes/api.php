<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FileFolderController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});


Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('file-folders/get-root', [FileFolderController::class, 'getRootNode']);
    Route::apiResource('file-folders', FileFolderController::class);
});