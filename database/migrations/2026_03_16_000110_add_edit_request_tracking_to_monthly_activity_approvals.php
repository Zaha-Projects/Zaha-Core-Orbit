<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('monthly_activity_approvals', function (Blueprint $table) {
            $table->boolean('is_edit_request_implemented')->default(false)->after('comment');
            $table->timestamp('implemented_at')->nullable()->after('is_edit_request_implemented');
        });
    }

    public function down(): void
    {
        Schema::table('monthly_activity_approvals', function (Blueprint $table) {
            $table->dropColumn(['is_edit_request_implemented', 'implemented_at']);
        });
    }
};
