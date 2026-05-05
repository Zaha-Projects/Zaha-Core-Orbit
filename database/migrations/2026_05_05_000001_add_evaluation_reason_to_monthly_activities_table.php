<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('monthly_activities', function (Blueprint $table) {
            if (! Schema::hasColumn('monthly_activities', 'evaluation_reason')) {
                $table->text('evaluation_reason')->nullable()->after('evaluation_score');
            }
        });
    }

    public function down(): void
    {
        Schema::table('monthly_activities', function (Blueprint $table) {
            if (Schema::hasColumn('monthly_activities', 'evaluation_reason')) {
                $table->dropColumn('evaluation_reason');
            }
        });
    }
};
