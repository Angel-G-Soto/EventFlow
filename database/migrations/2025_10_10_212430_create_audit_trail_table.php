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
        Schema::create('audit_trail', function (Blueprint $table) {
            $table->id('at_id');

            // --- Foreign Keys --- 
            $table->foreignId('user_id')->nullable()->constrained('user', 'user_id')->onDelete('set null');

            // --- Event History Details ---
            $table->string('at_action');
            $table->string('at_description');
            $table->boolean('is_admin_action');

            // --- Timestamps ---
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_trail');
    }
};
