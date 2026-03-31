<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDynamicWorkflowTables extends Migration
{
    public function up(): void
    {
        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name_ar')->nullable();
            $table->string('name_en')->nullable();
            $table->string('module')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('workflows')->cascadeOnDelete();
            $table->unsignedInteger('step_order');
            $table->string('step_key');
            $table->string('name_ar')->nullable();
            $table->string('name_en')->nullable();
            $table->enum('step_type', ['sub', 'main'])->default('sub');
            $table->unsignedInteger('approval_level')->default(1);
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->foreignId('permission_id')->nullable()->constrained('permissions')->nullOnDelete();
            $table->boolean('is_editable')->default(true);
            $table->timestamps();

            $table->unique(['workflow_id', 'step_order', 'approval_level']);
            $table->unique(['workflow_id', 'step_key']);
        });

        Schema::create('workflow_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('workflows')->cascadeOnDelete();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->foreignId('current_step_id')->nullable()->constrained('workflow_steps')->nullOnDelete();
            $table->enum('status', ['pending', 'in_progress', 'approved', 'changes_requested', 'rejected'])->default('pending');
            $table->unsignedInteger('edit_request_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->unique(['workflow_id', 'entity_type', 'entity_id'], 'workflow_instance_unique_entity');
        });

        Schema::create('workflow_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_instance_id')->constrained('workflow_instances')->cascadeOnDelete();
            $table->foreignId('workflow_step_id')->nullable()->constrained('workflow_steps')->nullOnDelete();
            $table->foreignId('acted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('action', ['approved', 'changes_requested', 'rejected', 'commented'])->default('commented');
            $table->text('comment')->nullable();
            $table->unsignedInteger('edit_request_iteration')->default(0);
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_logs');
        Schema::dropIfExists('workflow_instances');
        Schema::dropIfExists('workflow_steps');
        Schema::dropIfExists('workflows');
    }
}
