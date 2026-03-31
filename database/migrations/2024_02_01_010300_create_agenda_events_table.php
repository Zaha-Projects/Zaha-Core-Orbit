<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('agenda_events', function (Blueprint $table) {
            $table->id();
            $table->date('event_date')->nullable();
            $table->string('event_day')->nullable();
            $table->unsignedTinyInteger('month');
            $table->unsignedTinyInteger('day');
            $table->string('event_name');
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('event_category_id')->nullable();
            $table->string('plan_type')->nullable();
            $table->string('event_type')->nullable();
            $table->string('event_category')->nullable();
            $table->string('status')->default('draft');
            $table->string('relations_approval_status')->default('pending');
            $table->string('executive_approval_status')->default('pending');
            $table->boolean('is_archived')->default(false);
            $table->unsignedSmallInteger('archived_year')->nullable();
            $table->boolean('is_mandatory')->default(false);
            $table->boolean('is_unified')->default(true);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('approved_by_relations_at')->nullable();
            $table->timestamp('approved_by_executive_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('agenda_plan_file')->nullable();
            $table->timestamps();

            $table->index('event_date');
            $table->index('event_type');
            $table->index('plan_type');
            $table->index(['is_archived', 'archived_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agenda_events');
    }
};
