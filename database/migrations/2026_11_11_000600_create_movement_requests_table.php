<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Movement Phase 4 (release set 4A): create movement_requests (docs/movement-module-implementation-plan.md §7.6, Revision 5).
 *
 * Category 2 (creates an operational table, §18.0):
 * - up() creates the table.
 * - down() REFUSES to run while the table has rows (RuntimeException naming the table). Rolling back
 *   is allowed only while it is empty, e.g. after a failed deploy; once records exist, fix forward.
 * - FKs follow D19: requester restrict; decided_by / cancelled_by null on delete; branch_id and department_id
 *   are metadata, null on delete. No branch isolation (R5). Drafts only may be soft-deleted.
 */
return new class extends Migration {
    private const TABLE = 'movement_requests';

    public function up(): void
    {
        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->id();
            $table->string('reference_no')->nullable()->unique('movement_requests_reference_unique'); // set right after insert (Revision 5)
            $table->foreignId('requester_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('context_type')->nullable();
            $table->unsignedBigInteger('context_id')->nullable();
            $table->string('context_label')->nullable();
            $table->string('trip_direction'); // one_way | round_trip
            $table->text('purpose')->nullable();
            $table->text('notes')->nullable();
            $table->string('status');
            $table->string('fulfilment_status')->index('movement_requests_fulfilment_idx');
            $table->dateTime('fulfilment_dropped_at')->nullable();
            $table->char('client_token', 36)->nullable()->unique('movement_requests_client_token_unique');
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('decided_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('decision_comment')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'submitted_at'], 'movement_requests_status_submitted_idx');
            $table->index(['context_type', 'context_id'], 'movement_requests_context_idx');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable(self::TABLE) && DB::table(self::TABLE)->exists()) {
            throw new RuntimeException(sprintf(
                'Refusing to drop [%s]: it contains rows. Category 2 migrations roll back only while empty; fix forward instead (docs/movement-module-implementation-plan.md §18.0).',
                self::TABLE
            ));
        }

        Schema::dropIfExists(self::TABLE);
    }
};
