<?php

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
        // This migration is now only responsible for the User <-> Role relationship.
        Schema::create('role_assignment', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('user', 'user_id')->onDelete('cascade');
            $table->foreignId('role_id')->constrained('role', 'role_id')->onDelete('cascade');
            // A composite primary key ensures a user cannot have the same role twice.
            $table->primary(['user_id', 'role_id']);
        });

        // This migration is now only responsible for the Venue <-> EventType relationship.
        Schema::create('venue_event_type_exclusions', function (Blueprint $table) {
            $table->foreignId('venue_id')->constrained('venue', 'venue_id')->onDelete('cascade');
            $table->foreignId('event_type_id')->constrained('event_type', 'event_type_id')->onDelete('cascade');
            // A composite primary key ensures a user cannot have the same role twice.
            $table->primary(['venue_id', 'event_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_assignment');
        Schema::dropIfExists('venue_event_type_exclusions');
    }
};

