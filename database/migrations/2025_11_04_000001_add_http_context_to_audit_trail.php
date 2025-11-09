<?php

// database/migrations/2025_11_04_000001_add_http_context_to_audit_trail.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('audit_trail', function (Blueprint $table) {
            // Extra columns used by the Livewire view / details modal
            $table->string('ip', 45)->nullable()->after('target_id');      // IPv4/IPv6
            $table->string('method', 10)->nullable()->after('ip');         // GET/POST/PUT/DELETE
            $table->string('path', 2048)->nullable()->after('method');     // request path
            $table->text('ua')->nullable()->after('path');                 // user agent
            $table->json('meta')->nullable()->after('ua');                 // extra context

            // Helpful indexes for the filters you use
            $table->index(['user_id', 'created_at']);
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('audit_trail', function (Blueprint $table) {
            $table->dropIndex(['audit_trail_user_id_created_at_index']);
            $table->dropIndex(['audit_trail_action_index']);
            $table->dropIndex(['audit_trail_created_at_index']);

            $table->dropColumn(['ip', 'method', 'path', 'ua', 'meta']);
        });
    }
};

