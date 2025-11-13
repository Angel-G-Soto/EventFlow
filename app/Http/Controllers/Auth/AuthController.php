<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportRequest; //
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

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

    private function redirectAfterLogin() // NEW
    {
        $user = Auth::user();

        // pull roles from relation or accessor; if none, treat as empty array
        $roles = method_exists($user, 'roles')
            ? $user->roles->pluck('name')->all()
            : (array) ($user->roles ?? []);

        if (count($roles) <= 1) {
            // single role → set it and go directly to its home
            $active = $roles[0] ?? null;
            if ($active) session(['active_role' => $active]);

            // pick a sensible landing by role
            return match ($active) {
                'System Admin'          => redirect()->route('admin.events'),
                'Department Director'   => redirect()->route('admin.departments'),
                'Venue Manager'         => redirect()->route('admin.venues'),
                'DSCA Staff'            => redirect()->route('admin.events'),
                default                 => redirect()->route('calendar.public'),
            };
        }

        // multi-role → let the user choose
        return redirect()->route('choose-role');
    }
    /** SAML callback: authenticate then send to /calendar. */
    public function samlCallback(): RedirectResponse
    {
        $this->auth->handleSamlCallback(stateless: false);
        return $this->redirectAfterLogin(); // NEW    
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

        return $this->redirectAfterLogin(); // NEW
    }
}
