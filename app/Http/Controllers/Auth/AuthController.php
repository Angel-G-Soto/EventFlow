<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportRequest; //
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;

/**
 * Unifies SAML (SSO) and Nexo endpoints.
 * Nexo validation leverages your existing ImportRequest.
 */
class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth) {}

    /* -----------------------------------------------------------------
     |  SAML (SSO)
     |------------------------------------------------------------------*/

    /** Redirects to the SAML IdP (Identity Provider). */
    public function samlRedirect(): RedirectResponse
    {
        return $this->auth->samlRedirect();
    }

    /** SAML callback: authenticate then send to /calendar. */
    public function samlCallback(): RedirectResponse
    {
        $this->auth->handleSamlCallback(stateless: false);
        return redirect()->intended('/calendar');
    }

    /* -----------------------------------------------------------------
     |  NEXO
     |------------------------------------------------------------------*/

    /**
     * Nexo callback: validates using ImportRequest, authenticates via payload.email,
     * then redirects to /events/create.
     *
     * ImportRequest rules expect:
     *  - source_id ∈ config('sources.allowed_sources', [])
     *  - payload => { name, email, assoc_id, association_name, counselor?, email_counselor? }
     */
    public function nexoCallback(ImportRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $payload   = $validated['payload'];

        $this->auth->handleNexoPayload($payload);

        return redirect()->intended('/events/create');
    }
}
