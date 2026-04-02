<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
        // إعادة إنشاء جدول المراكز (fallback)
        Schema::create('centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        // ملاحظة: ما رح نرجع center_id للجداول لتجنب تعقيد rollback
    }

private function dropCenterColumn(string $tableName): void
{
    if (!Schema::hasColumn($tableName, 'center_id')) {
        return;
    }

    Schema::table($tableName, function (Blueprint $table) use ($tableName) {

        // حاول حذف foreign key بالاسم المتوقع
        try {
            $table->dropForeign($tableName . '_center_id_foreign');
        } catch (\Throwable $e) {
            try {
                $table->dropForeign(['center_id']);
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // حذف العمود
        try {
            $table->dropColumn('center_id');
        } catch (\Throwable $e) {
            // ignore
        }
    });
}

    private function getForeignKeyName(string $table): string
    {
        return $table . '_center_id_foreign';
    }
};