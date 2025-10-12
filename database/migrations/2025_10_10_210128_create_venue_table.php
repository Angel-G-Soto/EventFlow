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
        Schema::create('venue', function (Blueprint $table) {
            $table->id('venue_id');
            $table->foreignId('department_id')->constrained('department', 'department_id');
            $table->foreignId('v_manager_id')->constrained('user', 'user_id');
            $table->string('v_name');
            $table->string('v_code')->unique();
            $table->integer('v_features');
            $table->integer('v_capacity')->default(0);
            $table->integer('v_test_capacity')->default(0);
            $table->softDeletes('v_is_active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venue');
    }
};
