<?php

use App\Http\Controllers\Auth\ApiAuthController;
use App\Http\Controllers\Inventory\CategoryController;
use App\Http\Controllers\Inventory\ProductController;
use App\Http\Controllers\Inventory\SupplierController;
use App\Http\Controllers\Sales\SaleController;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [ApiAuthController::class, 'login']);

// Publik - baca saja, tidak perlu login
Route::apiResource('categories', CategoryController::class)
    ->only(['index', 'show']);

Route::apiResource('products', ProductController::class)
    ->only(['index', 'show']);

Route::apiResource('suppliers', SupplierController::class)
    ->only(['index', 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [ApiAuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return new UserResource($request->user());
    });

    Route::post('/sales', [SaleController::class, 'store']);

    // Admin-only - semua operasi tulis
    Route::middleware('admin')->group(function () {
        Route::apiResource('categories', CategoryController::class)
            ->except(['index', 'show']);

        Route::apiResource('products', ProductController::class)
            ->except(['index', 'show']);

        Route::apiResource('suppliers', SupplierController::class)
            ->except(['index', 'show']);
    });
});
