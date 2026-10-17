<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DtcController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\RevenueCatWebhookController;
use App\Http\Controllers\Api\V1\RunShareController;
use App\Http\Controllers\Api\V1\SyncController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::middleware('throttle:api-auth')->group(function () {
        Route::post('auth/register', [AuthController::class, 'register'])->name('auth.register');
        Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');
    });

    Route::post('webhooks/revenuecat', RevenueCatWebhookController::class)
        ->middleware('throttle:60,1')
        ->name('webhooks.revenuecat');

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        Route::get('me', [MeController::class, 'show'])->name('me.show');
        Route::delete('me', [MeController::class, 'destroy'])->name('me.destroy');
        Route::get('me/export', [MeController::class, 'export'])->name('me.export');
        Route::get('me/entitlements', [MeController::class, 'entitlements'])->name('me.entitlements');

        Route::post('sync', [SyncController::class, 'push'])->name('sync.push');
        Route::get('sync/pull', [SyncController::class, 'pull'])->name('sync.pull');

        Route::get('dtc/{code}', [DtcController::class, 'show'])
            ->middleware('throttle:dtc')
            ->name('dtc.show');

        Route::post('runs/{uuid}/share', [RunShareController::class, 'store'])->name('runs.share.store');
        Route::delete('runs/{uuid}/share', [RunShareController::class, 'destroy'])->name('runs.share.destroy');
    });
});
