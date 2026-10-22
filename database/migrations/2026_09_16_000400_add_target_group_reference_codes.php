<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('target_groups', function (Blueprint $table) {
            $table->string('code', 100)->nullable()->unique();
            $table->string('type', 30)->default('community')->after('code');
        });
        foreach (['children' => 'أطفال', 'youth' => 'شباب', 'women' => 'سيدات', 'disability' => 'ذوي إعاقة', 'local_community' => 'مجتمع محلي', 'other' => 'أخرى'] as $code => $name) {
            $id = DB::table('target_groups')->where('name', $name)->orderBy('id')->value('id');
            if ($id) {
                // Keep all referenced legacy IDs, including any duplicate labels.
                DB::table('target_groups')->where('id', $id)->update(['code' => $code, 'type' => in_array($code, ['children', 'youth'], true) ? 'age' : 'community']);
            }
        }
    }

    public function down(): void
    {
        Schema::table('target_groups', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn(['code', 'type']);
        });
    }
};
