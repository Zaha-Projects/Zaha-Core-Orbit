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
use App\Modules\Movement\Http\Controllers\AvailabilityController;
use App\Modules\Movement\Http\Controllers\MovementDashboardController;
use App\Modules\Movement\Http\Controllers\Requests\MovementRequestController;
use App\Modules\Movement\Http\Controllers\Requests\MovementRequestReviewController;
use Illuminate\Support\Facades\Route;

Route::prefix('dashboard/movement')->name('movement.')->group(function () {
    // Public (every authenticated user): aggregates only (D7).
    Route::get('/fleet-overview', [MovementDashboardController::class, 'fleet'])->name('fleet.overview');
    Route::get('/availability/summary', [AvailabilityController::class, 'summary'])->name('availability.summary');

    // Requests (Phase 4A). Requester writes are throttled by movement-requests (D20 request part).
    Route::prefix('requests')->name('requests.')->group(function () {
        Route::get('/', [MovementRequestController::class, 'index'])->name('index');
        Route::get('/review', [MovementRequestReviewController::class, 'index'])->name('review.index');
        Route::get('/create', [MovementRequestController::class, 'create'])->name('create');
        Route::post('/', [MovementRequestController::class, 'store'])->middleware('throttle:movement-requests')->name('store');
        Route::get('/{movementRequest}', [MovementRequestController::class, 'show'])->whereNumber('movementRequest')->name('show');
        Route::get('/{movementRequest}/edit', [MovementRequestController::class, 'edit'])->whereNumber('movementRequest')->name('edit');
        Route::put('/{movementRequest}', [MovementRequestController::class, 'update'])->whereNumber('movementRequest')->middleware('throttle:movement-requests')->name('update');
        Route::post('/{movementRequest}/submit', [MovementRequestController::class, 'submit'])->whereNumber('movementRequest')->middleware('throttle:movement-requests')->name('submit');
        Route::post('/{movementRequest}/cancel', [MovementRequestController::class, 'cancel'])->whereNumber('movementRequest')->middleware('throttle:movement-requests')->name('cancel');
        Route::delete('/{movementRequest}', [MovementRequestController::class, 'destroy'])->whereNumber('movementRequest')->middleware('throttle:movement-requests')->name('destroy');
        Route::post('/{movementRequest}/review', [MovementRequestReviewController::class, 'startReview'])->whereNumber('movementRequest')->name('review.start');
        Route::post('/{movementRequest}/decision', [MovementRequestReviewController::class, 'decide'])->whereNumber('movementRequest')->name('review.decide');
    });

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
