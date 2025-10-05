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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('venue_id')->constrained('venues');
            $table->string('e_student_id');
            $table->string('e_student_phone');
            $table->string('e_organization');
            $table->string('e_advisor_name');
            $table->string('e_advisor_email');
            $table->string('e_advisor_phone');
            $table->string('e_title');
            $table->text('e_description');
            $table->string('e_status');
            $table->dateTime('e_start_date');
            $table->dateTime('e_end_date');
            $table->string('e_guests');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
