<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('use_requirements', function (Blueprint $table) {
            $table->dropForeign(['venue_id']);
            $table->dropColumn('venue_id');
        });

        Schema::table('venues', function (Blueprint $table) {
            $table->foreignId('use_requirement_id')->constrained('use_requirements');
        });
    }

    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->dropForeign(['use_requirement_id']);
            $table->dropColumn('use_requirement_id');
        });

        Schema::table('use_requirements', function (Blueprint $table) {
            $table->foreignId('venue_id')->constrained('venues');
        });
    }

};

