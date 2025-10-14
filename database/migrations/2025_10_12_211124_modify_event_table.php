<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {

            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');

            $table->foreignId('creator_id')->constrained('users');
            $table->foreignId('current_approver_id')->constrained('users');

            $table->renameColumn('e_start_date', 'e_start_time');
            $table->renameColumn('e_end_date', 'e_end_time');

            $table->dropColumn(['e_category', 'e_organization']);

            $table->integer('e_organization_nexo_id')->nullable();
            $table->string('e_organization_name')->nullable();
            $table->string('e_type')->nullable();
            $table->string('e_status_code')->nullable();
            $table->string('e_upload_status')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {

            $table->dropForeign(['creator_id']);
            $table->dropForeign(['current_approver_id']);

            $table->foreignId('user_id')->constrained('users');
            $table->renameColumn('e_start_time', 'e_start_date');
            $table->renameColumn('e_end_time', 'e_end_date');

            $table->string('e_category')->nullable();
            $table->string('e_organization')->nullable();

            $table->dropColumn([
                'creator_id',
                'current_approver_id',
                'e_organization_nexo_id',
                'e_organization_name',
                'e_type',
                'e_status_code',
                'e_upload_status',
            ]);

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
