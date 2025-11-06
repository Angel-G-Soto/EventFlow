<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $roles = [
            ['name' => 'user', 'code' => 1],
            ['name' => 'advisor', 'code' => 2],
            ['name' => 'venue-manager', 'code' => 3],
            ['name' => 'event-approver', 'code' => 4],
            ['name' => 'system-admin', 'code' => 5],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['code' => $role['code']], // condition
                [
                    'name' => $role['name'],
                    'code' => $role['code'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('roles')->delete();
    }
};
