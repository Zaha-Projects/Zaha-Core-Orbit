<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {

        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();
            $table->timestamp('logged_at');
            $table->string('type');
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->string('priority')->nullable();
            $table->string('status')->default('open');
            $table->string('branch_head_status')->nullable();
            $table->text('branch_head_note')->nullable();
            $table->timestamp('branch_head_updated_at')->nullable();
            $table->string('maintenance_track_status')->nullable();
            $table->text('maintenance_track_note')->nullable();
            $table->timestamp('maintenance_track_updated_at')->nullable();
            $table->string('it_track_status')->nullable();
            $table->text('it_track_note')->nullable();
            $table->timestamp('it_track_updated_at')->nullable();
            $table->text('support_resources')->nullable();
            $table->string('support_party')->nullable();
            $table->text('root_cause_branch')->nullable();
            $table->text('root_cause_maintenance')->nullable();
            $table->text('root_cause_it')->nullable();
            $table->text('closure_summary')->nullable();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('center_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('logged_at', 'maintenance_requests_logged_at_idx');
            $table->index('status', 'maintenance_requests_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_requests');
    }
};
