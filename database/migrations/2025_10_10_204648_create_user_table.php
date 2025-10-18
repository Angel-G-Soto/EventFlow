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
        Schema::create('user', function (Blueprint $table) {
            $table->id('user_id');

            // --- Foreign Keys -- 
            $table->foreignId('department_id')->nullable()->constrained('department', 'department_id');

            //--- User Details ---
            $table->string('u_name');
            $table->string('u_email')->unique();
            $table->boolean('u_is_active')->default(true);

            // --- Timestamps --- 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user');
    }
};
