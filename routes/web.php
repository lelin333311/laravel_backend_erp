<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLogin'])->middleware('guest')->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest')->name('login.submit');
Route::get('/register', [AuthController::class, 'showRegister'])->middleware('guest')->name('register');
Route::post('/register', [AuthController::class, 'register'])->middleware('guest')->name('register.submit');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
});

Route::view('/', 'sales.home')->name('home');

Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
Route::get('/sales/create', [SaleController::class, 'create'])->name('sales.create');
Route::post('/sales', [SaleController::class, 'store'])->name('sales.store');
Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
Route::post('/sales/{sale}/payments', [SaleController::class, 'addPayment'])->name('sales.payments.store');

Route::resource('products', ProductController::class)
    ->except(['show']);