<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;

Route::middleware('auth:api')->group(function () {
    Route::prefix('products')->group(function () {
        Route::post('/', [ProductController::class, 'addProduct']);
        Route::get('/', [ProductController::class, 'listProducts']);
        Route::put('/{id}', [ProductController::class, 'updateProduct']);
        Route::delete('/{id}', [ProductController::class, 'deleteProduct']);
    });
});