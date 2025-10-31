<?php

use App\Http\Controllers\EventController;
use App\Livewire\EventRequestForm;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Models\Event;
use App\Livewire\Venue\Managers\Show;

Route::get('/', function () {
    return view('home')->layout('layouts.public');
    //return ['Laravel' => app()->version()];
})->name('home');


//Approver Pending------------------------------------------------------------------
Route::get('approver/requests/pending',\App\Livewire\Request\Pending\Approver\Index::class)->name('approver.requests.pending');

Route::get('/approver/requests/pending/{id}',function (){
    $event = Event::query()->findOrFail(request()->id);
    $role = "venue manager";
    if($role === "venue manager"){
        return view('requests.pending.approver.venuemanager.details', compact('event'));
    }
    else{
        return view('requests.pending.approver.details', compact('event'));
    }
})->name('approver.pending.request');

//Approver Request History-----------------------------------------------------------
Route::get('approver/requests/history',function(){
    return view('requests.history.approver.index');
})->name('approver.history.index');

Route::get('/approver/requests/history/{id}',function (){
    $event = Event::query()->findOrFail(request()->id);

    return view('requests.history.approver.details', compact('event'));

})->name('approver.history.request');

//Organization Requests-----------------------------------------------------------------
Route::get('org/requests/',function(){
    return view('requests.org.index');
})->name('org.index');

//Route::get('/request/pending/approver/{id}',App\Livewire\Request\Details::class)->name('approver.requests.details');

Route::get('/org/requests/pending/{id}',function (){
    $event = Event::query()->findOrFail(request()->id);

    return view('requests.org.details', compact('event'));

})->name('org.requests');



//Manage Venues For Venue management---------------------------------------------------------------------------------
Route::get('/venues',\App\Livewire\ManageVenues::class)->name('venues.manage');

Route::get('/venues/requirements/{venue}', \App\Livewire\Venue\Managers\Configure::class)
    ->name('venues.requirements.edit');

//Directors View for assigning managers
Route::get('/department/venues', function (){
   return view('venues.director.venues');
});

//Calendar for public viewing
Route::get('/calendar', function () {
    return view('calendar');
});


//Request Form for creating event request
Route::get('/forms', function () {
    $organization = [
        'id'            => 101, // optional—only keep if you still store org_id on events
        'name'          => 'IEEE UPRM',
        'advisor_name'  => 'Prof. Alice Rivera',
        'advisor_phone' => '787-555-0101',
        'advisor_email' => 'alice.rivera@uprm.edu',
    ];
    return view('requests.create-wrapper',compact('organization'));
});

Route::get('/venues/{venue}', Show::class)->name('venue.show');

require __DIR__.'/saml2.php';
//require __DIR__.'/auth.php';
