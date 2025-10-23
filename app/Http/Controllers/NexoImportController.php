<?php

namespace App\Http\Controllers;

use App\Http\Requests\NexoImportRequest;
// use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Throwable;

class NexoImportController extends Controller
{
    public function import(NexoImportRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $rows = $payload['rows'];
        $sourceId = $payload['source_id'] ?? null;

        // Ensure fallback tables exist if models were removed from the codebase.
        // These are minimal shapes to let the import run; convert them to proper migrations later.
        $this->ensureAssociationsTable();
        $this->ensurePublishersTable();
        $this->ensureNexoImportsTable();

        // Determine which user columns your users table actually has (email/name variations).
        $userEmailCol = Schema::hasColumn('users', 'email') ? 'email'
            : (Schema::hasColumn('users', 'u_email') ? 'u_email' : null);

        $userNameCol = Schema::hasColumn('users', 'name') ? 'name'
            : (Schema::hasColumn('users', 'u_name') ? 'u_name' : null);

        if (!$userEmailCol || !$userNameCol) {
            Log::error('Nexo import cannot find expected user email/name columns.', [
                'email_column_exists' => $userEmailCol !== null,
                'name_column_exists' => $userNameCol !== null,
            ]);

            return response()->json([
                'message' => 'User table missing expected columns (email/name). Please check schema.',
            ], 500);
        }

        // Idempotency: if source_id already processed, return 200
        if ($sourceId) {
            $existing = DB::table('nexo_imports')->where('source_id', $sourceId)->first();
            if ($existing) {
                return response()->json([
                    'message' => 'Already processed',
                    'source_id' => $sourceId,
                    'import_id' => $existing->id,
                ], 200);
            }
        }

        $summary = [
            'processed' => 0,
            'created_users' => 0,
            'updated_users' => 0,
            'created_associations' => 0,
            'updated_associations' => 0,
            'created_publishers' => 0,
        ];

        DB::beginTransaction();
        try {
            foreach ($rows as $r) {
                // Normalize incoming keys: accept 'id' as assoc id or 'assoc_id'
                $assocExternalId = $r['assoc_id'] ?? $r['id'] ?? null;
                if ($assocExternalId === null) {
                    throw new \InvalidArgumentException('Association id missing in row: ' . json_encode($r));
                }

                $assocName = $r['association_name'] ?? ($r['name'] ?? null);
                $assocCounselor = $r['counselor'] ?? null;
                $assocEmailCounselor = $r['email_counselor'] ?? null;

                // Upsert into associations table (fallback to DB table if no model)
                $assoc = DB::table('associations')->where('external_id', $assocExternalId)->first();
                if (!$assoc) {
                    $assocId = DB::table('associations')->insertGetId([
                        'external_id' => $assocExternalId,
                        'name' => $assocName,
                        'counselor' => $assocCounselor,
                        'email_counselor' => $assocEmailCounselor,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $summary['created_associations']++;
                } else {
                    // update
                    DB::table('associations')->where('id', $assoc->id)->update([
                        'name' => $assocName,
                        'counselor' => $assocCounselor,
                        'email_counselor' => $assocEmailCounselor,
                        'updated_at' => now(),
                    ]);
                    $assocId = $assoc->id;
                    $summary['updated_associations']++;
                }

                // Upsert user by email using existing User model and detected column names
                $emailValue = $r['email'] ?? null;
                $nameValue = $r['name'] ?? null;

                if (!$emailValue || !$nameValue) {
                    throw new \InvalidArgumentException('User name/email missing in row: ' . json_encode($r));
                }

                // Build attributes arrays keyed to the real column names
                $search = [$userEmailCol => $emailValue];
                $update = [$userNameCol => $nameValue];

                // Use query builder to support custom column names while still using the User model.
                $existingUser = DB::table('users')->where($userEmailCol, $emailValue)->first();

                if (!$existingUser) {
                    $userId = DB::table('users')->insertGetId(array_merge($search, $update, [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]));
                    $summary['created_users']++;
                } else {
                    DB::table('users')->where($userEmailCol, $emailValue)->update(array_merge($update, [
                        'updated_at' => now(),
                    ]));
                    $userId = $existingUser->user_id ?? $existingUser->id ?? $existingUser->id ?? null;
                    $summary['updated_users']++;
                }

                if (!$userId) {
                    // try to fetch primary key id common names if we didn't already get it
                    $userRow = DB::table('users')->where($userEmailCol, $emailValue)->first();
                    $userId = $userRow->user_id ?? $userRow->id ?? null;
                }

                // Ensure publisher pivot exists between user and association
                $existingPublisher = DB::table('publishers')
                    ->where('user_id', $userId)
                    ->where('assoc_id', $assocId)
                    ->first();

                if (!$existingPublisher) {
                    DB::table('publishers')->insert([
                        'user_id' => $userId,
                        'assoc_id' => $assocId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $summary['created_publishers']++;
                }

                $summary['processed']++;
            }

            // Insert import record for idempotency/audit
            $importId = DB::table('nexo_imports')->insertGetId([
                'source_id' => $sourceId,
                'row_count' => $summary['processed'],
                'payload_hash' => hash('sha256', json_encode($payload)),
                'summary' => json_encode($summary),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Import successful',
                'import_id' => $importId,
                'summary' => $summary,
            ], 200);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Nexo import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $payload,
            ]);
            return response()->json([
                'message' => 'Import failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a minimal associations table if it doesn't exist.
     * This is a fallback for older schema versions; prefer a migration in production.
     */
    protected function ensureAssociationsTable(): void
    {
        if (! Schema::hasTable('associations')) {
            Schema::create('associations', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('external_id')->nullable()->unique();
                $table->string('name')->nullable();
                $table->string('counselor')->nullable();
                $table->string('email_counselor')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            Log::info('Created fallback table: associations (Nexo import)');
        }
    }

    protected function ensurePublishersTable(): void
    {
        if (! Schema::hasTable('publishers')) {
            Schema::create('publishers', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('assoc_id');
                $table->timestamps();

                // foreign keys omitted in fallback to avoid failures if referenced tables differ
                $table->unique(['user_id', 'assoc_id']);
            });

            Log::info('Created fallback table: publishers (Nexo import)');
        }
    }

    protected function ensureNexoImportsTable(): void
    {
        if (! Schema::hasTable('nexo_imports')) {
            Schema::create('nexo_imports', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('source_id')->nullable()->index();
                $table->integer('row_count')->default(0);
                $table->string('payload_hash', 64)->nullable()->index();
                $table->json('summary')->nullable();
                $table->timestamps();
            });

            Log::info('Created fallback table: nexo_imports (Nexo import)');
        }
    }
}
