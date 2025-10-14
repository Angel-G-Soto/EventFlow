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
            $table->id();
            $table->foreignId('venue_id')->constrained('venues');
            $table->text('vr_document_link');
            $table->text('vr_document_name');
            $table->text('vr_document_description');
            $table->timestamps();
        });

        Schema::create('department_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments');
            $table->text('dr_document_link');
            $table->text('dr_document_name');
            $table->text('dr_document_description');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venue_requirements');
        Schema::dropIfExists('department_requirements');
    }
};
