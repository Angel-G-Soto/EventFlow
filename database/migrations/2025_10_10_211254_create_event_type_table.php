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
        Schema::create('event_type', function (Blueprint $table) {
            $table->id('event_type_id');

            // --- Event Type Details -- 
            $table->string('et_name')->unique();
            $table->string('et_code')->unique();

            // --- Timestamps ---
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_type');
    }
};
