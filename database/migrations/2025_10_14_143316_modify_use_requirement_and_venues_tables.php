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
        Schema::table('venues', function (Blueprint $table) {
            $table->dropForeign(['use_requirement_id']);
            $table->dropColumn('use_requirement_id');
        });

        Schema::dropIfExists('use_requirements');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('use_requirements', function (Blueprint $table) {
            $table->id();
            $table->text('us_doc_drive');
            $table->text('us_instructions');
            $table->boolean('us_alcohol_policy')->default(false);
            $table->boolean('us_cleanup_policy')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::table('venues', function (Blueprint $table) {
            $table->foreignId('use_requirement_id')->constrained('use_requirements');
        });
    }
};
