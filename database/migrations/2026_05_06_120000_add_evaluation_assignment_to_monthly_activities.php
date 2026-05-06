<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('monthly_activities', function (Blueprint $table) {
            $table->foreignId('evaluation_assigned_user_id')->nullable()->after('evaluation_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('evaluation_assigned_at')->nullable()->after('evaluation_assigned_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('monthly_activities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('evaluation_assigned_user_id');
            $table->dropColumn('evaluation_assigned_at');
        });
    }
};
