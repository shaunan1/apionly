<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

use App\Http\Controllers\UserController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
Route::get('/users', [UserController::class, 'index']);
Route::get('/orders', [CustomerController::class, 'index']);
Route::get('/customers', [OrderController::class, 'index']);
Route::get('/products', [ProductController::class, 'index']);