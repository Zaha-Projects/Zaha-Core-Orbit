<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('agenda_event_partner_departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agenda_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['agenda_event_id', 'department_id'], 'agenda_partner_department_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agenda_event_partner_departments');
    }
};
