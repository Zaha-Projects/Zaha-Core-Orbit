<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['users', 'monthly_activities', 'maintenance_requests', 'bookings', 'zaha_time_bookings', 'monthly_kpis'] as $table) {
            $this->dropCenterForeignAndMakeNullable($table);
        }

        Schema::dropIfExists('centers');
    }

    public function down(): void
    {
        Schema::create('centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        // Keep center_id nullable in rollback as well to avoid destructive changes.
    }

    private function dropCenterForeignAndMakeNullable(string $tableName): void
    {
        if (! Schema::hasColumn($tableName, 'center_id')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) {
            try {
                $table->dropConstrainedForeignId('center_id');
            } catch (\Throwable $e) {
                $table->dropForeign(['center_id']);
                $table->dropColumn('center_id');
            }
        });

        Schema::table($tableName, function (Blueprint $table) {
            $table->unsignedBigInteger('center_id')->nullable()->after('branch_id');
        });
    }
};
