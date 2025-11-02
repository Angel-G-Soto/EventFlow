<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VenueController;

Route::get('/', function () { return ['Laravel' => app()->version()];});

// Venues
    // View of all the venues
    Route::get('admin/venues', [VenueController::class, 'index'])->name('venues.index');
    // View of a specific venue
    Route::get('admin/venues/{venue_id}', [VenueController::class, 'show'])->name('venues.show');
    Route::get('admin/venues/{venue_id}/edit', [VenueController::class, 'edit'])->name('venues.edit');
    Route::post('admin/venues/{venue_id}/edit', [VenueController::class, 'update'])->name('venues.update');
    // Form to edit the venue requirements
    Route::get('admin/venues/{venue_id}/requirements', [VenueController::class, 'show_requirements'])->name('venues.requirements');
    Route::get('admin/venues/{venue_id}/requirements/edit', [VenueController::class, 'edit_requirements'])->name('venues.requirements.edit');
    Route::post('admin/venues/{venue_id}/requirements/edit', [VenueController::class, 'update_requirements'])->name('venues.requirements.update');

require __DIR__.'/saml2.php';
//require __DIR__.'/auth.php';

/**
 * Dummy 'events.create' route target used by ImportController redirect.
 * Returns JSON so your tests and API consumers get a clear payload.
 */
Route::get('/events/create', function () {
    return response()->json([
        'success' => true,
        'message' => 'Dummy events.create reached'
    ]);
})->name('events.create');
