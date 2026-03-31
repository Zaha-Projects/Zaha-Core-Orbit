<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('monthly_activity_sponsors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_activity_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('title')->nullable();
            $table->boolean('is_official')->default(true);
            $table->timestamps();
        });

        Schema::create('monthly_activity_partners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_activity_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('role')->nullable();
            $table->string('contact_info')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['monthly_activity_id', 'name']);
        });

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
        Schema::dropIfExists('monthly_activity_partners');
        Schema::dropIfExists('monthly_activity_sponsors');
    }
};
