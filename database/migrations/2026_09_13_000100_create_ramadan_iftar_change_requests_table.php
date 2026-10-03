<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ramadan_iftar_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ramadan_iftar_id')->constrained('ramadan_iftars')->restrictOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->text('reason');
            $table->string('status', 30)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_comment')->nullable();
            $table->foreignId('created_version_id')->nullable()->constrained('ramadan_iftars')->nullOnDelete();
            $table->timestamps();
            $table->index(['branch_id', 'status']);
            $table->index(['ramadan_iftar_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ramadan_iftar_change_requests');
    }
};
