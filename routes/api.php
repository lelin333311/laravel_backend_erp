<?php

use App\Http\Controllers\PartyController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard/summary', [TransactionController::class, 'summary']);

Route::apiResource('transactions', TransactionController::class);

Route::get('/products',        [ProductController::class, 'index']);
Route::get('/products/search', [ProductController::class, 'search']);

Route::prefix('v1')->group(function () {
    Route::get('/parties', [PartyController::class, 'index']);
    Route::post('/parties/sync', [PartyController::class, 'sync']);

    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products/sync', [ProductController::class, 'sync']);

    Route::get('/sales', [SaleController::class, 'index']);
    Route::post('/sales', [SaleController::class, 'store']);
    Route::post('/sales/sync', [SaleController::class, 'sync']);
    Route::get('/sales/{sale}', [SaleController::class, 'show']);
    Route::post('/sales/{sale}/payments', [SaleController::class, 'addPayment']);

    Route::get('/test', function () {
        return response()->json([
            'success' => true,
            'message' => 'Flutter successfully connected with Laravel!',
            'backend' => 'Laravel',
            'status' => 'Connected',
            'version' => '1.0',
        ]);
    });
});