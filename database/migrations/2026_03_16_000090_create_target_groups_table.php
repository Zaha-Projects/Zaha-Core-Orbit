<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration {public function up(): void {Schema::create('target_groups', function (Blueprint $table) {$table->id();$table->string('name');$table->boolean('is_other')->default(false);$table->boolean('is_active')->default(true);$table->unsignedInteger('sort_order')->default(0);$table->timestamps();$table->index('is_active','target_groups_active_idx');});} public function down(): void {Schema::dropIfExists('target_groups');}};
