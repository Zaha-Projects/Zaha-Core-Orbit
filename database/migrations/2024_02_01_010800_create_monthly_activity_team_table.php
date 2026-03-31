<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('monthly_activity_team', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_activity_id')->constrained()->cascadeOnDelete();
            $table->string('team_name')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('member_name');
            $table->string('member_email')->nullable();
            $table->string('role_desc')->nullable();
            $table->timestamps();

            $table->unique(['monthly_activity_id', 'member_email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_activity_team');
    }
};
