<?php

namespace App\Livewire\Admin;

use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Services\AuditService;

#[Layout('layouts.app')]
class AuditTrailIndex extends Component
{
    // Filters / query params
    public ?int $userId = null;
    public string $action = '';           // e.g. 'USER_UPDATE'
    public ?string $from = null;          // '2025-01-01'
    public ?string $to = null;            // '2025-01-31'
    public bool $adminOnly = false;       // only ADMIN_* actions
    public int $pageSize = 25;

    // Pagination state
    public int $page = 1;

    // View state
    public ?int $detailsId = null;        // for modal
    public array $details = [];

    // Filter reactions
    /**
     * Keep pagination in sync when filter fields change.
     *
     * @param string $field The Livewire-updated field name.
     */
    public function updated($field)
    {
        if (in_array($field, ['userId', 'action', 'from', 'to', 'adminOnly', 'pageSize'])) {
            $this->page = 1;
        }
    }

    // Filters: clear/reset
    /**
     * Reset all filters to their defaults and reset pagination.
     */
    public function clearFilters(): void
    {
        $this->reset(['userId', 'action', 'from', 'to', 'adminOnly']);
        $this->pageSize = 25;
        $this->page = 1;
    }

    /**
     * Navigate to a specific page number from the shared pagination partial.
     */
    public function goToPage(int $target): void
    {
        $this->page = max(1, $target);
    }

  // Validation rules
  /**
   * Validation rules for filter and view state properties.
   * Keeping this present aligns with other admin views and enables
   * centralized validation when needed.
   *
   * @return array<string, string|array<int,string>>
   */
    protected function rules(): array
    {
        return [
            'userId' => ['nullable', 'integer', 'min:1'],
            'action' => ['nullable', 'string', 'max:100', 'not_regex:/^\\s*$/'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => [
                'nullable', 'date_format:Y-m-d', 'after_or_equal:from',
                function ($attribute, $value, $fail) {
                    if (! empty($this->from) && ! empty($value) && strtotime($value) < strtotime($this->from)) {
                        $fail('The end date must be after the start date.');
                    }
                },
            ],
            'adminOnly' => ['boolean'],
            'pageSize' => ['integer', 'in:25,50,100'],
            'detailsId' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function showDetails(int $id): void
    {
        $this->authorize('access-dashboard');

        if ($id <= 0) {
            $this->detailsId = null;
            $this->details = [];

            return;
        }

        /** @var AuditService $svc */
        $svc = app(AuditService::class);
        $log = $svc->getLogById($id);  // returns AuditTrail model (service layer), not used directly in views

        if (! $log) {
            $this->detailsId = null;
            $this->details = [];
            // Optionally: flash or toast could be dispatched here

            return;
        }

        $this->detailsId = $id;
        $this->details = $this->mapAuditToDetails($log);

        // open modal like the Venues view does
        $this->dispatch('bs:open', id: 'auditDetails');
    }

    private function mapAuditToDetails(object $log): array
    {
        // robust meta parsing
        $rawMeta = $log->meta ?? [];
        if (! is_array($rawMeta)) {
            $decoded = json_decode((string) $rawMeta, true);
            $rawMeta = is_array($decoded) ? $decoded : [];
        }

        // Compact target “Class#id” label like your table does
        $targetType = (string) ($log->target_type ?? '');
        $targetId = $log->target_id ?? null;
        $target = $targetType ? class_basename($targetType) : '—';
        if (! empty($targetId)) {
            $target .= ': #'.$targetId;
        }

        return [
            'id' => $log->id ?? null,
            'user_id' => $log->user_id ?? null,
            'action' => $log->action ?? '',

            // human-ish target label
            'target' => $target,

            'ua' => $log->user_agent ?? ($log->ua ?? '—'),
            'ip' => (string) ($log->ip ?? 'Unknown'),
            'created_at' => optional($log->created_at ?? null)->format('Y-m-d H:i:s')
                ?: (is_string($log->created_at ?? null) ? $log->created_at : ''),

            // raw meta payload (pretty-printed in blade)
            'meta' => $rawMeta,
        ];
    }

    // Filtering helper

    // Render
    /**
     * Render the Audit Trail list view.
     */
    public function render()
    {
        $this->authorize('access-dashboard');

        try {
            $this->validate();
        } catch (\Throwable $e) {
            // If validation fails, return an empty paginator with errors surfaced
            $empty = collect();
            $rows = new LengthAwarePaginator($empty, 0, $this->pageSize, 1, [
                'path' => request()->url(),
                'query' => request()->query(),
            ]);

            return view('livewire.admin.audit-trail-index', compact('rows'));
        }

        $filters = [];
        if ($this->userId) {
            $filters['user_id'] = (int) $this->userId;
        }
        if ($this->action) {
            $filters['action'] = $this->action;
        }
        if ($this->from) {
            $filters['date_from'] = $this->from;
        }
        if ($this->to) {
            $filters['date_to'] = $this->to;
        }

        try {
            $rows = app(AuditService::class)->getPaginatedLogs($filters, $this->pageSize, (int) ($this->page ?? 1));
        } catch (\Throwable $e) {
            // On failure, return an empty paginator and surface a non-fatal error (no dummy data)
            $this->addError('userId', 'Audit log unavailable.');
            $empty = collect();
            $rows = new LengthAwarePaginator($empty, 0, $this->pageSize, 1, [
                'path' => request()->url(),
                'query' => request()->query(),
            ]);
        }

        return view('livewire.admin.audit-trail-index', compact('rows'));
    }
}
