<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('monthly_activity_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_activity_id')->constrained()->cascadeOnDelete();
            $table->string('step');
            $table->string('decision');
            $table->text('comment')->nullable();
            $table->boolean('is_edit_request_implemented')->default(false);
            $table->timestamp('implemented_at')->nullable();
            $table->foreignId('approved_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index('monthly_activity_id');
            $table->index('approved_by');
            $table->index(['step', 'decision']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_activity_approvals');
    }
};
