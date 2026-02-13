<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('monthly_activities', function (Blueprint $table) {
            $table->string('relations_officer_approval_status')->default('pending')->after('status');
            $table->string('relations_manager_approval_status')->default('pending')->after('relations_officer_approval_status');
            $table->string('programs_officer_approval_status')->default('pending')->after('relations_manager_approval_status');
            $table->string('programs_manager_approval_status')->default('pending')->after('programs_officer_approval_status');
            $table->string('executive_approval_status')->default('pending')->after('programs_manager_approval_status');
        });
    }

    public function down(): void
    {
        Schema::table('monthly_activities', function (Blueprint $table) {
            $table->dropColumn([
                'relations_officer_approval_status',
                'relations_manager_approval_status',
                'programs_officer_approval_status',
                'programs_manager_approval_status',
                'executive_approval_status',
            ]);
        });
    }
};

