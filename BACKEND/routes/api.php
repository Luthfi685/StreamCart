<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\LiveSessionController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\AgoraController;
use App\Http\Controllers\SupportChatController;
use Illuminate\Support\Facades\Http;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login']);
Route::get('/agora/token', [AgoraController::class, 'getToken']);

// Public stats for login page (no auth needed)
Route::get('/login-stats', function () {
    return response()->json([
        'sellers'  => \App\Models\User::where('role', 'seller')->count(),
        'products' => \App\Models\Product::count(),
        'buyers'   => \App\Models\User::where('role', 'buyer')->count(),
        'lives'    => \App\Models\LiveSession::where('status', 'live')->count(),
    ]);
});
Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::get('/auth/google/redirect', [AuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);

Route::get('/live-sessions', [LiveSessionController::class, 'index']);
Route::get('/live-sessions/{id}', [LiveSessionController::class, 'show']);
Route::get('/live-sessions/{id}/live-status', [LiveSessionController::class, 'getLiveStatus']); // Polling
Route::get('/live-sessions/{id}/chat', [ChatController::class, 'index']);

// Support Chat AJAX (Public for Demo)
Route::get('/support/rooms', [SupportChatController::class, 'getRooms']);
Route::get('/support/rooms/{roomId}/messages', [SupportChatController::class, 'getMessages']);
Route::post('/support/rooms/{roomId}/messages', [SupportChatController::class, 'sendMessage']);

/*
|--------------------------------------------------------------------------
| Protected Routes (Auth Required)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // Auth & Profile
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/password', [AuthController::class, 'updatePassword']);
    Route::post('/register-seller', [AuthController::class, 'registerSeller']);

    // Transaction PIN
    Route::post('/user/transaction-pin', [AuthController::class, 'setupTransactionPin']);

    Route::get('/payment-instructions', function() {
        return response()->json(config('wallet.admin_bank'));
    });

    // Orders (Buyer/Seller as Buyer)
    Route::prefix('v1/buyer')->middleware('role:buyer,seller')->group(function () {
        Route::post('/orders', [OrderController::class, 'store']);                   // Buyer buat pesanan
        Route::get('/transactions/history', [OrderController::class, 'history']);    // Buyer history
        Route::post('/orders/{id}/payment-proof', [OrderController::class, 'uploadPaymentProof']); // Buyer upload bukti bayar
        Route::post('/orders/{id}/confirm', [OrderController::class, 'confirmComplete']);
        Route::post('/orders/{id}/request-cancel', [OrderController::class, 'requestCancel']);
        Route::post('/orders/{id}/respond-cancel', [OrderController::class, 'respondCancel']);
        Route::post('/orders/{id}/refund-info', [OrderController::class, 'submitRefundInfo']);
        Route::post('/orders/{id}/reviews', [\App\Http\Controllers\Api\ReviewController::class, 'submitReviews']);
        
        // Recommendations from Python API
        Route::get('/recommendations', [\App\Http\Controllers\Api\ProductController::class, 'recommendations']);
    });

    // Orders (Shared or Seller)
    Route::get('/orders', [OrderController::class, 'index']);                    // List order
    Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus']); // Seller update status
    Route::post('/orders/{id}/respond-cancel', [OrderController::class, 'respondCancel']); // Seller respond cancel

    // Wallet (Seller)
    Route::get('/wallet', [WalletController::class, 'show']);                          // Lihat saldo
    Route::get('/wallet/transactions', [WalletController::class, 'transactions']);      // Riwayat mutasi
    Route::get('/wallet/withdrawals', [WalletController::class, 'withdrawalHistory']); // Riwayat WD
    Route::post('/wallet/withdraw', [WalletController::class, 'withdraw']);             // Ajukan WD

    // Seller Only
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);

    Route::post('/live-sessions', [LiveSessionController::class, 'store']);
    Route::put('/live-sessions/{id}/status', [LiveSessionController::class, 'updateStatus']);
    Route::post('/live-sessions/{id}/products', [LiveSessionController::class, 'bindProducts']);
    Route::post('/live-sessions/{id}/pin-product', [LiveSessionController::class, 'pinProduct']);
    Route::post('/live-sessions/{id}/unpin-product', [LiveSessionController::class, 'unpinProduct']);
    Route::get('/live-sessions/{id}/products', [LiveSessionController::class, 'getProducts']);
    
    // Chat & Like
    Route::post('/live-sessions/{id}/send-chat', [ChatController::class, 'store']);
    Route::put('/live-sessions/{id}/like', [LiveSessionController::class, 'like']);
    Route::post('/live-sessions/{id}/join', [LiveSessionController::class, 'join']);
    Route::post('/live-sessions/{id}/leave', [LiveSessionController::class, 'leave']);

    // Add user address routes
    Route::get('/addresses', [\App\Http\Controllers\Api\AddressController::class, 'index']);
    Route::post('/addresses', [\App\Http\Controllers\Api\AddressController::class, 'store']);
    Route::delete('/addresses/{id}', [\App\Http\Controllers\Api\AddressController::class, 'destroy']);
    
    // Help Center Tickets
    Route::get('/tickets', [\App\Http\Controllers\Api\TicketController::class, 'index']);
    Route::post('/tickets', [\App\Http\Controllers\Api\TicketController::class, 'store']);

    // Notifications
    Route::get('/user/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::patch('/user/notifications/read-all', [\App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
    Route::patch('/user/notifications/{id}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);

    // Admin Only
    Route::middleware('role:admin')->group(function () {
        Route::get('/stats', [AdminController::class, 'stats']);
        Route::get('/users', [AdminController::class, 'users']);
        Route::get('/transactions', [AdminController::class, 'transactions']);
        Route::get('/activity-logs', [AdminController::class, 'activityLogs']);

        // Payment Verification
        Route::get('/orders/pending-payments', [AdminController::class, 'pendingPayments']);
        Route::put('/orders/{id}/verify-payment', [AdminController::class, 'verifyPayment']);

        // Withdrawal Management
        Route::get('/withdrawals', [AdminController::class, 'withdrawals']);
        Route::put('/withdrawals/{id}', [AdminController::class, 'processWithdrawal']);
    });
});

/*
|--------------------------------------------------------------------------
| Regions Proxy API
|--------------------------------------------------------------------------
*/
Route::get('/regions/provinces', function () {
    return Http::get('https://emsifa.github.io/api-wilayah-indonesia/api/provinces.json')->json();
});
Route::get('/regions/cities/{provinceId}', function ($provinceId) {
    return Http::get("https://emsifa.github.io/api-wilayah-indonesia/api/regencies/{$provinceId}.json")->json();
});
Route::get('/regions/districts/{cityId}', function ($cityId) {
    return Http::get("https://emsifa.github.io/api-wilayah-indonesia/api/districts/{$cityId}.json")->json();
});
