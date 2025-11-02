<?php

use Illuminate\Support\Facades\Route;

use App\Livewire\PublicCalendar;
use App\Livewire\Auth\ChooseRole;
//use App\Livewire\Director\VenuesIndex as DirectorVenuesIndex;
use App\Livewire\Admin\UsersIndex;
use App\Livewire\Admin\DepartmentsIndex;
use App\Livewire\Admin\VenuesIndex;
use App\Livewire\Admin\EventsIndex;
use App\Livewire\Admin\AuditTrailIndex;

Route::get('/', PublicCalendar::class)->name('public.calendar');
Route::get('/choose-role', ChooseRole::class)->name('choose.role');
//Route::get('/director/venues', DirectorVenuesIndex::class)->name('director.venues');


//Admin---------------------------------------------------------------------------------------------
Route::get('/admin/users', UsersIndex::class)->name('admin.users');
Route::get('/admin/departments', DepartmentsIndex::class)->name('admin.departments');
Route::get('/admin/venues', VenuesIndex::class)->name('admin.venues');
Route::get('/admin/events', EventsIndex::class)->name('admin.events');
Route::get('/admin/audit-log', AuditTrailIndex::class)
  ->name('admin.audit');

//Approver Request History-----------------------------------------------------------
Route::get('approver/requests/history',\App\Livewire\Request\History\Index::class)->name('approver.history.index');
Route::get('/approver/requests/history/{event}',\App\Livewire\Request\History\Details::class)->name('approver.history.request');
//Approver Request Pending-------------------------------------------------------------
Route::get('approver/requests/history/',\App\Livewire\Request\History\Index::class)->name('approver.history.index');



//Route::get('/approver/requests/history/{id}',function (){
////    $event = Event::query()->findOrFail(request()->id);
//
//    return view('requests.history.details', compact('event'));
//
//})->name('approver.history.request');

require __DIR__ . '/saml2.php';
//require __DIR__.'/auth.php';
