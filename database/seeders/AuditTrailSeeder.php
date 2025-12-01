<?php

namespace Database\Seeders;

use App\Models\AuditTrail;
use App\Models\User;
use Illuminate\Database\Seeder;

class AuditTrailSeeder extends Seeder
{
    /**
     * Seed the audit_trail table with sample entries.
     */
    public function run(): void
    {
        // Ensure we have an actor to attach to each audit row
        $actor = User::withTrashed()->where('email', 'seed.audit@example.com')->first();
        if (! $actor) {
            $actor = User::withTrashed()->updateOrCreate(
                ['email' => 'seed.audit@example.com'],
                [
                    'first_name'        => 'Seed',
                    'last_name'         => 'Auditor',
                    'password'          => bcrypt(str()->random(16)),
                    'auth_type'         => 'saml',
                    'email_verified_at' => now(),
                ]
            );
        }
        if ($actor->trashed()) {
            $actor->restore();
        }

        $entries = [
            [
                'user_id' => $actor->id,
                'action' => 'USER_LOGIN',
                'target_type' => 'user',
                'target_id' => (string) $actor->id,
                'ip' => '127.0.0.1',
                'method' => 'GET',
                'path' => '/seed',
                'ua' => 'Seeder/CLI',
                'meta' => [
                    'source' => 'seeder',
                    'description' => 'Sample user login event',
                ],
            ],
            [
                'user_id' => $actor->id,
                'action' => 'EVENT_CREATED',
                'target_type' => 'event',
                'target_id' => '1',
                'ip' => '127.0.0.1',
                'method' => 'POST',
                'path' => '/events',
                'ua' => 'Seeder/CLI',
                'meta' => [
                    'source' => 'seeder',
                    'status' => 'draft',
                ],
            ],
            [
                'user_id' => $actor->id,
                'action' => 'EVENT_APPROVED',
                'target_type' => 'event',
                'target_id' => '1',
                'ip' => '127.0.0.1',
                'method' => 'POST',
                'path' => '/events/1/approve',
                'ua' => 'Seeder/CLI',
                'meta' => [
                    'source' => 'seeder',
                    'status' => 'approved',
                ],
            ],
        ];

        foreach ($entries as $entry) {
            AuditTrail::query()->create($entry);
        }
    }
}
