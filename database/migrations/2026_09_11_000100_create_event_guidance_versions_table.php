<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_guidance_versions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100);
            $table->unsignedInteger('version_number');
            $table->string('title');
            $table->longText('content');
            $table->boolean('is_active')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['code', 'version_number'], 'event_guidance_code_version_unique');
            $table->index(['code', 'is_active', 'published_at'], 'event_guidance_current_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_guidance_versions');
    }
};
