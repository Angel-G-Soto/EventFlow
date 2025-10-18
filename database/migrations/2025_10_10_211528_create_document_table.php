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
        Schema::create('document', function (Blueprint $table) {
            $table->id('document_id');

            // --- Foreign Keys --- 
            $table->foreignId('event_id')->constrained('event', 'event_id');

            // --- Document Details ---
            $table->string('doc_name'); // Original filename
            $table->string('doc_path'); // Path on disk

            // --- Timestamps --- 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document');
    }
};
