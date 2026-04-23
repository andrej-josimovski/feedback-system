<?php

use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('feedback')->group(function (): void {
    Route::get('/', [FeedbackController::class, 'index']);
    Route::post('/', [FeedbackController::class, 'store']);
    Route::get('{feedback}', [FeedbackController::class, 'show']);
});

Route::prefix('products')->group(function (): void {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('{product}', [ProductController::class, 'show']);
    Route::get('{product}/feedback', [FeedbackController::class, 'indexForProduct']);
    Route::post('{product}/feedback', [FeedbackController::class, 'storeForProduct']);
});

Route::prefix('reports')->group(function (): void {
    Route::get('/', [ReportController::class, 'index']);
    Route::post('/', [ReportController::class, 'store']);
    Route::get('{report}', [ReportController::class, 'show']);
    Route::get('{report}/sections', [ReportController::class, 'sections']);
});
