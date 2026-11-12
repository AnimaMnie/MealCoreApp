<?php

use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Models\Product;
use Illuminate\Support\Facades\Route;

// --- Publiczne: rejestracja i logowanie (limit 10 prób na minutę) -----
Route::prefix('auth')->middleware('throttle:10,1')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// --- Wymagają tokena Bearer (authentication) -------------------------
Route::middleware('auth:sanctum')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);

    // Produkty: odczyt dla każdego zalogowanego, zapis tylko admin (ProductPolicy).
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{product}', [ProductController::class, 'show']);
    Route::post('products', [ProductController::class, 'store'])->can('create', Product::class);
    Route::match(['put', 'patch'], 'products/{product}', [ProductController::class, 'update'])->can('update', 'product');
    Route::delete('products/{product}', [ProductController::class, 'destroy'])->can('delete', 'product');

    // Panel admina (authorization przez middleware "admin").
    Route::prefix('admin')->middleware('admin')->group(function () {
        Route::get('users', [UserController::class, 'index']);
        Route::patch('users/{user}/role', [UserController::class, 'updateRole']);
        Route::delete('users/{user}', [UserController::class, 'destroy']);
    });
});
