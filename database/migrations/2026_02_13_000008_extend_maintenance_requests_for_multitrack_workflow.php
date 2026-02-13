<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->string('branch_head_status')->nullable()->after('status');
            $table->text('branch_head_note')->nullable()->after('branch_head_status');
            $table->timestamp('branch_head_updated_at')->nullable()->after('branch_head_note');

            $table->string('maintenance_track_status')->nullable()->after('branch_head_updated_at');
            $table->text('maintenance_track_note')->nullable()->after('maintenance_track_status');
            $table->timestamp('maintenance_track_updated_at')->nullable()->after('maintenance_track_note');

            $table->string('it_track_status')->nullable()->after('maintenance_track_updated_at');
            $table->text('it_track_note')->nullable()->after('it_track_status');
            $table->timestamp('it_track_updated_at')->nullable()->after('it_track_note');

            $table->text('support_resources')->nullable()->after('it_track_updated_at');
            $table->string('support_party')->nullable()->after('support_resources');
            $table->text('root_cause_branch')->nullable()->after('support_party');
            $table->text('root_cause_maintenance')->nullable()->after('root_cause_branch');
            $table->text('root_cause_it')->nullable()->after('root_cause_maintenance');
            $table->text('closure_summary')->nullable()->after('root_cause_it');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->dropColumn([
                'branch_head_status',
                'branch_head_note',
                'branch_head_updated_at',
                'maintenance_track_status',
                'maintenance_track_note',
                'maintenance_track_updated_at',
                'it_track_status',
                'it_track_note',
                'it_track_updated_at',
                'support_resources',
                'support_party',
                'root_cause_branch',
                'root_cause_maintenance',
                'root_cause_it',
                'closure_summary',
            ]);
        });
    }
};
