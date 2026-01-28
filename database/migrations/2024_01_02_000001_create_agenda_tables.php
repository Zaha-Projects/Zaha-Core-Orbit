<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('agenda_events')) {
            Schema::create('agenda_events', function (Blueprint $table) {
                $table->id();
                $table->unsignedTinyInteger('month');
                $table->unsignedTinyInteger('day');
                $table->string('event_name');
                $table->string('event_category')->nullable();
                $table->string('status')->default('draft');
                $table->foreignId('created_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
                $table->timestamp('approved_by_relations_at')->nullable();
                $table->timestamp('approved_by_executive_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('agenda_event_targets')) {
            Schema::create('agenda_event_targets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agenda_event_id')->constrained('agenda_events')->cascadeOnUpdate()->cascadeOnDelete();
                $table->string('target_type');
                $table->unsignedBigInteger('target_id');
                $table->boolean('is_participant')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('agenda_approvals')) {
            Schema::create('agenda_approvals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agenda_event_id')->constrained('agenda_events')->cascadeOnUpdate()->cascadeOnDelete();
                $table->string('step');
                $table->string('decision');
                $table->text('comment')->nullable();
                $table->foreignId('approved_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('agenda_approvals');
        Schema::dropIfExists('agenda_event_targets');
        Schema::dropIfExists('agenda_events');
    }
};
