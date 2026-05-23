<?php

use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

// Public auth routes (no authentication required)
Route::post('/auth/login', [AuthController::class, 'login']);

// Public routes (no authentication required)
Route::prefix('products')->group(function (): void {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('{product}', [ProductController::class, 'show']);
});

// Protected routes (authentication required)
Route::middleware(['auth:sanctum'])->group(function (): void {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);

    // Feedback routes - both admin and members can create
    Route::prefix('feedback')->group(function (): void {
        Route::get('/', [FeedbackController::class, 'index'])->middleware('admin'); // Admin only
        Route::post('/', [FeedbackController::class, 'store']); // All authenticated users
        Route::get('{feedback}', [FeedbackController::class, 'show']);
    });

    // Product feedback routes
    Route::prefix('products')->group(function (): void {
        Route::get('{product}/feedback', [FeedbackController::class, 'indexForProduct'])->middleware('admin'); // Admin only
        Route::post('{product}/feedback', [FeedbackController::class, 'storeForProduct']); // All authenticated users
    });

    // Report routes - admin only
    Route::prefix('reports')->middleware('admin')->group(function (): void {
        Route::get('/', [ReportController::class, 'index']);
        Route::post('/', [ReportController::class, 'store']);
        Route::get('{report}', [ReportController::class, 'show']);
        Route::get('{report}/sections', [ReportController::class, 'sections']);
        Route::patch('{report}', [ReportController::class, 'update']);
        Route::patch('{report}/publish', [ReportController::class, 'publish']);
    });
});
