<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lightweight index existence check for MySQL/MariaDB.
     */
    private function hasIndex(string $table, string $index): bool
    {
        $table = str_replace('`', '', $table);
        $result = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]);
        return !empty($result);
    }

    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (!$this->hasIndex('events', 'events_status_idx')) {
                $table->index('status', 'events_status_idx');
            }
            if (!$this->hasIndex('events', 'events_venue_time_idx')) {
                $table->index(['venue_id', 'start_time', 'end_time'], 'events_venue_time_idx');
            }
            if (!$this->hasIndex('events', 'events_created_at_idx')) {
                $table->index('created_at', 'events_created_at_idx');
            }
        });

        Schema::table('event_histories', function (Blueprint $table) {
            if (!$this->hasIndex('event_histories', 'event_histories_approver_status_event_idx')) {
                $table->index(
                    ['approver_id', 'status_when_signed', 'event_id'],
                    'event_histories_approver_status_event_idx'
                );
            }
        });

        Schema::table('documents', function (Blueprint $table) {
            if (!$this->hasIndex('documents', 'documents_event_id_idx')) {
                $table->index('event_id', 'documents_event_id_idx');
            }
        });

        Schema::table('venues', function (Blueprint $table) {
            if (!$this->hasIndex('venues', 'venues_department_id_idx')) {
                $table->index('department_id', 'venues_department_id_idx');
            }
            if (!$this->hasIndex('venues', 'venues_name_idx')) {
                $table->index('name', 'venues_name_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if ($this->hasIndex('events', 'events_status_idx')) {
                $table->dropIndex('events_status_idx');
            }
            if ($this->hasIndex('events', 'events_venue_time_idx')) {
                $table->dropIndex('events_venue_time_idx');
            }
            if ($this->hasIndex('events', 'events_created_at_idx')) {
                $table->dropIndex('events_created_at_idx');
            }
        });

        Schema::table('event_histories', function (Blueprint $table) {
            if ($this->hasIndex('event_histories', 'event_histories_approver_status_event_idx')) {
                $table->dropIndex('event_histories_approver_status_event_idx');
            }
        });

        Schema::table('documents', function (Blueprint $table) {
            if ($this->hasIndex('documents', 'documents_event_id_idx')) {
                $table->dropIndex('documents_event_id_idx');
            }
        });

        Schema::table('venues', function (Blueprint $table) {
            if ($this->hasIndex('venues', 'venues_department_id_idx')) {
                $table->dropIndex('venues_department_id_idx');
            }
            if ($this->hasIndex('venues', 'venues_name_idx')) {
                $table->dropIndex('venues_name_idx');
            }
        });
    }
};
