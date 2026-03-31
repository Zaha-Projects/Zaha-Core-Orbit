<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnforceSingleActiveWorkflowPerModule extends Migration
{
    public function up(): void
    {
        Schema::table('workflows', function (Blueprint $table) {
            $table->string('active_module')->nullable()->after('module');
        });

        DB::table('workflows')->update([
            'active_module' => DB::raw('CASE WHEN is_active = 1 THEN module ELSE NULL END'),
        ]);

        Schema::table('workflows', function (Blueprint $table) {
            $table->unique('active_module', 'workflows_unique_active_module');
        });
    }

    public function down(): void
    {
        Schema::table('workflows', function (Blueprint $table) {
            $table->dropUnique('workflows_unique_active_module');
            $table->dropColumn('active_module');
        });
    }
}
