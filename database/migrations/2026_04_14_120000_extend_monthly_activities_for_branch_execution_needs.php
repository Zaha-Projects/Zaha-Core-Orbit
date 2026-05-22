<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('monthly_activities', function (Blueprint $table) {
            $table->string('execution_status')->default('executed')->after('status');
            $table->text('cancellation_reason')->nullable()->after('reschedule_reason');
            $table->string('external_liaison_name')->nullable()->after('outside_contact_number');
            $table->string('external_liaison_phone')->nullable()->after('external_liaison_name');
            $table->text('official_correspondence_brief')->nullable()->after('official_correspondence_target');
        });
    }

    public function down(): void
    {
        Schema::table('monthly_activities', function (Blueprint $table) {
            $table->dropColumn([
                'execution_status',
                'cancellation_reason',
                'external_liaison_name',
                'external_liaison_phone',
                'official_correspondence_brief',
            ]);
        });
    }
};
