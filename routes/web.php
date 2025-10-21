<?php

use Illuminate\Support\Facades\Route;

use App\Livewire\Admin\UsersIndex;
use App\Livewire\Admin\VenueIndex;
use App\Livewire\Admin\EventsIndex;
use App\Livewire\Admin\OverridesIndex;

Route::get('/admin/users', UsersIndex::class)->name('admin.users');
Route::get('/admin/venues', VenueIndex::class)->name('admin.venues');
Route::get('/admin/events', EventsIndex::class)->name('admin.events');
Route::get('/admin/overrides', OverridesIndex::class)->name('admin.overrides');
Route::redirect('/', '/admin/users');


require __DIR__ . '/saml2.php';
//require __DIR__.'/auth.php';
