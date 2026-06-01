<?php

use Illuminate\Support\Facades\Route;

// Health check
Route::get('/health', fn () => response()->json(['status' => 'ok']));

// Public routes
Route::post('/register', [\App\Http\Controllers\Api\AuthController::class, 'register']);
Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
Route::post('/forgot-password', [\App\Http\Controllers\Api\AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [\App\Http\Controllers\Api\AuthController::class, 'resetPassword']);

// Public painter profiles
Route::get('/painters', [\App\Http\Controllers\Api\PainterController::class, 'index']);
Route::get('/painters/{slug}', [\App\Http\Controllers\Api\PainterController::class, 'show']);
Route::get('/painters/{slug}/paintings', [\App\Http\Controllers\Api\PainterController::class, 'paintings']);
Route::get('/painters/{slug}/videos', [\App\Http\Controllers\Api\PainterController::class, 'videos']);
Route::get('/painters/{slug}/projects', [\App\Http\Controllers\Api\PainterController::class, 'projects']);

// Public paintings
Route::get('/paintings', [\App\Http\Controllers\Api\PaintingController::class, 'index']);
Route::get('/paintings/{id}', [\App\Http\Controllers\Api\PaintingController::class, 'show']);

// Public videos (shop)
Route::get('/videos', [\App\Http\Controllers\Api\VideoController::class, 'index']);
Route::get('/videos/{id}', [\App\Http\Controllers\Api\VideoController::class, 'show']);

// CSRF cookie for Sanctum
Route::get('/sanctum/csrf-cookie', fn () => response()->json(['status' => 'ok']));

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    // User
    Route::get('/user', [\App\Http\Controllers\Api\AuthController::class, 'user']);
    Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
    Route::put('/profile', [\App\Http\Controllers\Api\AuthController::class, 'updateProfile']);
    Route::put('/password', [\App\Http\Controllers\Api\AuthController::class, 'changePassword']);

    // Grisaille Tool
    Route::post('/grisaille/analyze', [\App\Http\Controllers\Api\GrisailleController::class, 'analyze']);
    Route::get('/grisaille/history', [\App\Http\Controllers\Api\GrisailleController::class, 'history']);
    Route::get('/grisaille/{id}/pdf', [\App\Http\Controllers\Api\GrisailleController::class, 'downloadPdf']);

    // Painter Dashboard (painter role only)
    Route::middleware('role:painter')->prefix('dashboard')->group(function () {
        Route::get('/paintings', [\App\Http\Controllers\Api\PaintingController::class, 'dashboardIndex']);
        Route::post('/paintings', [\App\Http\Controllers\Api\PaintingController::class, 'store']);
        Route::put('/paintings/{id}', [\App\Http\Controllers\Api\PaintingController::class, 'update']);
        Route::delete('/paintings/{id}', [\App\Http\Controllers\Api\PaintingController::class, 'destroy']);

        Route::get('/videos', [\App\Http\Controllers\Api\VideoController::class, 'dashboardIndex']);
        Route::post('/videos', [\App\Http\Controllers\Api\VideoController::class, 'store']);
        Route::put('/videos/{id}', [\App\Http\Controllers\Api\VideoController::class, 'update']);
        Route::delete('/videos/{id}', [\App\Http\Controllers\Api\VideoController::class, 'destroy']);

        Route::get('/orders', [\App\Http\Controllers\Api\OrderController::class, 'painterOrders']);
        Route::get('/earnings', [\App\Http\Controllers\Api\OrderController::class, 'earnings']);
    });

    // Orders & Payments
    Route::post('/orders/checkout', [\App\Http\Controllers\Api\OrderController::class, 'checkout']);
    Route::get('/orders', [\App\Http\Controllers\Api\OrderController::class, 'index']);
    Route::get('/orders/{id}', [\App\Http\Controllers\Api\OrderController::class, 'show']);
    Route::post('/videos/{id}/purchase', [\App\Http\Controllers\Api\VideoController::class, 'purchase']);
    Route::get('/videos/{id}/access', [\App\Http\Controllers\Api\VideoController::class, 'checkAccess']);

    // Stripe Connect
    Route::post('/stripe/connect/onboard', [\App\Http\Controllers\Api\PainterController::class, 'stripeOnboard']);
    Route::get('/stripe/connect/status', [\App\Http\Controllers\Api\PainterController::class, 'stripeStatus']);
    Route::post('/stripe/connect/refresh', [\App\Http\Controllers\Api\PainterController::class, 'stripeRefresh']);
});

// Stripe Webhooks
Route::post('/webhooks/stripe', [\App\Http\Controllers\Api\OrderController::class, 'webhook'])
    ->middleware(\App\Http\Middleware\VerifyStripeWebhook::class);

// Admin routes
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/users', [\App\Http\Controllers\Api\AdminController::class, 'users']);
    Route::put('/users/{id}/role', [\App\Http\Controllers\Api\AdminController::class, 'updateUserRole']);
    Route::delete('/users/{id}', [\App\Http\Controllers\Api\AdminController::class, 'deleteUser']);
    Route::get('/stats', [\App\Http\Controllers\Api\AdminController::class, 'stats']);
});
