<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SupportChatController;
use App\Http\Controllers\WebAuthController;
use App\Http\Controllers\SellerRegistrationController;
use App\Http\Controllers\SellerDashboardController;
use App\Http\Controllers\SellerProductController;
use App\Http\Controllers\SellerLiveSessionController;
use App\Http\Controllers\SellerOrderController;
use App\Http\Controllers\SellerWalletController;
use App\Http\Controllers\SellerStoreProfileController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminLiveSessionController;
use App\Http\Controllers\AdminTransactionController;

Route::get('/', function () { return redirect()->route('login'); });
Route::view('/delete-account', 'delete-account')->name('delete-account');

// Auth
Route::get('/login',  [WebAuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [WebAuthController::class, 'login']);
Route::post('/logout',[WebAuthController::class, 'logout'])->name('logout');

// Google OAuth for Web
Route::get('/auth/google',          [WebAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [WebAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

// Seller Registration
Route::get('/register-seller',  [SellerRegistrationController::class, 'showRegistrationForm']);
Route::post('/register-seller', [SellerRegistrationController::class, 'upgradeToSeller']);

// ── SELLER ROUTES ─────────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:seller'])->prefix('seller')->name('seller.')->group(function () {
    Route::get('/dashboard',          [SellerDashboardController::class, 'index'])->name('dashboard');

    // Realtime JSON polling endpoints
    Route::get('/api/dashboard-stats', [SellerDashboardController::class, 'realtimeStats'])->name('api.dashboard-stats');
    Route::get('/api/orders',          [SellerOrderController::class,    'realtimeOrders'])->name('api.orders');
    Route::get('/api/wallet-stats',    [SellerWalletController::class,   'realtimeStats'])->name('api.wallet-stats');

    Route::get('/products',           [SellerProductController::class, 'index'])->name('products.index');
    Route::get('/products/create',    [SellerProductController::class, 'create'])->name('products.create');
    Route::post('/products',          [SellerProductController::class, 'store'])->name('products.store');
    Route::get('/products/{id}/edit', [SellerProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{id}',      [SellerProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{id}',   [SellerProductController::class, 'destroy'])->name('products.destroy');

    Route::get('/live-sessions',      [SellerLiveSessionController::class, 'index'])->name('live.index');
    Route::post('/live-sessions',     [SellerLiveSessionController::class, 'store'])->name('live.store');
    Route::get('/live-sessions/{id}/studio', [SellerLiveSessionController::class, 'studio'])->name('live.studio');
    Route::post('/live-sessions/{id}/pin', [SellerLiveSessionController::class, 'pinProduct'])->name('live.pin');
    Route::patch('/live-sessions/{id}/end', [SellerLiveSessionController::class, 'end'])->name('live.end');
    Route::post('/live-sessions/{id}/start', [SellerLiveSessionController::class, 'startScheduled'])->name('live.start_scheduled');

    Route::get('/orders',             [SellerOrderController::class, 'index'])->name('orders.index');
    Route::patch('/orders/{id}/status', [SellerOrderController::class, 'updateStatus'])->name('orders.status');
    Route::patch('/orders/{id}/respond-cancel', [SellerOrderController::class, 'respondCancel'])->name('orders.respond_cancel');

    Route::get('/wallet',             [SellerWalletController::class, 'index'])->name('wallet.index');
    Route::post('/wallet/withdraw',   [SellerWalletController::class, 'withdraw'])->name('wallet.withdraw');

    Route::get('/store-profile',      [SellerStoreProfileController::class, 'index'])->name('store.index');
    Route::put('/store-profile',      [SellerStoreProfileController::class, 'update'])->name('store.update');
    Route::put('/store-profile/password', [SellerStoreProfileController::class, 'updatePassword'])->name('store.password');

    Route::get('/support',            [SupportChatController::class, 'sellerView'])->name('support');
});

// ── ADMIN ROUTES ──────────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard',          [AdminDashboardController::class, 'index'])->name('dashboard');

    // Real-time JSON API (AJAX polling — no CSRF needed, same web middleware)
    Route::get('/api/realtime-stats',      [AdminDashboardController::class,   'realtimeStats'])->name('api.realtime-stats');
    Route::get('/api/transactions',        [AdminTransactionController::class,  'realtimeTransactions'])->name('api.transactions');
    Route::get('/api/withdrawals',         [AdminTransactionController::class,  'realtimeWithdrawals'])->name('api.withdrawals');

    // Users
    Route::get('/users',                   [AdminUserController::class, 'index'])->name('users.index');
    Route::post('/users/{id}/ban',         [AdminUserController::class, 'ban'])->name('users.ban');
    Route::post('/users/{id}/unban',       [AdminUserController::class, 'unban'])->name('users.unban');

    // Live sessions
    Route::get('/live-sessions',           [AdminLiveSessionController::class, 'index'])->name('live.index');
    Route::patch('/live-sessions/{id}/stop', [AdminLiveSessionController::class, 'stop'])->name('live.stop');

    // Transactions (orders)
    Route::get('/transactions',            [AdminTransactionController::class, 'index'])->name('transactions.index');
    Route::patch('/transactions/{id}/approve', [AdminTransactionController::class, 'approve'])->name('transactions.approve');
    Route::patch('/transactions/{id}/reject',  [AdminTransactionController::class, 'reject'])->name('transactions.reject');

    // Withdrawals
    Route::get('/withdrawals',             [AdminTransactionController::class, 'withdrawals'])->name('withdrawals.index');
    Route::patch('/withdrawals/{id}/process', [AdminTransactionController::class, 'processWithdrawal'])->name('withdrawals.process');

    // Refunds
    Route::get('/refunds',                 [AdminTransactionController::class, 'refunds'])->name('refunds.index');
    Route::patch('/refunds/{id}/process',  [AdminTransactionController::class, 'processRefund'])->name('refunds.process');

    Route::get('/support',                 [SupportChatController::class, 'adminView'])->name('support');
    
    // Support Tickets (Form Pengaduan)
    Route::get('/tickets',                 [\App\Http\Controllers\AdminTicketController::class, 'index'])->name('tickets.index');
    Route::patch('/tickets/{id}/status',   [\App\Http\Controllers\AdminTicketController::class, 'updateStatus'])->name('tickets.status');
});

