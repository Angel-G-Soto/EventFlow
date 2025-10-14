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
//        Schema::table('venues', function (Blueprint $table) {
//            $table->foreignId('venue_requirement_id')->constrained('venue_requirements');
//        });
//
//        Schema::table('departments', function (Blueprint $table) {
//            $table->foreignId('department_requirement_id')->constrained('department_requirements');
//        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
//        Schema::table('venues', function (Blueprint $table) {
//            $table->dropForeign(['venue_requirement_id']);
//            $table->dropColumn('venue_requirement_id');
//        });
//
//        Schema::table('departments', function (Blueprint $table) {
//            $table->dropForeign(['department_requirement_id']);
//            $table->dropColumn('department_requirement_id');
//        });
    }
};
