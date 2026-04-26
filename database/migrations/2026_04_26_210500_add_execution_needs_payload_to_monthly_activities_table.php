<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_activities', function (Blueprint $table) {
            if (! Schema::hasColumn('monthly_activities', 'execution_needs_payload')) {
                $table->json('execution_needs_payload')->nullable()->after('requires_communications');
            }
        });
    }

    public function down(): void
    {
        Schema::table('monthly_activities', function (Blueprint $table) {
            if (Schema::hasColumn('monthly_activities', 'execution_needs_payload')) {
                $table->dropColumn('execution_needs_payload');
            }
        });
    }
};
