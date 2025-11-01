<?php

namespace App\Livewire\Admin;

use App\Models\AuditTrail;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class AuditTrailIndex extends Component
{
  use WithPagination;

  // Filters
  public ?int $userId = null;
  public string $action = '';           // e.g. 'USER_UPDATE'
  public ?string $from = null;          // '2025-01-01'
  public ?string $to = null;            // '2025-01-31'
  public bool $adminOnly = false;       // only ADMIN_* actions
  public int $perPage = 25;

  // View state
  public ?int $detailsId = null;        // for modal
  public array $details = [];

  /**
   * Keep pagination in sync when filter fields change.
   *
   * @param string $field The Livewire-updated field name.
   */
  public function updated($field)
  {
    if (in_array($field, ['userId', 'action', 'from', 'to', 'adminOnly', 'perPage'])) {
      $this->resetPage();
    }
  }

  /**
   * Reset all filters to their defaults and reset pagination.
   */
  public function clearFilters(): void
  {
    $this->reset(['userId', 'action', 'from', 'to', 'adminOnly']);
    $this->perPage = 25;
    $this->resetPage();
  }

  /**
   * Populate and show the details modal for the given audit record.
   *
   * Works with both demo (no DB) and real DB rows.
   *
   * @param int $auditId The audit record identifier to display.
   */
  public function showDetails(int $auditId): void
  {
    // If we're in demo mode (no rows in DB), pull details from dummy rows
    if (!Schema::hasTable('audit_trail') || self::filterAuditTrail(AuditTrail::query())->count() === 0) {
      $r = $this->dummyRows()->firstWhere('audit_id', $auditId);
      if (!$r) return;
      $this->detailsId = $auditId;
      $this->details = [
        'audit_id'   => $r->audit_id,
        'user_id'    => $r->user_id ?? null,
        'a_action'   => $r->a_action ?? null,
        'a_target'   => ($r->a_target_type ?? '—') . '#' . ($r->a_target_id ?? '—'),
        'ip'         => $r->ip ?? null,
        'method'     => $r->method ?? null,
        'path'       => $r->path ?? null,
        'ua'         => $r->ua ?? null,
        'meta'       => [],
        'created_at' => $r->a_created_at ?? null,
      ];
      $this->dispatch('bs:open', id: 'auditDetails');
      return;
    }

    // Otherwise, resolve the correct id column dynamically to avoid missing column errors
    $idColumn = $this->resolveIdColumn();
    if (!$idColumn) return;

    $row = self::filterAuditTrail(AuditTrail::query())->where($idColumn, $auditId)->first();
    if (!$row) return;

    $action = $row->a_action ?? $row->at_action ?? $row->action ?? null;
    $targetType = $row->a_target_type ?? $row->target_type ?? null;
    $targetId = $row->a_target_id ?? $row->target_id ?? null;
    $createdAt = $row->a_created_at ?? $row->created_at ?? null;
    $ua = $row->ua ?? null;
    $meta = $row->meta ?? [];
    if (is_string($meta)) {
      $decoded = json_decode($meta, true);
      $meta = json_last_error() === JSON_ERROR_NONE ? $decoded : ['raw' => $meta];
    }

    $this->detailsId = $auditId;
    $this->details = [
      'audit_id'   => $auditId,
      'user_id'    => $row->user_id ?? null,
      'a_action'   => $action,
      'a_target'   => ($targetType ?? '—') . '#' . ($targetId ?? '—'),
      'ip'         => $row->ip ?? null,
      'method'     => $row->method ?? null,
      'path'       => $row->path ?? null,
      'ua'         => $ua,
      'meta'       => is_array($meta) ? $meta : [],
      'created_at' => $createdAt ? (string) $createdAt : null,
    ];
    $this->dispatch('bs:open', id: 'auditDetails'); // Bootstrap modal opener
  }

  /**
   * Resolve the primary key column name for the audit_trail table.
   *
   * @return string|null The column name if found, otherwise null.
   */
  private function resolveIdColumn(): ?string
  {
    foreach (['audit_id', 'id', 'at_id'] as $col) {
      if (Schema::hasColumn('audit_trail', $col)) {
        return $col;
      }
    }
    return null;
  }

  /**
   * Build the base query with aliased columns to normalize schema variations.
   *
   * @return \Illuminate\Database\Eloquent\Builder
   */
  protected function baseQuery()
  {
    // Alias common column variants to what the view expects.
    // This lets us work whether the table uses columns like
    //  - audit_id / a_action / a_created_at, or
    //  - id / action / created_at (migration defaults), or
    //  - at_id / at_action (older naming).
    return self::filterAuditTrail(AuditTrail::query())
      ->selectRaw(
        "COALESCE(audit_id, at_id, id)               as audit_id, " .
          "user_id, " .
          "COALESCE(a_action, at_action, action)       as a_action, " .
          "COALESCE(a_target_type, target_type)        as a_target_type, " .
          "COALESCE(a_target_id, target_id)            as a_target_id, " .
          "ip, method, path, " .
          "COALESCE(a_created_at, created_at)          as a_created_at"
      );
  }

  /**
   * Build in-memory dummy rows when there is no database data yet.
   */
  protected function dummyRows(): Collection
  {
    $now = now();
    $actions = ['USER_CREATE', 'USER_UPDATE', 'VENUE_CREATE', 'VENUE_UPDATE', 'EVENT_SUBMIT', 'EVENT_APPROVE', 'ADMIN_DELETE'];
    $methods = ['GET', 'POST', 'PUT', 'DELETE'];
    $targets = [
      ['App\\Models\\User', 12],
      ['App\\Models\\User', 34],
      ['App\\Models\\Venue', 5],
      ['App\\Models\\Event', 99],
    ];

    $rows = [];
    for ($i = 1; $i <= 30; $i++) {
      $t = $targets[array_rand($targets)];
      $rows[] = (object) [
        'audit_id'      => $i,
        'user_id'       => rand(1, 10),
        'a_action'      => $actions[array_rand($actions)],
        'a_target_type' => $t[0],
        'a_target_id'   => $t[1],
        'ip'            => "192.168.1." . rand(2, 254),
        'method'        => $methods[array_rand($methods)],
        'path'          => '/api/example/' . $i,
        'a_created_at'  => $now->copy()->subMinutes($i * 7)->toDateTimeString(),
      ];
    }
    return collect($rows);
  }

  /**
   * Render the audit trail index view using either dummy data or DB records.
   *
   * @return \Illuminate\Contracts\View\View
   */

  /**
   * Apply all filters to the audit trail query.
   */
  public function filterAuditTrail($query)
  {
    if (!empty($this->searchTerm)) {
      $query->where('action', 'like', '%' . $this->searchTerm . '%')
        ->orWhere('user_name', 'like', '%' . $this->searchTerm . '%');
    }

    if (!empty($this->dateRange)) {
      $query->whereBetween('created_at', $this->dateRange);
    }

    return $query;
  }


  /**
   * Render the Audit Trail list view.
   */
  public function render()
  {
    // If table is missing or empty, serve in-memory dummy data for development.
    if (!Schema::hasTable('audit_trail') || self::filterAuditTrail(AuditTrail::query())->count() === 0) {
      $data = $this->dummyRows()
        ->when($this->userId, fn($c) => $c->where('user_id', $this->userId))
        ->when($this->action !== '', fn($c) => $c->where('a_action', $this->action))
        ->when($this->adminOnly, fn($c) => $c->filter(fn($r) => str_starts_with($r->a_action, 'ADMIN_')))
        ->when($this->from, function ($c) {
          $from = CarbonImmutable::parse($this->from)->startOfDay();
          return $c->where('a_created_at', '>=', $from->toDateTimeString());
        })
        ->when($this->to, function ($c) {
          $to = CarbonImmutable::parse($this->to)->endOfDay();
          return $c->where('a_created_at', '<=', $to->toDateTimeString());
        })
        ->sortByDesc('a_created_at')
        ->values();

      $page = LengthAwarePaginator::resolveCurrentPage();
      $items = $data->slice(($page - 1) * $this->perPage, $this->perPage)->values();
      $rows = new LengthAwarePaginator($items, $data->count(), $this->perPage, $page, [
        'path' => request()->url(),
        'query' => request()->query(),
      ]);
      return view('livewire.admin.audit-trail-index', compact('rows'));
    }

    $searchTerm = $this->baseQuery()
      ->when($this->userId, fn($searchTerm) => $searchTerm->where('user_id', $this->userId))
      // Filter on real column; alias is used only for select
      ->when($this->action !== '', fn($searchTerm) => $searchTerm->whereRaw('(COALESCE(a_action, at_action, action)) = ?', [$this->action]))
      ->when($this->adminOnly, fn($searchTerm) => $searchTerm->whereRaw('(COALESCE(a_action, at_action, action)) LIKE ?', ['ADMIN\_%']))
      ->when($this->from, function ($searchTerm) {
        $from = CarbonImmutable::parse($this->from)->startOfDay();
        $searchTerm->whereRaw('(COALESCE(a_created_at, created_at)) >= ?', [$from]);
      })
      ->when($this->to, function ($searchTerm) {
        $to = CarbonImmutable::parse($this->to)->endOfDay();
        $searchTerm->whereRaw('(COALESCE(a_created_at, created_at)) <= ?', [$to]);
      })
      ->orderByDesc('a_created_at');

    $rows = $searchTerm->paginate($this->perPage)->withQueryString();
    return view('livewire.admin.audit-trail-index', compact('rows'));
  }
}
