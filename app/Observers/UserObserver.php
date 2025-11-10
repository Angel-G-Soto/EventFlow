<?php

namespace App\Observers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class UserObserver
{
    public function created(User $user): void
    {
        try {
            if (!method_exists($user, 'roles')) {
                return;
            }
            if (!Schema::hasTable('roles') || !Schema::hasTable('user_role')) {
                return;
            }

            $default = Role::query()
                ->where('code', 'user')
                ->orWhere('name', 'user')
                ->first();

            if (!$default) {
                $default = Role::firstOrCreate(['code' => 'user'], ['name' => 'user']);
            }

            if ($default) {
                $user->roles()->syncWithoutDetaching([(int) $default->id]);
            }
        } catch (\Throwable) {
            // best-effort; ignore
        }
    }
}

