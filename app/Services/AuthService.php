<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;
use App\Services\AuditService;

/**
 * Class AuthService
 *
 * Centralizes all authentication flows (SAML SSO + Nexo) and session lifecycle.
 * This service intentionally avoids any "impersonation" features.
 *
 * Responsibilities:
 *  - SAML login redirect and callback handling (via Socialite's 'saml2' driver).
 *  - Nexo callback handling using a validated payload (JIT user provisioning).
 *  - Session-safe logout.
 *
 * Contract with UserService:
 *  - Requires: UserService::findOrCreateUser(string $email, string $name): User
 *    (Should create a user if not found, targeting your DB's standard 'email' column.)
 */
class AuthService
{
    public function __construct(
        private readonly UserService $userService, // Provided by your application
        private readonly AuditService $auditService,
    ) {}

    /* -----------------------------------------------------------------
     |  SAML (SSO)
     |------------------------------------------------------------------*/

    /**
     * Initiates the SAML login sequence by redirecting the user to the IdP.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Illuminate\Http\RedirectResponse
     *         A redirect response to the SAML Identity Provider.
     */
    public function samlRedirect()
    {
        // Socialite must be configured with the 'saml2' driver in config/services.php.
        return Socialite::driver('saml2')->redirect();
    }

    /**
     * Handles the SAML callback:
     *  1) Reads the SAML assertion via Socialite.
     *  2) Extracts email + display name (falling back to common attribute keys).
     *  3) JIT-provisions (finds or creates) the local user.
     *  4) Logs the user in (session-based).
     *
     * @param  bool $stateless  Use true if your environment requires stateless flows (e.g., behind certain proxies).
     * @return \App\Models\User The authenticated local user.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException (422) if the SAML assertion lacks an email.
     */
    public function handleSamlCallback(bool $stateless = false): User
    {
        $driver = Socialite::driver('saml2');
        if ($stateless) {
            $driver = $driver->stateless();
        }

        $saml = $driver->user();

        // Prefer standard getters
        $email = (string) $saml->getEmail();
        $name  = $saml->getName();

        // Fall back to common SAML attribute keys if needed
        $raw   = method_exists($saml, 'getRaw') ? (array) $saml->getRaw() : (array) ($saml->user ?? []);
        $first = $raw['first_name'] ?? $raw['givenname'] ?? $raw['given_name'] ?? null;
        $last  = $raw['last_name']  ?? $raw['surname']   ?? $raw['sn']         ?? null;

        if (!$name && ($first || $last)) {
            $name = trim(($first ?? '') . ' ' . ($last ?? '')) ?: $email;
        }
        if (!$email) {
            // Surface a 422 to controllers; customize to your global exception handler if desired.
            abort(422, 'SAML response missing a valid email attribute.');
        }

        /** @var User $user */
        $user = $this->userService->findOrCreateUser($email, $name ?: $email);

        // Audit: if this login provisioned a new local account, log USER_CREATED
        if (property_exists($user, 'wasRecentlyCreated') && $user->wasRecentlyCreated) {
            try {
                $display = trim((string)($user->first_name ?? '') . ' ' . (string)($user->last_name ?? '')) ?: (string)($user->email ?? '');
                $ctx = ['meta' => ['source' => 'saml_login']];
                try {
                    if (function_exists('request') && request()) {
                        $ctx = app(\App\Services\AuditService::class)
                            ->buildContextFromRequest(request(), $ctx['meta']);
                    }
                } catch (\Throwable) { /* no-http context */
                }
                $this->auditService->logAction((int)$user->id, $display, 'USER_CREATED', (string)($user->id ?? 0), $ctx);
            } catch (\Throwable) { /* best-effort */
            }
        }

        // Establish a standard session-based login
        Auth::login($user);

        return $user;
    }

    /* -----------------------------------------------------------------
     |  NEXO
     |------------------------------------------------------------------*/

    /**
     * Session keys for Nexo affiliation context.
     * These are intentionally *not* persisted to local DB tables here.
     * Controllers/Views can read them if they need to show context to the user.
     */
    public const KEY_NEXO_ASSOC_ID        = 'nexo_assoc_id';
    public const KEY_NEXO_ASSOC_NAME      = 'nexo_association_name';
    public const KEY_NEXO_COUNSELOR       = 'nexo_counselor';
    public const KEY_NEXO_COUNSELOR_EMAIL = 'nexo_counselor_email';

    /**
     * Handles a validated Nexo payload:
     *  1) Extracts the user's email + display name from the nested payload.
     *  2) JIT-provisions (finds or creates) the local user.
     *  3) Stores affiliation data in the session ONLY (per spec).
     *  4) Logs the user in (session-based).
     *
     * Expected $payload structure (already validated by NexoAuthRequest):
     *   [
     *     'name'             => string,     // display name (optional for us, but validated upstream)
     *     'email'            => string,     // required - used for local identity
     *     'assoc_id'         => int,
     *     'association_name' => string,
     *     'counselor'        => string|null,
     *     'email_counselor'  => string|null,
     *   ]
     *
     * @param  array $payload Validated Nexo payload (the 'payload' subobject from the request).
     * @return \App\Models\User The authenticated local user.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException (422) if missing email.
     */
    public function handleNexoPayload(array $payload): User
    {
        $email = (string) ($payload['email'] ?? '');
        $name  = trim((string) ($payload['name'] ?? '')) ?: $email;

        if (!$email) {
            abort(422, 'Nexo payload missing email.');
        }

        /** @var User $user */
        $user = $this->userService->findOrCreateUser($email, $name);

        // Audit: if this login provisioned a new local account, log USER_CREATED
        if (property_exists($user, 'wasRecentlyCreated') && $user->wasRecentlyCreated) {
            try {
                $display = trim((string)($user->first_name ?? '') . ' ' . (string)($user->last_name ?? '')) ?: (string)($user->email ?? '');
                $ctx = ['meta' => ['source' => 'nexo_login']];
                try {
                    if (function_exists('request') && request()) {
                        $ctx = app(\App\Services\AuditService::class)
                            ->buildContextFromRequest(request(), $ctx['meta']);
                    }
                } catch (\Throwable) { /* no-http context */
                }
                $this->auditService->logAction((int)$user->id, $display, 'USER_CREATED', (string)($user->id ?? 0), $ctx);
            } catch (\Throwable) { /* best-effort */
            }
        }

        // Store Nexo affiliation context in session only (no DB writes here)
        Session::put(self::KEY_NEXO_ASSOC_ID,        $payload['assoc_id'] ?? null);
        Session::put(self::KEY_NEXO_ASSOC_NAME,      $payload['association_name'] ?? null);
        Session::put(self::KEY_NEXO_COUNSELOR,       $payload['counselor'] ?? null);
        Session::put(self::KEY_NEXO_COUNSELOR_EMAIL, $payload['email_counselor'] ?? null);

        // Establish a standard session-based login
        Auth::login($user);

        return $user;
    }

    /* -----------------------------------------------------------------
     |  COMMON
     |------------------------------------------------------------------*/

    /**
     * Logs out the current user and safely destroys the session.
     * Use this for all flows (local login, SAML, Nexo).
     *
     * @return void
     */
    public function logout(): void
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }
}
