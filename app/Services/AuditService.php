<?php

namespace App\Services;

use App\Models\AuditTrail;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * AuditService
 *
 * This service is a critical component for creating and retrieving an immutable log
 * of all significant actions performed within EventFlow.
 */
class AuditService
{
    /**
     * This is the primary method used to log a standard action performed by any authenticated user.
     *
     * @param int    $userId The ID of the authenticated user performing the action.
     * @param string $actionCode A machine-readable string identifying the action (e.g., EVENT_CREATED).
     * @param string $description A human-readable sentence describing the action.
     * @return AuditTrail
     */
    public function logAction(int $userId, string $actionCode, string $description): AuditTrail
    {
        return $this->write($userId, $actionCode, $description, false);
    }

    /**
     * Logs a high-privilege action performed by a System Administrator.
     *
     * @param int    $adminId The ID of the authenticated admin performing the action.
     * @param string $actionCode A concise, machine-readable action code (e.g., ADMIN_OVERRIDE).
     * @param string $description A human-readable description of what happened.
     * @return AuditTrail
     */
    public function logAdminAction(int $adminId, string $actionCode, string $description): AuditTrail
    {
        return $this->write($adminId, $actionCode, $description, true);
    }

    /**
     * Retrieves a paginated and filterable list of audit trail records.
     * This method demonstrates the use of the in-model query scopes.
     *
     * @param array $filters An associative array of filters (e.g., ['user_id' => 1, 'action_code' => '...']).
     * @return LengthAwarePaginator
     */
    public function getAuditTrail(array $filters = []): LengthAwarePaginator
    {
        $query = AuditTrail::query()->with('actor')->latest('audit_timestamp');

        // 1. Use the 'forUser' scope if a user_id is provided
        if (!empty($filters['user_id'])) {
            $user = User::find($filters['user_id']);
            if ($user) {
                $query->forUser($user);
            }
        }

        // 2. Use the 'ofType' scope if an action_code is provided
        if (!empty($filters['at_action'])) {
            $query->ofType($filters['at_action']);
        }
        
        // 3. Use the 'adminActions' scope if the filter is set.
        if (array_key_exists('is_admin_action', $filters)) {
            $query->adminActions((bool) $filters['is_admin_action']);
        }

        // 4. Return Paginator with filtered audit entries
        return $query->paginate(50);
    }

    /**
     * Core writer method that creates the audit trail record in the database.
     */
    protected function write(int $userId, string $actionCode, string $description, bool $isAdmin): AuditTrail
    {
        // Create the AuditTrail record
        return AuditTrail::create([
            'user_id' => $userId,
            'at_action' => $actionCode,
            'at_description' => $description,
            'is_admin_action' => $isAdmin
        ]);
    }
}

