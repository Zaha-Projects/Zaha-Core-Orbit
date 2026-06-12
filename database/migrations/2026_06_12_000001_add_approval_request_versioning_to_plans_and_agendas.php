<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_activities', function (Blueprint $table) {
            if (! Schema::hasColumn('monthly_activities', 'version_number')) {
                $table->unsignedInteger('version_number')->default(1)->after('plan_version');
            }
            if (! Schema::hasColumn('monthly_activities', 'parent_version_id')) {
                $table->foreignId('parent_version_id')->nullable()->after('previous_version_id')->constrained('monthly_activities')->nullOnDelete();
            }
            if (! Schema::hasColumn('monthly_activities', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('agenda_events', function (Blueprint $table) {
            if (! Schema::hasColumn('agenda_events', 'version_number')) {
                $table->unsignedInteger('version_number')->default(1)->after('version');
            }
            if (! Schema::hasColumn('agenda_events', 'parent_version_id')) {
                $table->foreignId('parent_version_id')->nullable()->after('version_number')->constrained('agenda_events')->nullOnDelete();
            }
            if (! Schema::hasColumn('agenda_events', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        $this->createRequestTable('monthly_plan_delete_requests', true, false);
        $this->createRequestTable('monthly_plan_edit_requests', true, true);
        $this->createRequestTable('annual_agenda_delete_requests', false, false);
        $this->createRequestTable('annual_agenda_edit_requests', false, true);
    }

    public function down(): void
    {
        Schema::dropIfExists('annual_agenda_edit_requests');
        Schema::dropIfExists('annual_agenda_delete_requests');
        Schema::dropIfExists('monthly_plan_edit_requests');
        Schema::dropIfExists('monthly_plan_delete_requests');

        Schema::table('agenda_events', function (Blueprint $table) {
            if (Schema::hasColumn('agenda_events', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            if (Schema::hasColumn('agenda_events', 'parent_version_id')) {
                $table->dropConstrainedForeignId('parent_version_id');
            }
            if (Schema::hasColumn('agenda_events', 'version_number')) {
                $table->dropColumn('version_number');
            }
        });

        Schema::table('monthly_activities', function (Blueprint $table) {
            if (Schema::hasColumn('monthly_activities', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            if (Schema::hasColumn('monthly_activities', 'parent_version_id')) {
                $table->dropConstrainedForeignId('parent_version_id');
            }
            if (Schema::hasColumn('monthly_activities', 'version_number')) {
                $table->dropColumn('version_number');
            }
        });
    }

    private function createRequestTable(string $tableName, bool $hasBranch, bool $isEdit): void
    {
        if (Schema::hasTable($tableName)) {
            return;
        }

        Schema::create($tableName, function (Blueprint $table) use ($hasBranch, $isEdit) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->string('request_type');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            if ($hasBranch) {
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            }
            $table->text('reason')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('current_approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('approval_history')->nullable();
            if ($isEdit) {
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->json('changed_values')->nullable();
                $table->foreignId('approved_version_id')->nullable()->constrained($hasBranch ? 'monthly_activities' : 'agenda_events')->nullOnDelete();
            }
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index(['status', 'current_approver_id']);
        });
    }
};
