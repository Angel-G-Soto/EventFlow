<?php

use App\Http\Controllers\EventController;
use App\Livewire\EventRequestForm;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Models\Event;

Route::get('/', function () {
    return view('home')->layout('layouts.public');
    //return ['Laravel' => app()->version()];
})->name('home');


//Route::get('/org', function () {
//    $events = Event::query()
//        ->oldest('created_at')
//        ->paginate(8);
//
//    return view('requests.org_history', compact('events'));
//});

Route::get('approver/requests/pending',function(){
    $events = Event::query()
        ->oldest('created_at')
        ->paginate(8);
//    dd($events);
    return view('requests.pending.approver.index', compact('events'));
})->name('approver.index');

Route::get('org/requests/',function(){

    $events = Event::query()
        ->oldest('created_at')
        ->paginate(8);

    return view('requests.org.index');
})->name('org.index');

//Route::get('/request/pending/approver/{id}',App\Livewire\Request\Details::class)->name('approver.requests.details');

Route::get('/approver/requests/pending/{id}',function (){
    $event = Event::query()->findOrFail(request()->id);

    return view('requests.pending.approver.details', compact('event'));

})->name('approver.requests');

Route::get('/org/requests/pending/{id}',function (){
    $event = Event::query()->findOrFail(request()->id);

    return view('requests.org.details', compact('event'));

})->name('org.requests');




Route::get('/venues/manage',\App\Livewire\ManageVenues::class);

Route::get('/venues/{venue}/requirements', \App\Livewire\Venue\Managers\Configure::class)
    ->name('venues.requirements.edit');

Route::get('/department/venues', function (){
   return view('venues.director.venues');
});

Route::get('/calendar', function () {
    return view('calendar');
});



Route::get('/forms', function () {
    return view('form');
});



require __DIR__.'/saml2.php';
//require __DIR__.'/auth.php';
