<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('monthly_activity_needs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_activity_id')->constrained()->cascadeOnDelete();
            $table->string('need_key');
            $table->boolean('is_required')->default(false);
            $table->timestamps();

            $table->unique(['monthly_activity_id', 'need_key']);
            $table->index(['need_key', 'is_required']);
            $table->index('monthly_activity_id');
        });

        Schema::create('monthly_activity_need_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_activity_id')->constrained()->cascadeOnDelete();
            $table->string('need_key');
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->unique(['monthly_activity_id', 'need_key']);
            $table->index('monthly_activity_id');
            $table->index('need_key');
        });

        Schema::create('monthly_activity_need_followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_activity_id')->constrained()->cascadeOnDelete();
            $table->string('need_key');
            $table->json('followup')->nullable();
            $table->json('post_execution')->nullable();
            $table->timestamps();

            $table->unique(['monthly_activity_id', 'need_key']);
            $table->index('monthly_activity_id');
            $table->index('need_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_activity_need_followups');
        Schema::dropIfExists('monthly_activity_need_details');
        Schema::dropIfExists('monthly_activity_needs');
    }
};
