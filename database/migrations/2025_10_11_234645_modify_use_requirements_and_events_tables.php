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
        Schema::table('use_requirements', function (Blueprint $table) {
            $table->binary('us_alcohol_policy');
            $table->binary('us_cleanup_policy');
        });
        Schema::table('events', function (Blueprint $table) {
            $table->binary('e_alcohol_policy_agreement');
            $table->binary('e_cleanup_policy_agreement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('use_requirements', function (Blueprint $table) {
            $table->dropColumn('us_alcohol_policy');
            $table->dropColumn('us_cleanup_policy');
        });
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('e_alcohol_policy_agreement');
            $table->dropColumn('e_cleanup_policy_agreement');
        });
    }
};
