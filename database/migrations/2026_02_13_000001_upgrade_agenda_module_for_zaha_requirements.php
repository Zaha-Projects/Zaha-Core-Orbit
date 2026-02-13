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
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->unique(['agenda_event_id', 'entity_type', 'entity_id'], 'agenda_entity_unique');
        });

        Schema::table('agenda_events', function (Blueprint $table) {
            $table->date('event_date')->nullable()->after('id');
            $table->string('event_day')->nullable()->after('event_date');
            $table->foreignId('department_id')->nullable()->after('event_name')->constrained()->nullOnDelete();
            $table->foreignId('event_category_id')->nullable()->after('department_id')->constrained('event_categories')->nullOnDelete();
            $table->string('plan_type')->nullable()->after('event_category_id');
            $table->string('event_type')->nullable()->after('plan_type');
            $table->string('relations_approval_status')->default('pending')->after('status');
            $table->string('executive_approval_status')->default('pending')->after('relations_approval_status');

            $table->index('event_date');
            $table->index('event_type');
            $table->index('plan_type');
        });
    }

    public function down(): void
    {
        Schema::table('agenda_events', function (Blueprint $table) {
            $table->dropIndex(['event_date']);
            $table->dropIndex(['event_type']);
            $table->dropIndex(['plan_type']);
            $table->dropConstrainedForeignId('event_category_id');
            $table->dropConstrainedForeignId('department_id');
            $table->dropColumn([
                'event_date',
                'event_day',
                'plan_type',
                'event_type',
                'relations_approval_status',
                'executive_approval_status',
            ]);
        });

        Schema::dropIfExists('agenda_participations');
        Schema::dropIfExists('event_categories');
    }
};

