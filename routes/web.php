<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\VenueController;
use App\Http\Controllers\AuditLogController;

Route::get('/', fn () => ['Laravel' => app()->version()]);

Route::middleware(['auth', 'role:system-admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Admin dashboards
        Route::get('/users', [AdminController::class, 'usersIndex'])->name('users.index');
        Route::get('/departments', [AdminController::class, 'departmentsIndex'])->name('departments.index');
        Route::get('/venues', [AdminController::class, 'venuesIndex'])->name('venues.index'); // <-- this is admin.venues.index
        Route::get('/events', [AdminController::class, 'eventsIndex'])->name('events.index');

        // Overrides
        Route::get('/overrides', [AdminController::class, 'overridesIndex'])->name('overrides.index');
        Route::post('/overrides', [AdminController::class, 'performOverride'])->name('overrides.store');

        // AuditLog
        Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');

        // Venues (CRUD/views under admin namespace)
        Route::get('/venues/{venue_id}', [VenueController::class, 'show'])->name('venues.show');
        Route::get('/venues/{venue_id}/edit', [VenueController::class, 'edit'])->name('venues.edit');
        Route::post('/venues/{venue_id}/edit', [VenueController::class, 'update'])->name('venues.update');
        Route::get('/venues/{venue_id}/requirements', [VenueController::class, 'show_requirements'])->name('venues.requirements');
        Route::get('/venues/{venue_id}/requirements/edit', [VenueController::class, 'edit_requirements'])->name('venues.requirements.edit');
        Route::post('/venues/{venue_id}/requirements/edit', [VenueController::class, 'update_requirements'])->name('venues.requirements.update');
    });

// If you need SAML routes
require __DIR__.'/saml2.php';
// require __DIR__.'/auth.php';
