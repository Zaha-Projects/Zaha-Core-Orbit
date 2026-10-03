<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('execution_need_types', function (Blueprint $table) {
            $table->timestamp('scope_configured_at')->nullable();
        });
        // Old flags cannot prove whether an administrator selected the scope.
        // Keep them. The settings page offers an explicit, auditable choice.
    }

    public function down(): void
    {
        Schema::table('execution_need_types', fn (Blueprint $table) => $table->dropColumn('scope_configured_at'));
    }
};
