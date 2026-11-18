<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Movement Phase 5 (release set 5A): create movement_bookings (docs/movement-module-implementation-plan.md §7.10, §11.7).
 * Phase 5 writes only request bookings (source 'request', confirmed by the manager); joining arrives in Phase 6.
 *
 * Category 2 (creates an operational table, §18.0): down() refuses while the table has rows.
 * - FKs follow D19: trip, request leg, booker and stops restrict (bookings are never deleted, §7.12); actor columns
 *   and the branch metadata null on delete. No branch isolation (R5).
 * - client_token is unique (idempotency, §11.7).
 * - active_join_key is a STORED generated column with a unique index: the database backstop for
 *   "one active join booking per user per trip" (§7.10, §11.7). It is NULL for request and manager bookings.
 */
return new class extends Migration {
    private const TABLE = 'movement_bookings';

    public function up(): void
    {
        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->id();
            $table->foreignId('movement_trip_id')->constrained('movement_trips')->restrictOnDelete();
            $table->foreignId('request_leg_id')->nullable()->constrained('movement_request_legs')->restrictOnDelete();
            $table->string('source'); // request | join | manager
            $table->foreignId('booked_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('context_type')->nullable();
            $table->unsignedBigInteger('context_id')->nullable();
            $table->string('context_label')->nullable();
            $table->foreignId('pickup_stop_id')->constrained('movement_trip_stops')->restrictOnDelete();
            $table->foreignId('dropoff_stop_id')->constrained('movement_trip_stops')->restrictOnDelete();
            $table->unsignedSmallInteger('passenger_count');
            $table->string('contact_phone')->nullable();
            $table->text('notes')->nullable();
            $table->string('status'); // pending | confirmed | rejected | cancelled | no_show
            $table->char('client_token', 36)->nullable()->unique('movement_bookings_client_token_unique');
            $table->string('active_join_key')->nullable()
                ->storedAs("IF(source = 'join' AND status IN ('pending', 'confirmed'), CONCAT(movement_trip_id, '-', booked_by_user_id), NULL)")
                ->unique('movement_bookings_active_join_unique');
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('decided_at')->nullable();
            $table->text('decision_comment')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->index(['movement_trip_id', 'status'], 'movement_bookings_trip_status_idx');
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
