<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserConsumerController;
use App\Http\Controllers\RecommendationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Provider Routes
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::post('/products', [ProductController::class, 'store']);
Route::put('/products/{id}', [ProductController::class, 'update']);
Route::delete('/products/{id}', [ProductController::class, 'destroy']);

// Consumer Routes
Route::get('/products/{id}/seller', [UserConsumerController::class, 'getProductSeller']);
Route::get('/users', [UserConsumerController::class, 'getAllUsers']);

// AI Recommendation Routes
Route::get('/recommendations/similar/{productId}', [RecommendationController::class, 'getSimilarProducts']);
Route::get('/recommendations/user/{userId}', [RecommendationController::class, 'getUserRecommendations']);
Route::post('/recommendations/update-history', [RecommendationController::class, 'updatePurchaseHistory']);