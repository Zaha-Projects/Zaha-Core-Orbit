<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('monthly_activities', function (Blueprint $table) {
            $table->boolean('requires_programs')->default(false)->after('needs_media_coverage');
            $table->boolean('requires_workshops')->default(false)->after('requires_programs');
            $table->boolean('requires_communications')->default(false)->after('requires_workshops');
            $table->string('liaison_approval_status')->default('pending')->after('programs_manager_approval_status');
            $table->string('hq_relations_manager_approval_status')->default('pending')->after('liaison_approval_status');
        });

        Schema::create('activity_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('monthly_activities')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role');
            $table->text('note');
            $table->string('coverage_status')->nullable();
            $table->timestamps();

            $table->index(['activity_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_notes');

        Schema::table('monthly_activities', function (Blueprint $table) {
            $table->dropColumn([
                'requires_programs',
                'requires_workshops',
                'requires_communications',
                'liaison_approval_status',
                'hq_relations_manager_approval_status',
            ]);
        });
    }
};
