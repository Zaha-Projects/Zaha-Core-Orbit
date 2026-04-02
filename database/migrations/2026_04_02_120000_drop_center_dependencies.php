<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $tables = [
            'users',
            'monthly_activities',
            'maintenance_requests',
            'bookings',
            'zaha_time_bookings',
            'monthly_kpis',
        ];

        foreach ($tables as $table) {
            $this->dropCenterColumn($table);
        }

        // حذف جدول المراكز نهائياً
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
    }

    private function dropCenterColumn(string $tableName): void
    {
        // 🔥 أولاً: تأكد من وجود العمود من database مباشرة (مش cache)
        $columnExists = DB::select("
            SHOW COLUMNS FROM `$tableName` LIKE 'center_id'
        ");

        if (empty($columnExists)) {
            return;
        }

        // 🔥 حذف foreign key إذا موجود
        try {
            DB::statement("ALTER TABLE `$tableName` DROP FOREIGN KEY `{$tableName}_center_id_foreign`");
        } catch (\Throwable $e) {
            try {
                DB::statement("ALTER TABLE `$tableName` DROP FOREIGN KEY `{$tableName}_center_id_foreign_1`");
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // 🔥 حذف العمود (حتى لو حاول ينحذف مرتين ما بكسر)
        try {
            DB::statement("ALTER TABLE `$tableName` DROP COLUMN `center_id`");
        } catch (\Throwable $e) {
            // ignore
        }
    }
};