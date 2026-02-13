<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('monthly_activities', function (Blueprint $table) {
            $table->timestamp('lock_at')->nullable()->after('status');
            $table->boolean('is_official')->default(false)->after('lock_at');
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('monthly_activity_change_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('field_name');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->timestamp('changed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_activity_change_logs');
        Schema::dropIfExists('settings');

        Schema::table('monthly_activities', function (Blueprint $table) {
            $table->dropColumn(['lock_at', 'is_official']);
        });
    }
};

