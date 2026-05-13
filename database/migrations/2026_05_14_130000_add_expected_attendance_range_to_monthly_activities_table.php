<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('monthly_activities', function (Blueprint $table): void {
            if (! Schema::hasColumn('monthly_activities', 'expected_attendance_from')) {
                $table->unsignedInteger('expected_attendance_from')->nullable()->after('expected_attendance');
            }

            if (! Schema::hasColumn('monthly_activities', 'expected_attendance_to')) {
                $table->unsignedInteger('expected_attendance_to')->nullable()->after('expected_attendance_from');
            }
        });

        DB::table('monthly_activities')
            ->whereNotNull('expected_attendance')
            ->whereNull('expected_attendance_from')
            ->whereNull('expected_attendance_to')
            ->update([
                'expected_attendance_from' => DB::raw('expected_attendance'),
                'expected_attendance_to' => DB::raw('expected_attendance'),
            ]);
    }

    public function down(): void
    {
        Schema::table('monthly_activities', function (Blueprint $table): void {
            if (Schema::hasColumn('monthly_activities', 'expected_attendance_to')) {
                $table->dropColumn('expected_attendance_to');
            }

            if (Schema::hasColumn('monthly_activities', 'expected_attendance_from')) {
                $table->dropColumn('expected_attendance_from');
            }
        });
    }
};
