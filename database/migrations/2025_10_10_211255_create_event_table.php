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
            $table->foreignId('venue_id')->constrained('venue', 'venue_id');
            $table->foreignId('e_creator_id')->nullable()->onstrained('user', 'user_id')->onDelete('set null');
            $table->foreignId('e_current_approver_id')->nullable()->constrained('user', 'user_id')->onDelete('set null');
            $table->string('e_title');
            $table->text('e_description')->nullable();
            $table->string('e_status');
            $table->dateTime('e_date');

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
