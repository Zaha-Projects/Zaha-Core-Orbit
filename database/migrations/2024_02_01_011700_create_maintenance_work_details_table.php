<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('maintenance_work_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_request_id')->constrained()->cascadeOnDelete();
            $table->timestamp('start_from')->nullable();
            $table->timestamp('end_to')->nullable();
            $table->text('team_desc')->nullable();
            $table->string('resources_type');
            $table->string('support_party')->nullable();
            $table->decimal('estimated_cost', 12, 2)->nullable();
            $table->text('root_cause_analysis')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_work_details');
    }
};
