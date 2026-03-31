<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('event_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['department_id', 'name']);
        });

        Schema::create('agenda_participations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agenda_event_id')->constrained()->cascadeOnDelete();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->string('participation_status')->default('unspecified');
            $table->date('proposed_date')->nullable();
            $table->date('actual_execution_date')->nullable();
            $table->string('branch_plan_file')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->unique(['agenda_event_id', 'entity_type', 'entity_id'], 'agenda_entity_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agenda_participations');
        Schema::dropIfExists('event_categories');
    }
};
