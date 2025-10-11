<?php

namespace App\Services;

use App\Models\AuditTrail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class AuditService
{
  public const EVENT_CREATED            = 'EVENT_CREATED';
  public const EVENT_WITHDRAWN          = 'EVENT_WITHDRAWN';
  public const ADMIN_OVERRIDE           = 'ADMIN_OVERRIDE';
  public const ROLE_IMPERSONATION_START = 'ROLE_IMPERSONATION_START';
  public const ROLE_IMPERSONATION_END   = 'ROLE_IMPERSONATION_END';
  // add more as needed


  /**
   * standard user action
   */
  public function logAction(User $user, string $action, string $description, array $context = []): AuditTrail
  {
    return $this->write($user, $action, $description, false, $context);
  }

  /**
   * admin/high-privilege action
   */
  public function logAdminAction(User $user, string $action, string $description, array $context = []): AuditTrail
  {
    return $this->write($user, $action, $description, true, $context);
  }

  /**
   * Core writer to legacy schema:
   * - table: AuditTrail
   * - pk: audit_id
   * - cols: u_id, audit_action, audit_target_type, audit_target_id, u_updated_at
   * 
   */
  protected function write(User $user, string $action, string $description, bool $admin, array $context): AuditTrail
  {
    $subjectType = $context['subject_type'] ?? ($context['target_type'] ?? null);
    $subjectId   = $context['subject_id']   ?? ($context['target_id']   ?? null);

    // Expects 'u_updated_at' only (no created_at/updated_at)
    // We’ll write “now()” to that column so it acts as the event timestamp.
    $now = Carbon::now();

    return AuditTrail::create([
      'u_id'              => $this->resolveUserPk($user),
      'audit_action'      => Str::limit($action, 100),      // keep within reasonable length
      'audit_target_type' => $subjectType,
      'audit_target_id'   => $subjectId,
      'u_updated_at'      => $now,

    ]);
  }

  /**
   * Helper: legacy users table may use 'u_id' instead of 'id'.
   * Update this to pull the correct PK column from your User model.
   */
  protected function resolveUserPk(User $user): int|string
  {
    return $user->u_id ?? $user->id;
  }
}
