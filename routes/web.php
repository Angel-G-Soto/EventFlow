<?php

use Illuminate\Support\Facades\Route;

use App\Livewire\PublicCalendar;
use App\Livewire\Auth\ChooseRole;
//use App\Livewire\Director\VenuesIndex as DirectorVenuesIndex;
use App\Livewire\Admin\{UsersIndex, VenuesIndex, EventsIndex, AuditTrailIndex, DepartmentsIndex};
use App\Http\Controllers\AdminController;

Route::get('/', PublicCalendar::class)->name('public.calendar');
Route::middleware('web')->group(function () {
  // after SSO callbacks we redirect here (see §2)
  Route::get('/choose-role', ChooseRole::class)
    ->name('choose.role')
    ->middleware('auth'); // NEW
}); //Route::get('/director/venues', DirectorVenuesIndex::class)->name('director.venues');


//Admin---------------------------------------------------------------------------------------------
Route::prefix('admin')->name('admin.')->group(function () {
  Route::get('/users', \App\Livewire\Admin\UsersIndex::class)->name('users');
  Route::get('/events', \App\Livewire\Admin\EventsIndex::class)->name('events');
  Route::get('/venues', \App\Livewire\Admin\VenuesIndex::class)->name('venues');
  Route::get('/departments', \App\Livewire\Admin\DepartmentsIndex::class)->name('departments');
  Route::get('/audit', \App\Livewire\Admin\AuditTrailIndex::class)->name('audit');
});


//Approver Request History-----------------------------------------------------------
Route::get('approver/requests/history', \App\Livewire\Request\History\Index::class)->name('approver.history.index');
Route::get('/approver/requests/history/{event}', \App\Livewire\Request\History\Details::class)->name('approver.history.request');
//Approver Request Pending-------------------------------------------------------------
Route::get('approver/requests/history/', \App\Livewire\Request\History\Index::class)->name('approver.history.index');

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

//Route::get('/approver/requests/history/{id}',function (){
////    $event = Event::query()->findOrFail(request()->id);
//
//    return view('requests.history.details', compact('event'));
//
//})->name('approver.history.request');

require __DIR__ . '/saml2.php';
//require __DIR__.'/auth.php';
