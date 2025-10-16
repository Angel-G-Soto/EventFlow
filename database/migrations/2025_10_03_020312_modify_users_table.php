<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop the 'name' column
            $table->dropColumn('name');

            // Drop unique index on 'email'
            $table->dropUnique(['email']);

            // Add new columns
            $table->string('first_name')->nullable(true);
            $table->string('last_name')->nullable(true);
            $table->string('auth_type')->default('local');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add 'name' column back
            $table->string('name')->after('id');

            // Make email unique again
            $table->unique('email');

            // Drop newly added columns
            $table->dropColumn(['first_name', 'last_name', 'auth_type']);
        });
    }
};
