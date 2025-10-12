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
        Schema::create('event_history', function (Blueprint $table) {
            $table->id('eh_id');
            $table->foreignId('event_id')->constrained('event', 'event_id');
            $table->foreignId('user_id')->nullable()->constrained('user', 'user_id');
            $table->string('eh_action');
            $table->string('eh_comment');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_history');
    }
};
