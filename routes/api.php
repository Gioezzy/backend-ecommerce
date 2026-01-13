<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::post('/register', [AuthController::class, 'register']);

Route::post('/login', [AuthController::class, 'login']);
Route::post('/midtrans-callback', [\App\Http\Controllers\PaymentCallbackController::class, 'receive']);

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    Route::post('/orders', [\App\Http\Controllers\OrderController::class, 'store']);
    Route::get('/orders', [\App\Http\Controllers\OrderController::class, 'index']);
});

Route::get('/products', [\App\Http\Controllers\ProductItemController::class, 'index']);
Route::get('/products/{id}', [\App\Http\Controllers\ProductItemController::class, 'show']);
