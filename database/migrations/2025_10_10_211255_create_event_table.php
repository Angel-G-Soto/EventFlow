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
        Schema::create('event', function (Blueprint $table) {
            $table->id('event_id');
            $table->foreignId('creator_id')->nullable()->constrained('user', 'user_id')->onDelete('set null');
            $table->foreignId('current_approver_id')->nullable()->constrained('user', 'user_id')->onDelete('set null');
            $table->foreignId('venue_id')->constrained('venue', 'venue_id');
            $table->string('e_student_id');
            $table->string('e_student_phone');
            $table->string('e_title');
            $table->string('e_category');
            $table->text('e_description')->nullable();
            $table->string('e_status');
            $table->string('e_status_code');
            $table->dateTime('e_start_date');
            $table->dateTime('e_end_date');
            $table->string('e_guest');

            // For "just-in-time" Nexo data
            $table->string('organization_nexo_id');
            $table->string('organization_nexo_name');
            $table->string('advisor_email');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event');
    }
};
