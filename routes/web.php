<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiagnosticController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\RunController;
use App\Http\Controllers\ShareController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::get('r/{token}', [ShareController::class, 'show'])
    ->middleware('throttle:60,1')
    ->name('shared-runs.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('vehicules', [VehicleController::class, 'index'])->name('vehicles.index');
    Route::get('vehicules/nouveau', [VehicleController::class, 'create'])->name('vehicles.create');
    Route::post('vehicules', [VehicleController::class, 'store'])->name('vehicles.store');
    Route::get('vehicules/{vehicle:uuid}', [VehicleController::class, 'show'])->name('vehicles.show');

    Route::get('tirs', [RunController::class, 'index'])->name('runs.index');
    Route::get('tirs/{run:uuid}', [RunController::class, 'show'])->name('runs.show');
    Route::get('tirs/{run:uuid}/csv', [RunController::class, 'csv'])->name('runs.csv');
    Route::post('tirs/{run:uuid}/partage', [RunController::class, 'share'])->name('runs.share');
    Route::delete('tirs/{run:uuid}/partage', [RunController::class, 'unshare'])->name('runs.unshare');

    Route::get('diagnostics', [DiagnosticController::class, 'index'])->name('diagnostics.index');
    Route::get('sante', [HealthController::class, 'index'])->name('health.index');

    Route::get('partages', [ShareController::class, 'index'])->name('shares.index');
    Route::delete('partages/{sharedRun}', [ShareController::class, 'destroy'])->name('shares.destroy');

    Route::get('abonnement', [BillingController::class, 'index'])->name('billing.index');
});

require __DIR__.'/settings.php';
