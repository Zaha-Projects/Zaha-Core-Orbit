<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('maintenance_requests')) {
            Schema::create('maintenance_requests', function (Blueprint $table) {
                $table->id();
                $table->timestamp('logged_at')->nullable();
                $table->string('type');
                $table->string('category');
                $table->text('description');
                $table->string('priority')->nullable();
                $table->string('status')->default('open');
                $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnUpdate()->nullOnDelete();
                $table->foreignId('center_id')->nullable()->constrained('centers')->cascadeOnUpdate()->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
                $table->timestamp('closed_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('maintenance_work_details')) {
            Schema::create('maintenance_work_details', function (Blueprint $table) {
                $table->id();
                $table->foreignId('maintenance_request_id')->constrained('maintenance_requests')->cascadeOnUpdate()->cascadeOnDelete();
                $table->timestamp('start_from')->nullable();
                $table->timestamp('end_to')->nullable();
                $table->string('team_desc')->nullable();
                $table->string('resources_type')->nullable();
                $table->string('support_party')->nullable();
                $table->decimal('estimated_cost', 12, 2)->nullable();
                $table->text('root_cause_analysis')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('updated_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('maintenance_approvals')) {
            Schema::create('maintenance_approvals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('maintenance_request_id')->constrained('maintenance_requests')->cascadeOnUpdate()->cascadeOnDelete();
                $table->string('step');
                $table->string('decision');
                $table->text('comment')->nullable();
                $table->foreignId('approved_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('maintenance_attachments')) {
            Schema::create('maintenance_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('maintenance_request_id')->constrained('maintenance_requests')->cascadeOnUpdate()->cascadeOnDelete();
                $table->string('file_path');
                $table->string('file_type')->nullable();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_attachments');
        Schema::dropIfExists('maintenance_approvals');
        Schema::dropIfExists('maintenance_work_details');
        Schema::dropIfExists('maintenance_requests');
    }
};
