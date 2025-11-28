<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\Factory as ViewFactoryContract;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
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
     * Download a PDF snapshot of the audit log with optional filters.
     *
     * Query params mirror the Livewire filters:
     *  - user       : string|int|null (id, name, or email)
     *  - action     : string|null
     *  - date_from  : date|null (Y-m-d)
     *  - date_to    : date|null (Y-m-d, >= date_from)
     *  - limit      : int|null (1..2000, default 500)
     */
    public function download(Request $request): StreamedResponse
    {
        $this->authorizeAccess($request);

        $validated = $request->validate([
            'user'      => ['nullable', 'string', 'max:255'],
            'action'    => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date', 'after_or_equal:date_from'],
            'limit'     => ['nullable', 'integer', 'min:1', 'max:2000'],
        ]);

        $filters = array_filter([
            'user'      => $validated['user']      ?? null,
            'action'    => $validated['action']    ?? null,
            'date_from' => $validated['date_from'] ?? null,
            'date_to'   => $validated['date_to']   ?? null,
        ], static fn ($v) => $v !== null && $v !== '');

        $limit    = (int) ($validated['limit'] ?? 500);
        $perPage  = max(1, min($limit, 2000));
        /** @var \Illuminate\Pagination\LengthAwarePaginator $pageData */
        $pageData = $this->auditService->getPaginatedLogs($filters, $perPage, 1);
        $logs     = $pageData->getCollection();

        $filename = 'audit-log-' . now()->format('Ymd_His') . '.pdf';

        // Best-effort audit trail for the export itself
        $actor = $request->user();
        if ($actor && $actor->id) {
            try {
                $meta = [
                    'filters'  => $filters,
                    'rowCount' => $logs->count(),
                    'limit'    => $perPage,
                    'filename' => $filename,
                ];
                $ctx = $this->auditService->buildContextFromRequest($request, $meta);
                $this->auditService->logAdminAction(
                    (int) $actor->id,
                    'audit_log',
                    'AUDIT_LOG_EXPORTED',
                    $filename,
                    $ctx
                );
            } catch (\Throwable) {
                // do not block download on audit failure
            }
        }

        $html = view('admin.audit.pdf', [
            'logs'    => $logs,
            'filters' => $filters,
        ])->render();

        $pdf = Pdf::loadHTML($html)->setPaper('letter', 'landscape');

        return response()->streamDownload(
            static function () use ($pdf) {
                echo $pdf->output();
            },
            $filename,
            ['Content-Type' => 'application/pdf']
        );
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
