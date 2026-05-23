<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration {public function up(): void {Schema::create('roles', function (Blueprint $table) {$table->bigIncrements('id');$table->string('name');$table->string('name_ar')->nullable();$table->string('name_en')->nullable();$table->string('guard_name');$table->timestamps();$table->unique(['name','guard_name']);});} public function down(): void {Schema::dropIfExists('roles');}};
