<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('name');
            $table->unsignedInteger('sort_order')->default(0)->after('is_active');
        });

        Schema::table('department_units', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('name');
            $table->unsignedInteger('sort_order')->default(0)->after('is_active');
        });

        Schema::table('event_categories', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('active');
        });

        Schema::create('event_status_lookups', function (Blueprint $table) {
            $table->id();
            $table->string('module', 50);
            $table->string('code', 100);
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['module', 'code']);
            $table->index(['module', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_status_lookups');

        Schema::table('event_categories', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });

        Schema::table('department_units', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'sort_order']);
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'sort_order']);
        });
    }
};
