<?php

declare(strict_types=1);

namespace Tests\Feature\AuthServiceTest;

use App\Http\Controllers\Auth\AuthController;
use App\Models\User;
use App\Services\AuthService;
use App\Services\UserService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\Contracts\Provider as SocialiteProvider;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use Mockery;
use Tests\TestCase;

/**
 * Class AuthFlowsTest
 *
 * End-to-end (feature) tests for the authentication flows plus a light unit test.
 *
 * What’s covered:
 *  - SAML callback flow:
 *      * Mocks Socialite 'saml2' driver and returned user
 *      * Calls /auth/callback and asserts user is authenticated and redirected to /calendar
 *  - Nexo callback flow (validated by ImportRequest):
 *      * Valid payload path → authenticates, sets session context, redirects to /events/create
 *      * Invalid source_id path → fails validation (no auth)
 *      * Missing payload.email path → fails validation (no auth)
 *  - Unit-ish test of AuthService::handleNexoPayload (no HTTP):
 *      * Ensures session keys and Auth are set correctly
 *
 * Notes:
 *  - We mock UserService::findOrCreateUser to avoid real DB writes.
 *  - If your test kernel already loads routes/saml2.php, the inline routes
 *    in setUp() are harmless; remove them if you prefer.
 *  - If your SAML flow calls ->stateless(), we mock that too.
 */
class AuthServiceTestFlow extends TestCase
{
    /**
     * Bind minimal routes if your test kernel isn't loading routes/saml2.php.
     * We only need:
     *  - /auth/callback  (SAML)
     *  - /auth/nexo/callback (Nexo)
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (! Route::has('saml.callback')) {
            Route::any('/auth/callback', [AuthController::class, 'samlCallback'])->name('saml.callback');
        }
        if (! Route::has('nexo.callback')) {
            Route::any('/auth/nexo/callback', [AuthController::class, 'nexoCallback'])->name('nexo.callback');
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ---------------------------------------------------------------------
    // SAML callback: feature flow (Socialite mocked)
    // ---------------------------------------------------------------------

    /**
     * SAML: ensures that when the IdP returns a user, we:
     *  - extract email/name,
     *  - log in the user,
     *  - redirect to /calendar (per requirement).
     */
    public function test_saml_callback_logs_in_and_redirects_to_calendar(): void
    {
        // Mock UserService::findOrCreateUser to avoid DB writes.
        $mockUserService = Mockery::mock(UserService::class);
        $mockUserService->shouldReceive('findOrCreateUser')
            ->once()
            ->andReturnUsing(function (string $email, string $name) {
                $u        = new User();
                $u->id    = 123;                // give it an ID so Auth::login() is happy
                $u->email = $email;
                $u->name  = $name;
                return $u;
            });
        $this->app->instance(UserService::class, $mockUserService);

        // Mock Socialite pipeline: Socialite::driver('saml2')->[stateless()->]user()
        $factory  = Mockery::mock(SocialiteFactory::class);
        $provider = Mockery::mock(SocialiteProvider::class);
        $samlUser = Mockery::mock(SocialiteUserContract::class);

        // Provide the attributes the service reads
        $samlUser->shouldReceive('getEmail')->andReturn('saml_user@example.edu');
        $samlUser->shouldReceive('getName')->andReturn('Saml User');
        // If code calls ->getRaw(), return common SAML keys
        $samlUser->shouldReceive('getRaw')->andReturn(['givenname' => 'Saml', 'sn' => 'User']);

        // If your code calls ->stateless(), have it return the provider itself
        $provider->shouldReceive('stateless')->andReturn($provider);
        $provider->shouldReceive('user')->once()->andReturn($samlUser);

        $factory->shouldReceive('driver')->with('saml2')->andReturn($provider);

        // Swap the Socialite factory binding
        $this->app->instance('Laravel\Socialite\Contracts\Factory', $factory);

        // Exercise the route
        $response = $this->call('GET', route('saml.callback'));

        // Assert redirect location and authenticated user
        $response->assertRedirect('/calendar');
        $this->assertTrue(Auth::check(), 'Expected user to be authenticated after SAML callback.');
        $this->assertSame('saml_user@example.edu', Auth::user()->email);
    }

    // ---------------------------------------------------------------------
    // Nexo callback: feature flows (ImportRequest validation + session)
    // ---------------------------------------------------------------------

