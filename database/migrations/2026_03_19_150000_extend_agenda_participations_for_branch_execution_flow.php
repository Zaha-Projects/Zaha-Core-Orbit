<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('agenda_participations', function (Blueprint $table) {
            $table->date('proposed_date')->nullable()->after('participation_status');
            $table->date('actual_execution_date')->nullable()->after('proposed_date');
            $table->string('branch_plan_file')->nullable()->after('actual_execution_date');
        });
    }

    public function down(): void
    {
        Schema::table('agenda_participations', function (Blueprint $table) {
            $table->dropColumn(['proposed_date', 'actual_execution_date', 'branch_plan_file']);
        });
    }
};
