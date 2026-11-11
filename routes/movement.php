<?php

/*
 * Movement module routes (docs/movement-module-implementation-plan.md, D14).
 * Required from routes/web.php inside the existing auth group. URL prefix /dashboard/movement,
 * route names movement.*. Authorization is permission-based through Policies; no role: middleware.
 */

use App\Modules\Movement\Http\Controllers\Fleet\DriverController;
use App\Modules\Movement\Http\Controllers\Fleet\LocationController;
use App\Modules\Movement\Http\Controllers\Fleet\UnavailabilityController;
use App\Modules\Movement\Http\Controllers\Fleet\VehicleController;
use App\Modules\Movement\Http\Controllers\Fleet\VehicleTypeController;
use App\Modules\Movement\Http\Controllers\MovementDashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('dashboard/movement')->name('movement.')->group(function () {
    // Public (every authenticated user): aggregates only (D7).
    Route::get('/fleet-overview', [MovementDashboardController::class, 'fleet'])->name('fleet.overview');

    Route::prefix('vehicle-types')->name('vehicle-types.')->group(function () {
        Route::get('/', [VehicleTypeController::class, 'index'])->name('index');
        Route::post('/', [VehicleTypeController::class, 'store'])->name('store');
        Route::get('/{vehicleType}/edit', [VehicleTypeController::class, 'edit'])->whereNumber('vehicleType')->name('edit');
        Route::put('/{vehicleType}', [VehicleTypeController::class, 'update'])->whereNumber('vehicleType')->name('update');
        Route::delete('/{vehicleType}', [VehicleTypeController::class, 'destroy'])->whereNumber('vehicleType')->name('destroy');
        Route::post('/{vehicleType}/restore', [VehicleTypeController::class, 'restore'])->whereNumber('vehicleType')->withTrashed()->name('restore');
    });

    Route::prefix('vehicles')->name('vehicles.')->group(function () {
        Route::get('/', [VehicleController::class, 'index'])->name('index');
        Route::get('/create', [VehicleController::class, 'create'])->name('create');
        Route::post('/', [VehicleController::class, 'store'])->name('store');
        Route::get('/{vehicle}', [VehicleController::class, 'show'])->whereNumber('vehicle')->name('show');
        Route::get('/{vehicle}/edit', [VehicleController::class, 'edit'])->whereNumber('vehicle')->name('edit');
        Route::put('/{vehicle}', [VehicleController::class, 'update'])->whereNumber('vehicle')->name('update');
        Route::post('/{vehicle}/status', [VehicleController::class, 'changeStatus'])->whereNumber('vehicle')->name('status');
        Route::delete('/{vehicle}', [VehicleController::class, 'destroy'])->whereNumber('vehicle')->name('destroy');
        Route::post('/{vehicle}/restore', [VehicleController::class, 'restore'])->whereNumber('vehicle')->withTrashed()->name('restore');
    });

    Route::prefix('drivers')->name('drivers.')->group(function () {
        Route::get('/', [DriverController::class, 'index'])->name('index');
        Route::get('/create', [DriverController::class, 'create'])->name('create');
        Route::post('/', [DriverController::class, 'store'])->name('store');
        Route::get('/{driver}', [DriverController::class, 'show'])->whereNumber('driver')->name('show');
        Route::get('/{driver}/edit', [DriverController::class, 'edit'])->whereNumber('driver')->name('edit');
        Route::put('/{driver}', [DriverController::class, 'update'])->whereNumber('driver')->name('update');
        Route::post('/{driver}/link', [DriverController::class, 'link'])->whereNumber('driver')->name('link');
        Route::delete('/{driver}/link', [DriverController::class, 'unlink'])->whereNumber('driver')->name('unlink');
        Route::delete('/{driver}', [DriverController::class, 'destroy'])->whereNumber('driver')->name('destroy');
        Route::post('/{driver}/restore', [DriverController::class, 'restore'])->whereNumber('driver')->withTrashed()->name('restore');
    });

    Route::post('/unavailabilities', [UnavailabilityController::class, 'store'])->name('unavailabilities.store');
    Route::delete('/unavailabilities/{unavailability}', [UnavailabilityController::class, 'destroy'])->whereNumber('unavailability')->name('unavailabilities.destroy');

    Route::prefix('locations')->name('locations.')->group(function () {
        Route::get('/', [LocationController::class, 'index'])->name('index');
        Route::get('/create', [LocationController::class, 'create'])->name('create');
        Route::post('/', [LocationController::class, 'store'])->name('store');
        Route::get('/{location}/edit', [LocationController::class, 'edit'])->whereNumber('location')->name('edit');
        Route::put('/{location}', [LocationController::class, 'update'])->whereNumber('location')->name('update');
        Route::delete('/{location}', [LocationController::class, 'destroy'])->whereNumber('location')->name('destroy');
        Route::post('/{location}/restore', [LocationController::class, 'restore'])->whereNumber('location')->withTrashed()->name('restore');
    });
});
