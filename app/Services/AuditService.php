<?php

namespace App\Services;

use App\Models\AuditTrail;

/**
 * AuditService
 *
 * This service is a critical component responsible for creating an immutable, chronological log
 * of all significant actions performed within EventFlow. It is designed to be a lightweight,
 * decoupled utility that receives all necessary data as simple parameters.
 */
class AuditService
{
    /**
     * This is the primary method used to log a standard action performed by any authenticated user.
     *
     * @param int    $userId The ID of the authenticated user performing the action.
     * @param string $actionCode A machine-readable string identifying the action (e.g., EVENT_CREATED).
     * @param string $description A human-readable sentence describing the action.
     * @return AuditTrail The newly created AuditTrail Eloquent model instance.
     */
    public function logAction(int $userId, string $userName, string $actionCode, string $description): AuditTrail
    {
        return $this->write($userId, $userName, $actionCode, $description);
    }

    /**
     * Logs a high-privilege action performed by a System Administrator.
     *
     * @param int    $adminId The ID of the authenticated admin performing the action.
     * @param string $actionCode A concise, machine-readable action code (e.g., ADMIN_OVERRIDE).
     * @param string $description A human-readable description of what happened.
     * @param string|null $ipAddress Optional. The IP address from which the request originated.
     * @return AuditTrail The newly created AuditTrail Eloquent model instance.
     */
    public function logAdminAction(int $adminId, string $userName, string $actionCode, string $description): AuditTrail
    {
        return $this->write($adminId, $userName, $actionCode, $description);
    }

    /**
     * Core writer method that creates the audit trail record in the database.
     *
     * @param int $userId
     * @param string $actionCode
     * @param string $description
     * @return AuditTrail
     */
    protected function write(int $userId, string $userName, string $actionCode, string $description): AuditTrail
    {
        // Create the record using the fields defined in the ERD.
        return AuditTrail::create([
            'user_id' => $userId,
            'at_action' => $actionCode,
            'at_description' => $description,
            'at_user'=> $userName
        ]);
    }
}