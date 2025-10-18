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

            // --- Foreign Keys -- 
            $table->foreignId('creator_id')->nullable()->constrained('user', 'user_id')->onDelete('set null');
            $table->foreignId('current_approver_id')->nullable()->constrained('user', 'user_id')->onDelete('set null');
            $table->foreignId('venue_id')->constrained('venue', 'venue_id');
            $table->foreignId('event_type_id')->constrained('event_type', 'event_type_id');

            // -- Event Details -- 
            $table->string('e_student_id');
            $table->string('e_student_phone');
            $table->string('e_title');
            $table->text('e_description')->nullable();
            $table->string('e_status');
            $table->string('e_status_code');
            $table->dateTime('e_start_date');
            $table->dateTime('e_end_date');
            $table->boolean('sells_food');
            $table->boolean('uses_instutional_funds');
            $table->boolean('has_external_guest');
            
            // For "just-in-time" Nexo data (denormalized)
            $table->string('organization_nexo_id');
            $table->string('organization_nexo_name');
            $table->string('advisor_name');
            $table->string('advisor_email');
            $table->string('advisor_phone');      // Does not come from Nexo

            // --- Timestamps -- 
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
