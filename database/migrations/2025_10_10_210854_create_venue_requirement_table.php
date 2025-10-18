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
        Schema::create('venue_requirement', function (Blueprint $table) {
            $table->id('vr_id');

            // --- Foreign Keys -- 
            $table->foreignId('venue_id')->constrained('venue', 'venue_id');

            // --- Venue Requirement Details ---
            $table->string('vr_name')->nullable();
            $table->string('vr_type')->index();
            $table->text('vr_content')->nullable();
     
            // --- Timestamps ---
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venue_requirement');
    }
};
