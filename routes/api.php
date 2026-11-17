<?php

use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\MealController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ShoppingItemController;
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

    // Dziennik posiłków (każdy widzi i zmienia tylko swoje - MealPolicy).
    Route::get('meals/summary', [MealController::class, 'summary']);
    Route::get('meals/history', [MealController::class, 'history']);
    Route::get('meals', [MealController::class, 'index']);
    Route::post('meals', [MealController::class, 'store']);
    Route::get('meals/{meal}', [MealController::class, 'show'])->can('view', 'meal');
    Route::match(['put', 'patch'], 'meals/{meal}', [MealController::class, 'update'])->can('update', 'meal');
    Route::delete('meals/{meal}', [MealController::class, 'destroy'])->can('delete', 'meal');

    // Grupy (GroupPolicy: widzą członkowie, zarządza właściciel).
    Route::get('groups', [GroupController::class, 'index']);
    Route::post('groups', [GroupController::class, 'store']);
    Route::get('groups/{group}', [GroupController::class, 'show'])->can('view', 'group');
    Route::delete('groups/{group}', [GroupController::class, 'destroy'])->can('delete', 'group');
    Route::post('groups/{group}/members', [GroupController::class, 'addMember'])->can('manageMembers', 'group');
    Route::delete('groups/{group}/members/{user}', [GroupController::class, 'removeMember'])->can('manageMembers', 'group');

    // Lista zakupów grupy - tylko dla członków grupy.
    Route::get('groups/{group}/shopping-items', [ShoppingItemController::class, 'index'])->can('view', 'group');
    Route::post('groups/{group}/shopping-items', [ShoppingItemController::class, 'store'])->can('view', 'group');
    Route::patch('shopping-items/{shoppingItem}', [ShoppingItemController::class, 'update'])->can('update', 'shoppingItem');
    Route::delete('shopping-items/{shoppingItem}', [ShoppingItemController::class, 'destroy'])->can('delete', 'shoppingItem');

    // Panel admina (authorization przez middleware "admin").
    Route::prefix('admin')->middleware('admin')->group(function () {
        Route::get('users', [UserController::class, 'index']);
        Route::patch('users/{user}/role', [UserController::class, 'updateRole']);
        Route::delete('users/{user}', [UserController::class, 'destroy']);
    });
});
