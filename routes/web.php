<?php

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\BackupController;
use App\Http\Middleware\EnsureAuthentication;
use App\Models\Document;
use App\Services\DocumentService;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

use App\Livewire\PublicCalendar;
use App\Livewire\Auth\ChooseRole;
//use App\Livewire\Director\VenuesIndex as DirectorVenuesIndex;
use App\Livewire\Admin\UsersIndex;
use App\Livewire\Admin\DepartmentsIndex;
use App\Livewire\Admin\VenuesIndex;
use App\Livewire\Admin\EventsIndex;
use App\Livewire\Admin\AuditTrailIndex;
use App\Livewire\Admin\CategoriesIndex;
use Illuminate\Http\Request;
use App\Http\Controllers\AuditLogController;

require __DIR__ . '/saml2.php';
//require __DIR__.'/auth.php';


Route::get('/', PublicCalendar::class)->name('public.calendar');
//Route::get('/choose-role', ChooseRole::class)->name('choose.role');
//Route::get('/director/venues', DirectorVenuesIndex::class)->name('director.venues');

Route::get('/mail/test', function () {
  return view('mail.approval-required-email');
});

Route::middleware([EnsureAuthentication::class])->group(function () {
  //Admin---------------------------------------------------------------------------------------------
  Route::get('/admin/backups', \App\Livewire\Admin\Backups::class)->name('admin.backups');
  Route::get('/admin/backups/download', [BackupController::class, 'download'])->name('admin.backups.download');
  Route::get('/admin/users', UsersIndex::class)->name('admin.users');
  Route::get('/admin/departments', DepartmentsIndex::class)->name('admin.departments');
  Route::get('/admin/venues', VenuesIndex::class)->name('admin.venues');
  Route::get('/dsca/categories', CategoriesIndex::class)->name('dsca.categories');
  Route::get('/admin/events', EventsIndex::class)->name('admin.events');
  Route::get('/admin/audit-log', AuditTrailIndex::class)
    ->name('admin.audit');
  Route::get('/admin/audit-log/download', [AuditLogController::class, 'download'])
    ->name('admin.audit.download');

  //Approver Request History-----------------------------------------------------------
  Route::get('/approver/requests/history', \App\Livewire\Request\History\Index::class)->name('approver.history.index');
  Route::get('/approver/requests/history/{eventHistory}', \App\Livewire\Request\History\Details::class)->name('approver.history.request');

  //Approver Request Pending---------------------------------------------------------------------------
  Route::get('/approver/requests/pending', \App\Livewire\Request\Pending\Index::class)->name('approver.pending.index');
  Route::get('/approver/requests/pending/{event}', \App\Livewire\Request\Pending\Details::class)->name('approver.pending.request');

  //Student organization----------------------------------------------------------------------------------
  Route::get('/user/requests', \App\Livewire\Request\Org\Index::class)->name('user.index');
  Route::get('/user/requests/{event}', \App\Livewire\Request\Org\Details::class)->name('user.request');

  //Venue Manager------------------------------------------------------------------------------------------
  Route::get('/venues', \App\Livewire\Venue\Index::class)->name('venues.manage');
  Route::get('/venues/{venue}', \App\Livewire\Venue\Show::class)->name('venue.show');
  Route::get('/venues/requirements/{venue}', \App\Livewire\Venue\Configure::class)
    ->name('venue.requirements.edit');

  //Event Creation
  Route::get('/event/create', \App\Livewire\Request\Create::class)->name('event.create');

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

  Route::get('/director', \App\Livewire\Director\VenuesIndex::class)->name('director.venues.index');

  //Documents


    Route::get('/documents/{documentId}', [DocumentController::class, 'show'])
      ->name('documents.show');

  // Route::get('/documents/{documentId}', \App\Livewire\ShowDocument::class)
  //     ->name('documents.show');


  // Route::get('/documents/{document}/pdf', function (Document $document, DocumentService $service) {
  //       // Optional: policy check here if you want
  //       // $this->authorize('viewMyDocument', [$document->event]);

  //       return $service->showPDF($document);
  //   })->name('documents.pdf');



//  Route::get('/documents/{file_path}', function (string $file_path, Request $request) {
//    $path = \Illuminate\Support\Facades\Storage::disk('documents')->path($file_path);
//
//      $downloadName = $request->query('name') ?: basename($path);
//
////    abort_unless(file_exists($path), 404);
//
//    return Response::file($path, [
//      'Content-Type'        => 'application/pdf',
//      'Content-Disposition' => 'inline; filename="' . $downloadName . '"',
//      'Cache-Control'       => 'private, max-age=3600',
//    ]);
//  })->name('documents.show');




  //Route::get('/approver/requests/history/{id}',function (){
  ////    $event = Event::query()->findOrFail(request()->id);
  //
  //    return view('requests.history.details', compact('event'));
  //
  //})->name('approver.history.request');

});
