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
        Schema::create('venue_requirements', function (Blueprint $table) {
            $table->id('vr_id');
            $table->foreignId('venue_id')->constrained('venue', 'venue_id');
            $table->text('vr_instructions')->nullable();
            $table->string('vr_doc_link')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venue_requirements');
    }
};
