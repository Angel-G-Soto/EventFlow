<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NexoImportController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

// Nexo import endpoint
Route::post('/nexo-import', [NexoImportController::class, 'handleNexoImport'])
    ->middleware('nexo.api'); // Protected
