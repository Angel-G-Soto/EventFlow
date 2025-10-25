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
        ///////////////////////// ROLES //////////////////////////////////////
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('user_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('role_id')->constrained('roles');
            $table->timestamps();
        });

        //////////////////// Audit Trail ///////////////////////////////////
        Schema::create('audit_trail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('action');
            $table->string('target_type');
            $table->string('target_id');
            $table->timestamps();
        });

        //////////////// User, Department, Venue and Use Requirement ////////
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('auth_type');
            $table->softDeletes();
            $table->foreignId('department_id')->constrained('departments');
            $table->dropColumn('name');
            $table->string('first_name');
            $table->string('last_name');
        });

        Schema::create('venues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments');
            $table->foreignId('manager_id')->nullable()->constrained('users');
            $table->string('name');
            $table->string('code');
            $table->string('features');
            $table->integer('capacity');
            $table->integer('test_capacity');
            $table->time('opening_time')->default('00:00:00');
            $table->time('closing_time')->default('23:59:59');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('use_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained('venues');
            $table->string('name');
            $table->string('hyperlink', 512);
            $table->string('description', 512);
            $table->timestamps();
        });


        ////////////////////// Event, Document and Category ////////////////////

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->constrained('users');
            $table->foreignId('venue_id')->constrained('venues');
            $table->integer('organization_nexo_id');
            $table->string('organization_nexo_name');
            $table->string('organization_advisor_email');
            $table->string('organization_advisor_name');
            $table->string('organization_advisor_phone');
            $table->string('student_number');
            $table->string('student_phone');
            $table->string('title');
            $table->text('description');
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->string('status');
            $table->integer('guests');
            $table->boolean('handles_food');
            $table->boolean('use_institutional_funds');
            $table->boolean('external_guest');
            $table->timestamps();
        });

        Schema::create('event_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approver_id')->constrained('users');
            $table->foreignId('event_id')->constrained('events');
            $table->string('action')->nullable();
            $table->string('comment');
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events');
            $table->string('name');
            $table->string('file_path');
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('category_event', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories');
            $table->foreignId('event_id')->constrained('events');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        ////////////////////// Event, Document and Category ////////////////////
        Schema::dropIfExists('category_event');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('event_histories');
        Schema::dropIfExists('events');

        //////////////////// User, Department, Venue and Use Requirement ////////
        Schema::dropIfExists('use_requirements');
        Schema::dropIfExists('venues');

        // Remove foreign key from users before dropping departments
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'department_id')) {
                $table->dropForeign(['department_id']);
            }
            if (Schema::hasColumn('users', 'auth_type')) {
                $table->dropColumn('auth_type');
            }
            if (Schema::hasColumn('users', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            if (Schema::hasColumn('users', 'first_name')) {
                $table->dropColumn('first_name');
            }
            if (Schema::hasColumn('users', 'last_name')) {
                $table->dropColumn('last_name');
            }
            $table->string('name');
        });

        Schema::dropIfExists('departments');

        //////////////////// Audit Trail ///////////////////////////////////
        Schema::dropIfExists('audit_trail');

        ///////////////////////// ROLES //////////////////////////////////////
        Schema::dropIfExists('user_role');
        Schema::dropIfExists('roles');
    }
};
