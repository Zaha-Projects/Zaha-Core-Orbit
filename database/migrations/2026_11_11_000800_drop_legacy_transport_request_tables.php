<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Movement Phase 4 (release set 4B): retire legacy B, the transport request subsystem
 * (docs/movement-module-implementation-plan.md §18.3, Revision 5). Deploy only after 4A is verified live.
 *
 * Category 4 (destructive legacy retirement, §18.0):
 * - up() drops transport_request_feedback, transport_request_actions, transport_request_trips and
 *   transport_requests (children first). Absent tables are skipped, so the migration is idempotent.
 * - up() refuses to drop a table that still contains rows unless config('movement.legacy.allow_drop_with_rows')
 *   is true (env MOVEMENT_ALLOW_LEGACY_DROP), the explicit "disaster-recovery backup verified" switch (§18.1).
 *   No B data is migrated or archived (R3).
 * - down() recreates the EMPTY structure exactly as defined by the original 2026_02_13_000007{0..3} migrations.
 *   It never restores data: rows are recoverable only from the disaster-recovery backup.
 *
 * C (movement_days/movement_trips) and the old vehicles/drivers are untouched (Phase 5).
 */
return new class extends Migration {
    /** Child tables first, so foreign keys to transport_requests are gone before it. */
    private const TABLES = ['transport_request_feedback', 'transport_request_actions', 'transport_request_trips', 'transport_requests'];

    public function up(): void
    {
        $allowDropWithRows = (bool) config('movement.legacy.allow_drop_with_rows', false);

        // Check every table before dropping any, so a refusal leaves the schema untouched.
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && ! $allowDropWithRows && DB::table($table)->exists()) {
                throw new RuntimeException(sprintf(
                    'Refusing to drop legacy table [%s]: it contains rows. Verify the disaster-recovery backup, '
                    .'then set MOVEMENT_ALLOW_LEGACY_DROP=true for this deploy (see docs/movement-module-implementation-plan.md §18.1).',
                    $table
                ));
            }
        }

        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        // Parents first; each block mirrors its original migration verbatim.
        if (! Schema::hasTable('transport_requests')) {
            Schema::create('transport_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('requester_branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->date('request_date');
                $table->string('day_name')->nullable();
                $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
                $table->string('status')->default('pending');
                $table->text('movement_officer_notes')->nullable();
                $table->text('general_notes')->nullable();
                $table->timestamps();
                $table->index('status', 'transport_requests_status_idx');
            });
        }

        if (! Schema::hasTable('transport_request_trips')) {
            Schema::create('transport_request_trips', function (Blueprint $table) {
                $table->id();
                $table->foreignId('transport_request_id')->constrained()->cascadeOnDelete();
                $table->unsignedTinyInteger('trip_no')->default(1);
                $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
                $table->string('destination');
                $table->string('accompanying_team')->nullable();
                $table->time('departure_time')->nullable();
                $table->time('return_time')->nullable();
                $table->timestamps();
                $table->index('trip_no', 'transport_req_trips_no_idx');
            });
        }

        if (! Schema::hasTable('transport_request_actions')) {
            Schema::create('transport_request_actions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('transport_request_id')->constrained()->cascadeOnDelete();
                $table->string('action_type');
                $table->foreignId('action_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('action_at')->nullable();
                $table->text('comment')->nullable();
                $table->timestamps();
                $table->index('action_type', 'transport_req_actions_type_idx');
            });
        }

        if (! Schema::hasTable('transport_request_feedback')) {
            Schema::create('transport_request_feedback', function (Blueprint $table) {
                $table->id();
                $table->foreignId('transport_request_id')->unique()->constrained()->cascadeOnDelete();
                $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedTinyInteger('punctuality_score')->nullable();
                $table->unsignedTinyInteger('cleanliness_score')->nullable();
                $table->unsignedTinyInteger('driver_behavior_score')->nullable();
                $table->unsignedTinyInteger('overall_score')->nullable();
                $table->text('comment')->nullable();
                $table->timestamps();
            });
        }
    }
};
