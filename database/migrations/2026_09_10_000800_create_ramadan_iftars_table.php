<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ramadan_iftars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agenda_event_id')->nullable()->constrained('agenda_events')->nullOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('relations_officer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->date('planned_date');
            $table->date('actual_date')->nullable();
            $table->time('time_from')->nullable();
            $table->time('time_to')->nullable();
            $table->string('location_type', 50);
            $table->string('location_name')->nullable();
            $table->text('address')->nullable();
            $table->string('google_maps_url', 2048)->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->string('supporting_entity_name')->nullable();
            $table->string('host_type', 50);
            $table->foreignId('community_organization_id')->nullable()->constrained('community_organizations')->nullOnDelete();
            $table->foreignId('local_community_id')->nullable()->constrained('local_communities')->nullOnDelete();
            $table->foreignId('mobilization_method_id')->nullable()->constrained('mobilization_methods')->nullOnDelete();
            $table->text('mobilization_method_other')->nullable();
            $table->unsignedInteger('planned_meals_count');
            $table->unsignedInteger('actual_meals_count')->nullable();
            $table->unsignedInteger('expected_attendance');
            $table->unsignedInteger('actual_attendance')->nullable();
            $table->string('status', 50)->default('draft');
            $table->string('execution_status', 50)->default('planned');
            $table->unsignedInteger('version_number')->default(1);
            $table->foreignId('parent_version_id')->nullable()->constrained('ramadan_iftars')->nullOnDelete();
            $table->timestamp('guidance_accepted_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'planned_date'], 'ramadan_iftars_branch_date_idx');
            $table->index(['branch_id', 'status'], 'ramadan_iftars_branch_status_idx');
            $table->index(['branch_id', 'execution_status'], 'ramadan_iftars_branch_execution_idx');
            $table->index('deleted_at', 'ramadan_iftars_deleted_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ramadan_iftars');
    }
};
