<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_organizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->string('location_name')->nullable();
            $table->text('address')->nullable();
            $table->string('google_maps_url', 2048)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['branch_id', 'is_active'], 'community_organizations_branch_active_idx');
            $table->index('name', 'community_organizations_name_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_organizations');
    }
};
