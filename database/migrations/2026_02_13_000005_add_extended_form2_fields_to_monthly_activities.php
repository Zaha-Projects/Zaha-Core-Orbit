<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('monthly_activities', function (Blueprint $table) {
            $table->date('rescheduled_date')->nullable()->after('modified_proposed_date');
            $table->text('reschedule_reason')->nullable()->after('rescheduled_date');
            $table->boolean('relations_approval_on_reschedule')->default(false)->after('reschedule_reason');
            $table->string('responsible_party')->nullable()->after('is_in_agenda');
            $table->boolean('has_sponsor')->default(false)->after('description');
            $table->string('sponsor_name_title')->nullable()->after('has_sponsor');
            $table->boolean('has_partners')->default(false)->after('sponsor_name_title');
            $table->string('partner_1_name')->nullable()->after('has_partners');
            $table->string('partner_1_role')->nullable()->after('partner_1_name');
            $table->string('partner_2_name')->nullable()->after('partner_1_role');
            $table->string('partner_2_role')->nullable()->after('partner_2_name');
            $table->string('partner_3_name')->nullable()->after('partner_2_role');
            $table->string('partner_3_role')->nullable()->after('partner_3_name');
            $table->string('letter_purpose')->nullable()->after('needs_official_letters');
            $table->string('execution_time')->nullable()->after('time_to');
            $table->string('target_group')->nullable()->after('execution_time');
            $table->text('short_description')->nullable()->after('target_group');
            $table->string('volunteer_need')->nullable()->after('short_description');
            $table->decimal('audience_satisfaction_percent', 5, 2)->nullable()->after('volunteer_need');
            $table->decimal('evaluation_score', 5, 2)->nullable()->after('audience_satisfaction_percent');
        });
    }

    public function down(): void
    {
        Schema::table('monthly_activities', function (Blueprint $table) {
            $table->dropColumn([
                'rescheduled_date',
                'reschedule_reason',
                'relations_approval_on_reschedule',
                'responsible_party',
                'has_sponsor',
                'sponsor_name_title',
                'has_partners',
                'partner_1_name',
                'partner_1_role',
                'partner_2_name',
                'partner_2_role',
                'partner_3_name',
                'partner_3_role',
                'letter_purpose',
                'execution_time',
                'target_group',
                'short_description',
                'volunteer_need',
                'audience_satisfaction_percent',
                'evaluation_score',
            ]);
        });
    }
};

