<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NexoImportController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

// Nexo import endpoint — protected by API key middleware (see below).
Route::post('nexo/import', [NexoImportController::class, 'import'])
    ->middleware('nexo.api'); // Make sure to register middleware in Kernel
