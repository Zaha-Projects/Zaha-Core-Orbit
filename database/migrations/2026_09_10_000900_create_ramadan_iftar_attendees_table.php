<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ramadan_iftar_attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ramadan_iftar_id')->constrained('ramadan_iftars')->cascadeOnDelete();
            $table->string('full_name');
            $table->string('phone', 50)->nullable();
            $table->unsignedSmallInteger('age')->nullable();
            $table->foreignId('target_group_id')->nullable()->constrained('target_groups')->nullOnDelete();
            $table->foreignId('beneficiary_segment_id')->nullable()->constrained('beneficiary_segments')->nullOnDelete();
            $table->boolean('attended')->default(false);
            $table->timestamp('checked_in_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['ramadan_iftar_id', 'attended'], 'ramadan_attendees_iftar_attended_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ramadan_iftar_attendees');
    }
};
