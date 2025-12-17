<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donations_cash', function (Blueprint $table) {
            $table->id();
            $table->string('donor_type');
            $table->string('donor_name')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->unsignedTinyInteger('day')->nullable();
            $table->date('date')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method')->nullable();
            $table->string('receipt_no')->nullable();
            $table->string('purpose_type');
            $table->foreignId('monthly_activity_id')->nullable()->constrained('monthly_activities')->cascadeOnUpdate()->nullOnDelete();
            $table->string('finance_status')->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->timestamp('received_at')->nullable();
            $table->date('booking_date');
            $table->time('time_from')->nullable();
            $table->time('time_to')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->string('customer_name');
            $table->string('facility_name');
            $table->string('payment_type')->nullable();
            $table->string('receipt_ref')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->string('discount_reason')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('center_id')->nullable()->constrained('centers')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('zaha_time_bookings', function (Blueprint $table) {
            $table->id();
            $table->timestamp('received_at')->nullable();
            $table->date('booking_date');
            $table->time('time_from')->nullable();
            $table->time('time_to')->nullable();
            $table->string('entity_type');
            $table->string('contact_person');
            $table->string('phone')->nullable();
            $table->unsignedInteger('children_count')->nullable();
            $table->string('payment_cash_ref')->nullable();
            $table->string('payment_electronic_ref')->nullable();
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->string('discount_reason')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('center_id')->nullable()->constrained('centers')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payable_type');
            $table->unsignedBigInteger('payable_id');
            $table->string('method');
            $table->decimal('amount', 12, 2);
            $table->string('reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('zaha_time_bookings');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('donations_cash');
    }
};
