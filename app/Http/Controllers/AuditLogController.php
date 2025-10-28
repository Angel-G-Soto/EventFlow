<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Contracts\View\View;
use Illuminate\Contracts\View\Factory;

/**
 * Class AuditLogController
 *
 * Read-only controller that exposes a paginated, filterable audit trail
 * to high-privilege administrators. It delegates all data access to the
 * AuditService and enforces access via the AuditLogPolicy (Gate).
 *
 * Responsibilities:
 *  - Authorize access to the audit log.
 *  - Parse & validate filter inputs from the request.
 *  - Delegate retrieval to the AuditService.
 *  - Return a view with the paginated results and current filter state.
 *
 * @package App\Http\Controllers\Admin
 */
class AuditLogController extends Controller
{
    /**
     * @param AuditService $auditService Service responsible for retrieving audit data.
     */
    public function __construct(private AuditService $auditService)
    {
        // You may add middleware here if needed (e.g., auth).
        // $this->middleware('auth');
    }

    /**
     * Display the audit log index page with optional filters and pagination.
     *
     * Authorization:
     *  Uses Gate::authorize('view', 'audit-log') which should be bound to AuditLogPolicy@view
     *  in your AuthServiceProvider. Only high-privilege admins should pass.
     *
     * Filters:
     *  - user_id   : (int)   Filter by the ID of the user who performed the action.
     *  - action    : (string)Partial match against action code (e.g., "USER_DELETED").
     *  - date_from : (date)  Inclusive lower bound on created_at.
     *  - date_to   : (date)  Inclusive upper bound on created_at (must be >= date_from).
     *  - per_page  : (int)   Page size (1..200), defaults to 25.
     *
     * Returns a View/Factory contract to keep tests decoupled from Blade rendering.
     *
     * @param  Request  $request
     * @return View|Factory
     */
    public function index(Request $request): View|Factory
    {
        // 1) Policy authorization: throws AuthorizationException if denied.
        Gate::authorize('view', 'audit-log');

        // 2) Validate and normalize filter inputs.
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

        $perPage = (int)($validated['per_page'] ?? 25);

        // 3) Fetch data from the service. The service encapsulates all query logic.
        $logs  = $this->auditService->getPaginatedLogs($filters, $perPage);
        $users = $this->auditService->getAuditedUsers(); // for the dropdown in the view

        // 4) Return the view (no rendering occurs during tests if you mock the View factory).
        return view('admin.audit-log.index', [
            'logs'    => $logs,
            'users'   => $users,
            'filters' => $filters,
            'perPage' => $perPage,
        ]);
    }
}
