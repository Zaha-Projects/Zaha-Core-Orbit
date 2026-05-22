<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workflow_action_logs', function (Blueprint $table) {
            $table->id();
            $table->string('module');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->string('action_type');
            $table->string('status')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('performed_at')->nullable();
            $table->timestamps();
            $table->index(['module', 'entity_type', 'entity_id'], 'workflow_entity_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_action_logs');
    }
};

