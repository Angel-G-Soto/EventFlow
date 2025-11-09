<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Contracts\View\Factory as ViewFactoryContract;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Read-only controller for listing and filtering audit trail entries.
 *
 * Access Control:
 *  - Role-based access to be enforced via authorizeAccess() (see TODO).
 *  - Intention: only "system-admin" (or equivalent) may view this page.
 */
class AuditLogController extends Controller
{
    public function __construct(private AuditService $auditService)
    {
        // When roles/middleware are ready, enable:
        // $this->middleware(['auth', 'role:system-admin']);
    }

    /**
     * Display the audit log index page with optional filters and pagination.
     *
     * Query params:
     *  - user_id   : int|null
     *  - action    : string|null (<=255)
     *  - date_from : date|null (Y-m-d)
     *  - date_to   : date|null (Y-m-d, >= date_from)
     *  - per_page  : int|null (1..200, default 25)
     *
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException (403) once access checks are enabled
     */
    public function index(Request $request): ViewContract|ViewFactoryContract
    {
        // Centralized access hook (currently a no-op)
        $this->authorizeAccess($request);

        $validated = $request->validate([
            'user_id'   => ['nullable', 'integer'],
            'action'    => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page'  => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $filters = [
            'user_id'   => $validated['user_id']   ?? null,
            'action'    => $validated['action']    ?? null,
            'date_from' => $validated['date_from'] ?? null,
            'date_to'   => $validated['date_to']   ?? null,
        ];

        $perPage = (int) ($validated['per_page'] ?? 25);

        $logs  = $this->auditService->getPaginatedLogs($filters, $perPage);
        $users = $this->auditService->getAuditedUsers();

        return view('admin.audit.index', [
            'logs'    => $logs,
            'users'   => $users,
            'filters' => $filters,
            'perPage' => $perPage,
        ]);
    }

    /**
     * Placeholder for future role-based authorization.
     *
     * TODO: Replace with real role check once roles are implemented.
     * Examples of future implementations:
     *  1) Middleware-only (preferred for route-level): $this->middleware(['auth','role:system-admin'])
     *  2) Inline role service:
     *      if (!$this->roleService->userHas($request->user(), 'system-admin')) {
     *          abort(Response::HTTP_FORBIDDEN);
     *      }
     *  3) Simple check on User model (if it gains hasRole()):
     *      if (!$request->user() || !$request->user()->hasRole('system-admin')) {
     *          abort(Response::HTTP_FORBIDDEN);
     *      }
     */
    protected function authorizeAccess(Request $request): void
    {
        // No-op for now to keep development moving.
        // abort(Response::HTTP_FORBIDDEN); // <- enable when wiring roles
    }
}
