<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('roles')
            ->where('name', 'branch_relations_manager')
            ->update(['name' => 'supervisor', 'name_en' => 'Supervisor', 'name_ar' => 'رئيس فرع']);
    }

    public function down(): void
    {
        DB::table('roles')
            ->where('name', 'supervisor')
            ->update(['name' => 'branch_relations_manager', 'name_en' => 'Supervisor', 'name_ar' => 'رئيس فرع']);
    }
};