    /**
     * Nexo (valid path): with a valid source_id and payload,
     * we authenticate via payload.email, set session context, and redirect to /events/create.
     */
    public function test_nexo_callback_valid_payload_logs_in_sets_session_and_redirects(): void
    {
        // ImportRequest validates source_id against this list
        Config::set('sources.allowed_sources', ['test-nexo-source']);

        // Mock UserService::findOrCreateUser to avoid DB writes.
        $mockUserService = Mockery::mock(UserService::class);
        $mockUserService->shouldReceive('findOrCreateUser')
            ->once()
            ->andReturnUsing(function (string $email, string $name) {
                $u        = new User();
                $u->id    = 456;
                $u->email = $email;
                $u->name  = $name;
                return $u;
            });
        $this->app->instance(UserService::class, $mockUserService);

        // Valid request body per ImportRequest
        $requestBody = [
            'source_id' => 'test-nexo-source',
            'payload' => [
                'name'               => 'Ada Lovelace',
                'email'              => 'ada@example.edu',
                'assoc_id'           => 9876,
                'association_name'   => 'IEEE UPRM',
                'counselor'          => 'Dr. Rivera',
                'email_counselor'    => 'dr.rivera@example.edu',
            ],
        ];

        $response = $this->call('POST', route('nexo.callback'), $requestBody);

        $response->assertRedirect('/events/create');
        $this->assertTrue(Auth::check(), 'Expected user to be authenticated after Nexo callback.');
        $this->assertSame('ada@example.edu', Auth::user()->email);

        // Assert session keys written by AuthService::handleNexoPayload()
        $response->assertSessionHasAll([
            'nexo_assoc_id'         => 9876,
            'nexo_association_name' => 'IEEE UPRM',
            'nexo_counselor'        => 'Dr. Rivera',
            'nexo_counselor_email'  => 'dr.rivera@example.edu',
        ]);
    }

    /**
     * Nexo (invalid source): ImportRequest should reject unknown source_id.
     * We expect validation errors and no authentication.
     */
    public function test_nexo_callback_rejects_unallowed_source(): void
    {
        Config::set('sources.allowed_sources', ['test-nexo-source']);

        // Use an unknown source_id to trigger validation error.
        $badBody = [
            'source_id' => 'unknown-source',
            'payload' => [
                'name' => 'X',
                'email' => 'x@example.edu',
                'assoc_id' => 1,
                'association_name' => 'A',
            ],
        ];

        $response = $this->call('POST', route('nexo.callback'), $badBody);

        $response->assertSessionHasErrors(['source_id']);
        $this->assertFalse(Auth::check(), 'User should not be authenticated when source_id is invalid.');
    }

    /**
     * Nexo (missing email): ImportRequest requires payload.email.
     * We expect a validation error and no authentication.
     */
    public function test_nexo_callback_requires_email(): void
    {
        Config::set('sources.allowed_sources', ['test-nexo-source']);

        // Omit payload.email to trigger validation error.
        $missingEmail = [
            'source_id' => 'test-nexo-source',
            'payload' => [
                'name' => 'No Email',
                'assoc_id' => 22,
                'association_name' => 'Assoc',
            ],
        ];

        $response = $this->call('POST', route('nexo.callback'), $missingEmail);

        $response->assertSessionHasErrors(['payload.email']);
        $this->assertFalse(Auth::check(), 'User should not be authenticated when payload.email is missing.');
    }

    // ---------------------------------------------------------------------
    // Unit-ish coverage: AuthService::handleNexoPayload (no HTTP)
    // ---------------------------------------------------------------------

    /**
     * Directly exercises AuthService::handleNexoPayload to confirm it:
     *  - calls UserService::findOrCreateUser with payload.email/name,
     *  - logs the user in,
     *  - writes the expected session keys for affiliation context.
     */
    public function test_auth_service_handle_nexo_payload_logs_in_and_sets_session(): void
    {
        // Mock the dependency (UserService) and inject it into AuthService
        $mockUserService = Mockery::mock(UserService::class);
        $mockUserService->shouldReceive('findOrCreateUser')
            ->once()
            ->with('neo@example.edu', 'Neo')
            ->andReturnUsing(function () {
                $u        = new User();
                $u->id    = 999;
                $u->email = 'neo@example.edu';
                $u->name  = 'Neo';
                return $u;
            });

        $service = new AuthService($mockUserService);

        // Call the method under test
        $user = $service->handleNexoPayload([
            'name'               => 'Neo',
            'email'              => 'neo@example.edu',
            'assoc_id'           => 777,
            'association_name'   => 'Matrix Assoc',
            'counselor'          => 'Morpheus',
            'email_counselor'    => 'morpheus@zion.net',
        ]);

        // Assert returned user and framework auth state
        $this->assertSame('neo@example.edu', $user->email);
        $this->assertTrue(Auth::check(), 'Expected AuthService to log the user in.');
        $this->assertSame('neo@example.edu', Auth::user()->email);

        // Assert session context keys
        $this->assertSame(777, Session::get('nexo_assoc_id'));
        $this->assertSame('Matrix Assoc', Session::get('nexo_association_name'));
        $this->assertSame('Morpheus', Session::get('nexo_counselor'));
        $this->assertSame('morpheus@zion.net', Session::get('nexo_counselor_email'));
    }
}
