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
        Schema::table('events', function (Blueprint $table) {
            $table->renameColumn('user_id', 'e_creator_id');
            $table->foreign('e_creator_id')->references('id')->on('users');
            $table->foreignId('e_current_approver_id')->constrained('users');
            $table->integer('organization_nexo_id');
            $table->renameColumn('e_organization', 'e_organization_name');
            $table->renameColumn('e_advisor_email', 'e_organization_advisor_email');
            $table->renameColumn('e_advisor_name', 'e_organization_advisor_name');
            $table->renameColumn('e_advisor_phone', 'e_organization_advisor_phone');
            $table->renameColumn('e_category', 'e_type');
            $table->string('e_status_code');
            $table->string('e_upload_status');
            $table->renameColumn('e_start_date', 'e_start_time');
            $table->renameColumn('e_end_date', 'e_end_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->renameColumn('e_creator_id', 'user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->renameColumn('e_organization_name', 'e_organization');
            $table->renameColumn('e_organization_advisor_email', 'e_advisor_email');
            $table->renameColumn('e_organization_advisor_name', 'e_advisor_name');
            $table->renameColumn('e_organization_advisor_phone', 'e_advisor_phone');
            $table->renameColumn('e_type', 'e_category');
            $table->renameColumn('e_start_time', 'e_start_date');
            $table->renameColumn('e_end_time', 'e_end_date');

            $table->dropConstrainedForeignId('e_current_approver_id');
            $table->dropColumn('organization_nexo_id');
            $table->dropColumn('e_status_code');
            $table->dropColumn('e_upload_status');
        });
    }
};
