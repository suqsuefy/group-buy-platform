<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json([
    'name' => 'Group Buying API',
    'status' => 'online',
]));

use App\Http\Controllers\DashboardController;

Route::get('/login', fn () => redirect()->route('dashboard.login'))->name('login');
Route::middleware('guest:web')->group(function () {
    Route::get('/dashboard/login', [DashboardController::class, 'loginForm'])->name('dashboard.login');
    Route::post('/dashboard/login', [DashboardController::class, 'login'])->name('dashboard.login.submit');
});
Route::middleware(['auth:web', \App\Http\Middleware\EnsureDashboardAccess::class])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.home');
    Route::post('/dashboard/logout', [DashboardController::class, 'logout'])->name('dashboard.logout');
    Route::post('/dashboard/orders/{order}/transition', [DashboardController::class, 'transition'])->name('dashboard.orders.transition');
    Route::post('/dashboard/orders/{order}/collect', [DashboardController::class, 'collect'])->name('dashboard.orders.collect');
    Route::post('/dashboard/stores/{store}/moderate', [DashboardController::class, 'moderateStore'])->name('dashboard.stores.moderate');
    Route::post('/dashboard/products/{product}/moderate', [DashboardController::class, 'moderateProduct'])->name('dashboard.products.moderate');
});
