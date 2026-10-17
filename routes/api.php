<?php

use App\Http\Controllers\Api\V1\Admin\ReleaseController;
use App\Http\Controllers\Api\V1\AppBuildController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DtcController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\OtaController;
use App\Http\Controllers\Api\V1\ReleaseDownloadController;
use App\Http\Controllers\Api\V1\RevenueCatWebhookController;
use App\Http\Controllers\Api\V1\RunShareController;
use App\Http\Controllers\Api\V1\SyncController;
use App\Http\Middleware\EnsureValidPublishToken;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::middleware('throttle:api-auth')->group(function () {
        Route::post('auth/register', [AuthController::class, 'register'])->name('auth.register');
        Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');
    });

    Route::post('webhooks/revenuecat', RevenueCatWebhookController::class)
        ->middleware('throttle:60,1')
        ->name('webhooks.revenuecat');

    Route::middleware('throttle:60,1')->group(function () {
        Route::get('apps/{project}/{arc}/{fingerprint}/{held?}', [OtaController::class, 'check'])
            ->where('fingerprint', '[A-Za-z0-9._-]+')
            ->where('held', '[A-Za-z0-9-]+')
            ->name('ota.check');

        Route::get('app-builds/{project}/android/latest', [AppBuildController::class, 'latest'])
            ->name('app-builds.latest');

        Route::get('releases/{uuid}/download', ReleaseDownloadController::class)
            ->middleware('signed:relative')
            ->name('releases.download');
    });

    Route::prefix('admin')
        ->name('admin.')
        ->middleware(['throttle:30,1', EnsureValidPublishToken::class])
        ->group(function () {
            Route::get('releases', [ReleaseController::class, 'index'])->name('releases.index');
            Route::post('releases', [ReleaseController::class, 'store'])->name('releases.store');
            Route::post('releases/{uuid}/deactivate', [ReleaseController::class, 'deactivate'])->name('releases.deactivate');
            Route::post('releases/{uuid}/activate', [ReleaseController::class, 'activate'])->name('releases.activate');
        });

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
