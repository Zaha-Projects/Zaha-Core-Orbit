<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('monthly_activities', function (Blueprint $table) {
            $table->string('official_correspondence_target')->nullable()->after('official_correspondence_reason');
            $table->string('outside_contact_number')->nullable()->after('outside_google_maps_url');
        });

        Schema::table('monthly_activity_supplies', function (Blueprint $table) {
            $table->string('provider_type')->nullable()->after('available');
            $table->string('provider_name')->nullable()->after('provider_type');
        });
    }

    public function down(): void
    {
        Schema::table('monthly_activity_supplies', function (Blueprint $table) {
            $table->dropColumn(['provider_type', 'provider_name']);
        });

        Schema::table('monthly_activities', function (Blueprint $table) {
            $table->dropColumn(['official_correspondence_target', 'outside_contact_number']);
        });
    }
};
