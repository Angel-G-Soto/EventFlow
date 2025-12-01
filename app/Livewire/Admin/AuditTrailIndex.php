<?php

namespace App\Livewire\Admin;

use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Services\AuditService;
use Carbon\Carbon;

#[Layout('layouts.app')]
class AuditTrailIndex extends Component
{
    // Filters / query params
    public string $search = '';           // Combined search (user/action/target)
    public ?string $from = null;          // '2025-01-01'
    public ?string $to = null;            // '2025-01-31'
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
        if (in_array($field, ['search', 'from', 'to', 'pageSize'])) {
            $this->page = 1;
        }
    }

    // Filters: clear/reset
    /**
     * Reset all filters to their defaults and reset pagination.
     */
    public function clearFilters(): void
    {
        $this->resetErrorBag(['from', 'to']);
        $this->resetValidation(['from', 'to']);
        $this->reset(['search', 'from', 'to']);
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

    /**
     * Explicit applySearch handler so deferred search inputs submit via button/enter.
     */
    public function applySearch(): void
    {
        $this->page = 1;
    }

    /**
     * Apply the selected date range with validation, mirroring EventsIndex behavior.
     */
    public function applyDateRange(): void
    {
        $this->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        $this->page = 1;
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
            'search' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => [
                'nullable', 'date_format:Y-m-d', 'after_or_equal:from',
                function ($attribute, $value, $fail) {
                    if (! empty($this->from) && ! empty($value) && strtotime($value) < strtotime($this->from)) {
                        $fail('The end date must be after the start date.');
                    }
                },
            ],
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
        $justification = null;
        if (is_array($rawMeta) && array_key_exists('justification', $rawMeta)) {
            $justification = (string) $rawMeta['justification'];
            unset($rawMeta['justification']);
        }

        // Compact target “Class#id” label like your table does
        $targetType = (string) ($log->target_type ?? '');
        $targetId = $log->target_id ?? null;
        $target = $targetType ? class_basename($targetType) : '—';
        if (! empty($targetId)) {
            $target .= ': #'.$targetId;
        }

        // Resolve a human-friendly user label if possible
        $userLabel = null;
        try {
            $actor = method_exists($log, 'actor') ? $log->actor : null;
            if ($actor) {
                $name = trim((string)($actor->first_name ?? '') . ' ' . (string)($actor->last_name ?? ''));
                $userLabel = $name !== ''
                    ? $name
                    : (string)($actor->name ?? ($actor->email ?? ''));
            }
        } catch (\Throwable $e) {
            $userLabel = null;
        }

        return [
            'id' => $log->id ?? null,
            'user_id' => $log->user_id ?? null,
            'user_label' => $userLabel,
            'action' => $log->action ?? '',

            // human-ish target label
            'target' => $target,

            'ua' => $log->user_agent ?? ($log->ua ?? '—'),
            'ip' => (string) ($log->ip ?? 'Unknown'),
            'created_at' => ($log->created_at instanceof \DateTimeInterface)
                ? $log->created_at->format('Y-m-d H:i:s')
                : (is_string($log->created_at ?? null) ? (string) $log->created_at : ''),

            // raw meta payload (pretty-printed in blade)
            'meta' => $rawMeta,
            'justification' => $justification,
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
        if (!empty($this->search)) {
            $filters['q'] = (string) $this->search;
        }
        if ($this->from) {
            try {
                $filters['date_from'] = Carbon::parse($this->from)->startOfDay()->toDateTimeString();
            } catch (\Throwable) {
                $filters['date_from'] = $this->from;
            }
        }
        if ($this->to) {
            try {
                $filters['date_to'] = Carbon::parse($this->to)->endOfDay()->toDateTimeString();
            } catch (\Throwable) {
                $filters['date_to'] = $this->to;
            }
        }

        try {
            $rows = app(AuditService::class)->getPaginatedLogs($filters, $this->pageSize, (int) ($this->page ?? 1));
        } catch (\Throwable $e) {
            // On failure, return an empty paginator and surface a non-fatal error (no dummy data)
            $this->addError('search', 'Audit log unavailable.');
            $empty = collect();
            $rows = new LengthAwarePaginator($empty, 0, $this->pageSize, 1, [
                'path' => request()->url(),
                'query' => request()->query(),
            ]);
        }

        return view('livewire.admin.audit-trail-index', compact('rows'));
    }
}
