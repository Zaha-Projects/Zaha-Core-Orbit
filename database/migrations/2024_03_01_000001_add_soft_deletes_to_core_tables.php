<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $tables = [
            'branches',
            'centers',
            'departments',
            'attachments',
            'audit_logs',
            'vehicles',
            'drivers',
            'trips',
            'payments',
        ];

        foreach ($tables as $table) {
            if (!Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $tableBlueprint) {
                    $tableBlueprint->softDeletes();
                });
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'branches',
            'centers',
            'departments',
            'attachments',
            'audit_logs',
            'vehicles',
            'drivers',
            'trips',
            'payments',
        ];

        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $tableBlueprint) {
                    $tableBlueprint->dropSoftDeletes();
                });
            }
        }
    }
};
