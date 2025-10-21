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
        schema::table('events', function (Blueprint $table) {
            $table->dropColumn('e_type');
            $table->dropColumn('e_alcohol_policy_agreement');
            $table->dropColumn('e_cleanup_policy_agreement');
        });

        schema::create('category_venue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories');
            $table->foreignId('venue_id')->constrained('venues');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('e_type')->nullable();
            $table->boolean('e_alcohol_policy_agreement')->default(false);
            $table->boolean('e_cleanup_policy_agreement')->default(false);
        });

        Schema::dropIfExists('category_venue');
    }
};
