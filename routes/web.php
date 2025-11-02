<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\VenueController;
use App\Http\Controllers\AuditLogController;

Route::get('/', function () { return ['Laravel' => app()->version()];});

// If you need SAML routes
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
