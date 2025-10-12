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
        Schema::create('Role Assignment', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('user', 'user_id')->onDelete('cascade');
            $table->foreignId('role_id')->constrained('role', 'role_id')->onDelete('cascade');
            // A composite primary key ensures a user cannot have the same role twice.
            $table->primary(['user_id', 'role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Role Assignment');
    }
};

