<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workflow_steps', function (Blueprint $table): void {
            $table->string('condition_field')->nullable()->after('permission_id');
            $table->string('condition_value')->nullable()->after('condition_field');
        });
    }

    public function down(): void
    {
        Schema::table('workflow_steps', function (Blueprint $table): void {
            $table->dropColumn(['condition_field', 'condition_value']);
        });
    }
};
