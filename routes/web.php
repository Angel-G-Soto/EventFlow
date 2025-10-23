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

//Route::get('/approver', function () {
//    $events = Event::query()
//        ->oldest('created_at')
//        ->paginate(8);
//    return view('requests.pending', compact('events'));
//})->name('approver');

Route::get('/org', function () {
    $events = Event::query()
        ->oldest('created_at')
        ->paginate(8);

    return view('requests.org_history', compact('events'));
});


Route::get('/approver/requests',function(){
    $events = Event::query()
        ->oldest('created_at')
        ->paginate(8);
    return view('requests.pending', compact('events'));
});

Route::get('/approver/requests/{id}',App\Livewire\Request\Details::class)->name('approver.requests.details');

Route::get('/approver/requests/{id}',function (){
    $event = Event::query()->findOrFail(request()->id);

    return view('requests.approve', compact('event'));

})->name('approver.requests.details');


Route::get('/dropdown',function() {
    return view('dropdown');
});

Route::get('/venues/manage',\App\Livewire\ManageVenues::class);

Route::get('/test', function () {
    return view('calendar');
//    return view('components.layouts.auth',[
//        'slot' => 'example',
//    ]);
//    return new \App\Mail\AdvisorEmail([
//        'Event Name'=> 'Resume Workshop',
//        'Event Location'=> 'S-121',
//    ]);
});

Route::get('/forms', function () {
    return view('form');
});

Route::get('/idk',EventRequestForm::class);

require __DIR__.'/saml2.php';
//require __DIR__.'/auth.php';
