<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

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
