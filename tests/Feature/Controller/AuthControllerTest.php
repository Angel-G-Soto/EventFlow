<?php

declare(strict_types=1);

namespace Tests\Feature\Controller;

use App\Http\Controllers\Auth\AuthController;
use App\Http\Requests\ImportRequest;
use App\Models\User;                 // <-- IMPORTANT: use your Eloquent User
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Mockery;
use Tests\TestCase;

/**
 * Class AuthControllerTest
 *
 * Purpose
 * -------
 * Unit-test the AuthController methods in isolation from the HTTP kernel,
 * middleware, routing, and external providers. We verify that:
 *
 *  1) samlRedirect()
 *     - delegates to AuthService::samlRedirect()
 *     - returns the same RedirectResponse (e.g., to the IdP)
 *
 *  2) samlCallback()
 *     - calls AuthService::handleSamlCallback(false)
 *     - returns a RedirectResponse to /calendar
 *
 *  3) nexoCallback()
 *     - reads a validated payload from ImportRequest::validated()
 *     - calls AuthService::handleNexoPayload($payload)
 *     - returns a RedirectResponse to /events/create
 *
 * Notes
 * -----
 * - These are controller *unit* tests, not feature tests: they do not spin up routes.
 * - We mock AuthService and ImportRequest to control inputs and verify interactions.
 * - AuthService methods are type-hinted to return App\Models\User, so mocks must
 *   return a real User model instance (not stdClass), or PHP will throw a TypeError.
 */
class AuthControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Helper: bind the mocked AuthService and resolve the controller from the container.
     */
    private function makeController($authServiceMock): AuthController
    {
        $this->app->instance(AuthService::class, $authServiceMock);
        return $this->app->make(AuthController::class);
    }

    public function test_samlRedirect_uses_service_and_returns_redirect(): void
    {
        // Arrange
        $service = Mockery::mock(AuthService::class);
        $service->shouldReceive('samlRedirect')
            ->once()
            ->andReturn(redirect()->away('https://idp.example/saml/login'));

        $controller = $this->makeController($service);

        // Act
        $resp = $controller->samlRedirect();

        // Assert
        $this->assertInstanceOf(RedirectResponse::class, $resp);
        $this->assertSame('https://idp.example/saml/login', $resp->getTargetUrl());
    }

    public function test_samlCallback_calls_service_and_redirects_to_calendar(): void
    {
        // Arrange: AuthService::handleSamlCallback(false) must return a User model
        $service = Mockery::mock(AuthService::class);
        $service->shouldReceive('handleSamlCallback')
            ->once()
            ->withArgs(fn (bool $stateless) => $stateless === false)
            ->andReturnUsing(function () {
                $u = new User();
                $u->id = 1;           // give it an ID so it's a plausible Eloquent model
                $u->email = 'saml_user@example.edu';
                $u->name = 'Saml User';
                return $u;            // <-- return App\Models\User (not stdClass)
            });

        $controller = $this->makeController($service);

        // Act
        $resp = $controller->samlCallback();

        // Assert
        $this->assertInstanceOf(RedirectResponse::class, $resp);
        $this->assertStringEndsWith('/calendar', $resp->getTargetUrl());
    }

    public function test_nexoCallback_uses_import_request_validated_payload_and_redirects_to_events_create(): void
    {
        // Arrange: the validated structure ImportRequest would produce
        $validated = [
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

        // Mock the FormRequest so controller receives our validated data
        $request = Mockery::mock(ImportRequest::class);
        $request->shouldReceive('validated')
            ->once()
            ->andReturn($validated);

        // AuthService::handleNexoPayload($payload) must return a User model
        $service = Mockery::mock(AuthService::class);
        $service->shouldReceive('handleNexoPayload')
            ->once()
            ->with($validated['payload'])
            ->andReturnUsing(function () {
                $u = new User();
                $u->id = 2;
                $u->email = 'ada@example.edu';
                $u->name = 'Ada Lovelace';
                return $u;            // <-- return App\Models\User (not stdClass)
            });

        $controller = $this->makeController($service);

        // Act
        $resp = $controller->nexoCallback($request);

        // Assert
        $this->assertInstanceOf(RedirectResponse::class, $resp);
        $this->assertStringEndsWith('/events/create', $resp->getTargetUrl());
    }
}
