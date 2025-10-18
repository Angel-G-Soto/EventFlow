<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opening_hour', function (Blueprint $table) {
            $table->id('oh_id');

            // Foreign key to the Venue table. Deletes hours if the venue is deleted.
            $table->foreignId('venue_id')->constrained('Venue', 'venue_id')->onDelete('cascade');

            // Day of the week (e.g., 1 for Monday, 7 for Sunday)
            $table->tinyInteger('day_of_week');
            $table->time('open_time');
            $table->time('close_time');

            // Ensure a venue can't have duplicate entries for the same day
            $table->unique(['venue_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opening_hour');
    }
};