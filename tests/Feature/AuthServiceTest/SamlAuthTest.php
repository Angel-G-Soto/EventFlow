<?php

declare(strict_types=1);

namespace Tests\Feature\AuthServiceTest;

use App\Http\Controllers\Auth\AuthController;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\Contracts\Provider as SocialiteProvider;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use Mockery;
use Tests\TestCase;

class SamlAuthTest extends TestCase
{
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Bind controller route (in case your test suite doesn't load routes/saml2.php)
        Route::any('/auth/callback', [AuthController::class, 'samlCallback'])->name('saml.callback');

        // Mock UserService so we avoid any DB writes
        $mockUserService = Mockery::mock(UserService::class);
        $mockUserService->shouldReceive('findOrCreateUser')
            ->once()
            ->andReturnUsing(function (string $email, string $name) {
                $u        = new User();
                $u->id    = 123; // fake ID so Authenticatable is happy
                $u->email = $email;
                $u->name  = $name;
                return $u;
            });
        $this->app->instance(UserService::class, $mockUserService);

        // Mock Socialite 'saml2' driver & returned user
        $socialiteFactory = Mockery::mock(SocialiteFactory::class);
        $provider         = Mockery::mock(SocialiteProvider::class);
        $socialiteUser    = Mockery::mock(SocialiteUserContract::class);

        $socialiteUser->shouldReceive('getEmail')->andReturn('saml_user@example.edu');
        $socialiteUser->shouldReceive('getName')->andReturn('Saml User');
        // Optional: if your code calls getRaw()
        $socialiteUser->shouldReceive('getRaw')->andReturn(['givenname' => 'Saml', 'sn' => 'User']);

        $provider->shouldReceive('user')->once()->andReturn($socialiteUser);

        $socialiteFactory->shouldReceive('driver')->with('saml2')->andReturn($provider);

        // Swap the Socialite factory binding
        $this->app->instance('Laravel\Socialite\Contracts\Factory', $socialiteFactory);
    }

    public function test_saml_callback_logs_in_and_redirects_to_calendar(): void
    {
        $response = $this->call('GET', route('saml.callback'));

        $response->assertRedirect('/calendar');
        $this->assertTrue(Auth::check(), 'User should be authenticated after SAML callback.');
        $this->assertSame('saml_user@example.edu', Auth::user()->email);
    }
}
