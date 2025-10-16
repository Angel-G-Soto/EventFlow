<?php

use Illuminate\Support\Facades\Route;

use Laravel\Socialite\Facades\Socialite;

use Illuminate\Support\Facades\Auth;

use App\Models\User;


Route::get('/auth/saml/login', function () {

    return Socialite::driver('saml2')->redirect();

})->name("saml.login");


Route::any('/auth/callback', function () {

    $saml = Socialite::driver('saml2')->stateless()->user();
//    $saml = Socialite::driver('saml2')->stateless()->user();
    $user = User::updateOrCreate([
        'email' => $saml->getEmail(),
    ], [
        /*'name' => $saml->first_name . ' ' . $saml->last_name,*/
        'first_name' => $saml->first_name,
        'last_name' => $saml->last_name,
        'auth_type' => 'saml2',
        'password' => "thisisatest",
    ]);
    Auth::login($user);
    return redirect('/dashboard');
})->name("saml.callback");

//Route::get('/auth/saml/metadata', function () {
//    return Socialite::driver('saml2')->getServiceProviderMetadata();
//})->name("saml.metadata");
