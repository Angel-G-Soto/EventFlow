<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
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
     * Download a CSV snapshot of the audit log with optional filters.
     *
     * Query params mirror the Livewire filters:
     *  - user       : string|int|null (id, name, or email)
     *  - action     : string|null
     *  - date_from  : date|null (Y-m-d)
     *  - date_to    : date|null (Y-m-d, >= date_from)
     */
    public function download(Request $request): StreamedResponse
    {
        $this->authorizeAccess($request);

        $validated = $request->validate([
            'user'      => ['nullable', 'string', 'max:255'],
            'action'    => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date', 'after_or_equal:date_from'],
            // no limit: export all matching rows
        ]);

        $filters = array_filter([
            'user'      => $validated['user']      ?? null,
            'action'    => $validated['action']    ?? null,
            'date_from' => $validated['date_from'] ?? null,
            'date_to'   => $validated['date_to']   ?? null,
        ], static fn ($v) => $v !== null && $v !== '');

        // Pull all matching logs (no export row limit)
        $pageData = $this->auditService->getPaginatedLogs($filters, PHP_INT_MAX, 1);
        $logs     = $pageData->getCollection();

        $filename = 'audit-log-' . now()->format('Ymd_His') . '.csv';

        // Best-effort audit trail for the export itself
        $actor = $request->user();
        if ($actor && $actor->id) {
            try {
                $meta = [
                    'filters'  => $filters,
                    'rowCount' => $logs->count(),
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

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($logs) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['When', 'User', 'Action', 'Target', 'User Agent', 'IP', 'Meta']);
            $tz = config('app.timezone');
            foreach ($logs as $log) {
                $when = '';
                try {
                    $when = \Carbon\Carbon::parse($log->created_at ?? null)->timezone($tz)->format('Y-m-d H:i:s');
                } catch (\Throwable) {
                    $when = (string) ($log->created_at ?? '');
                }

                $actor = $log->actor ?? null;
                $name = null;
                if ($actor) {
                    $full = trim(($actor->first_name ?? '') . ' ' . ($actor->last_name ?? ''));
                    $name = $full !== '' ? $full : ($actor->name ?? ($actor->email ?? null));
                }
                $userLabel = $name ?? '—';
                if (!empty($log->user_id)) {
                    $userLabel .= ' (#' . $log->user_id . ')';
                }

                $target = ($log->target_type ? class_basename($log->target_type) : '—');
                if (!empty($log->target_id)) {
                    $target .= ' #' . $log->target_id;
                }

                $meta = $log->meta ?? [];
                if (!is_array($meta)) {
                    $decoded = json_decode((string) $meta, true);
                    $meta = is_array($decoded) ? $decoded : [];
                }
                $metaStr = !empty($meta) ? json_encode($meta, JSON_UNESCAPED_SLASHES) : '';

                fputcsv($out, [
                    $when,
                    $userLabel,
                    $log->action ?? '',
                    $target,
                    $log->ua ?? ($log->user_agent ?? ''),
                    $log->ip ?? '',
                    $metaStr,
                ]);
            }
            fclose($out);
        };

        return response()->streamDownload($callback, $filename, $headers);
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
