<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| SAML & Nexo Auth Routes (Single Controller)
|--------------------------------------------------------------------------
| Register this file from routes/web.php or RouteServiceProvider:
|   require base_path('routes/saml2.php');
|
| Notes:
| - Keep these outside of 'auth' middleware (these endpoints establish auth).
| - If Nexo always POSTs from a server-to-server call, prefer ->post() instead of ->any().
| - Attach API key/signature middleware to the Nexo route when integrating.
*/

//Route::get('/auth/saml/login', [AuthController::class, 'samlRedirect'])
//    ->name('saml.login');
//
//Route::any('/auth/callback', [AuthController::class, 'samlCallback'])
//    ->name('saml.callback');
//
//Route::any('/auth/nexo/callback', [AuthController::class, 'nexoCallback'])
//    // ->middleware('verify.apikey') // uncomment to use API-key middleware when integrating
//    ->name('nexo.callback');

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
    return redirect('/');
})->name("saml.callback");
