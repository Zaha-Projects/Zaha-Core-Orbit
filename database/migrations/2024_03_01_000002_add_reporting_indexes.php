<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('donations_cash', function (Blueprint $table) {
            $table->index('date', 'donations_cash_date_idx');
            $table->index('finance_status', 'donations_cash_finance_status_idx');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->index('booking_date', 'bookings_booking_date_idx');
            $table->index('status', 'bookings_status_idx');
        });

        Schema::table('zaha_time_bookings', function (Blueprint $table) {
            $table->index('booking_date', 'zaha_time_booking_date_idx');
            $table->index('status', 'zaha_time_booking_status_idx');
        });

        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->index('logged_at', 'maintenance_requests_logged_at_idx');
            $table->index('status', 'maintenance_requests_status_idx');
        });

        Schema::table('agenda_approvals', function (Blueprint $table) {
            $table->index('approved_at', 'agenda_approvals_approved_at_idx');
        });

        Schema::table('monthly_activity_approvals', function (Blueprint $table) {
            $table->index('approved_at', 'monthly_activity_approvals_approved_at_idx');
        });

        Schema::table('maintenance_approvals', function (Blueprint $table) {
            $table->index('approved_at', 'maintenance_approvals_approved_at_idx');
        });

        Schema::table('trips', function (Blueprint $table) {
            $table->index('trip_date', 'trips_trip_date_idx');
            $table->index('status', 'trips_status_idx');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index('paid_at', 'payments_paid_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('donations_cash', function (Blueprint $table) {
            $table->dropIndex('donations_cash_date_idx');
            $table->dropIndex('donations_cash_finance_status_idx');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_booking_date_idx');
            $table->dropIndex('bookings_status_idx');
        });

        Schema::table('zaha_time_bookings', function (Blueprint $table) {
            $table->dropIndex('zaha_time_booking_date_idx');
            $table->dropIndex('zaha_time_booking_status_idx');
        });

        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->dropIndex('maintenance_requests_logged_at_idx');
            $table->dropIndex('maintenance_requests_status_idx');
        });

        Schema::table('agenda_approvals', function (Blueprint $table) {
            $table->dropIndex('agenda_approvals_approved_at_idx');
        });

        Schema::table('monthly_activity_approvals', function (Blueprint $table) {
            $table->dropIndex('monthly_activity_approvals_approved_at_idx');
        });

        Schema::table('maintenance_approvals', function (Blueprint $table) {
            $table->dropIndex('maintenance_approvals_approved_at_idx');
        });

        Schema::table('trips', function (Blueprint $table) {
            $table->dropIndex('trips_trip_date_idx');
            $table->dropIndex('trips_status_idx');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_paid_at_idx');
        });
    }
};
