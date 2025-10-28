<?php

use Illuminate\Support\Facades\Route;

use App\Livewire\Admin\UsersIndex;
use App\Livewire\Admin\DepartmentsIndex;
use App\Livewire\Admin\VenuesIndex;
use App\Livewire\Admin\EventsIndex;
use App\Livewire\Admin\OverridesIndex;
use App\Livewire\Admin\AuditTrailIndex;

Route::get('/admin/users', UsersIndex::class)->name('admin.users');
Route::get('/admin/departments', DepartmentsIndex::class)->name('admin.departments');
Route::get('/admin/venues', VenuesIndex::class)->name('admin.venues');
Route::get('/admin/events', EventsIndex::class)->name('admin.events');
Route::get('/admin/overrides', OverridesIndex::class)->name('admin.overrides');
Route::get('/admin/audit-log', AuditTrailIndex::class)
  ->name('admin.audit');
Route::redirect('/', '/admin/users');


require __DIR__ . '/saml2.php';
//require __DIR__.'/auth.php';
