<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\DashboardController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);

    // private api catalog
    Route::apiResource('catalogs', CatalogController::class, [
        'only' => [ 'store', 'update', 'destroy']
    ]);
    
    // private api item
    Route::apiResource('items', ItemController::class, [
        'only' => [ 'store', 'update', 'destroy']
    ]);

    // private api checkout
    Route::post('checkout/verify', [CheckoutController::class, 'verify']);
    Route::post('checkout/ship', [CheckoutController::class, 'ship']);
    Route::post('checkout/cancel', [CheckoutController::class, 'cancel']);
    
    // private api dashboard
    Route::get('dashboard/sales', [DashboardController::class, 'sales']);
    Route::get('dashboard/summary', [DashboardController::class, 'summary']);
});

// public api catalog
Route::apiResource('catalogs', CatalogController::class, [
    'only' => ['index', 'show']
]);

// public api item
Route::apiResource('items', ItemController::class, [
    'only' => ['index', 'show']
]);

// public api checkout
Route::post('checkout', [CheckoutController::class, 'store']);


Route::get('/coba', function () {
    return response()->json(['message' => 'Hello, World!']);
});