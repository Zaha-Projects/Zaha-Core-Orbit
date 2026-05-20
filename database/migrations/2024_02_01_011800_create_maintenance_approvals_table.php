<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {

        Schema::create('maintenance_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_request_id')->constrained()->cascadeOnDelete();
            $table->string('step');
            $table->string('decision');
            $table->text('comment')->nullable();
            $table->foreignId('approved_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index('maintenance_request_id');
            $table->index('approved_by');
            $table->index('approved_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_approvals');
    }
};
