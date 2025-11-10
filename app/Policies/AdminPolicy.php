<?php

namespace App\Policies;

use App\Models\User;

class AdminPolicy
{
  /**
   * Determine whether the user can access any admin dashboards
   * (User Management, Event Oversight, Audit Trail).
   */
  public function accessDashboard(User $user): bool
  {
    return $user->getRoleNames()->contains('system-admin');
  }

  /**
   * Determine whether the user can perform manual overrides on event requests
   * (force-approve, revert status, etc.).
   */
  public function performOverride(User $user): bool
  {
    return $user->getRoleNames()->contains('system-admin');
  }

  /**
   * Determine whether the user can create, edit, suspend, or delete user accounts.
   */
  public function manageUsers(User $user): bool
  {
    return $user->getRoleNames()->contains('system-admin');
  }
}
