<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImportController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

/**
 * Import endpoint:
 * - URL: POST /api/nexo-import
 * - Middleware: 'nexo.api' => VerifyApiKey (shared API key)
 * - Controller: ImportController@handlePrefillRedirect
 */
Route::post('/nexo-import', [ImportController::class, 'handlePrefillRedirect'])
    ->middleware('nexo.api'); // Protected
