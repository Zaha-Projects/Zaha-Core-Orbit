<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('color_hex', 7)->nullable()->after('address');
            $table->string('icon', 32)->nullable()->after('color_hex');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->string('color_hex', 7)->nullable()->after('name');
            $table->string('icon', 32)->nullable()->after('color_hex');
        });

        Schema::table('department_units', function (Blueprint $table) {
            $table->string('color_hex', 7)->nullable()->after('role_name');
            $table->string('icon', 32)->nullable()->after('color_hex');
        });
    }

    public function down(): void
    {
        Schema::table('department_units', function (Blueprint $table) {
            $table->dropColumn(['color_hex', 'icon']);
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn(['color_hex', 'icon']);
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['color_hex', 'icon']);
        });
    }
};
