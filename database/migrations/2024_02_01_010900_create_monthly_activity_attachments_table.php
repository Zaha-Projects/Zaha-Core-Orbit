<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('monthly_activity_attachments')) {
            return;
        }

        Schema::create('monthly_activity_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_activity_id')->constrained()->cascadeOnDelete();
            $table->string('file_type');
            $table->string('file_path');
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_activity_attachments');
    }
};
