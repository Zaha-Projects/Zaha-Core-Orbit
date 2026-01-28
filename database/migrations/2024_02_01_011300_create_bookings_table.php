<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('bookings')) {
            return;
        }

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->timestamp('received_at');
            $table->date('booking_date');
            $table->time('time_from');
            $table->time('time_to');
            $table->string('received_by');
            $table->string('customer_name');
            $table->string('facility_name');
            $table->string('payment_type');
            $table->string('receipt_ref')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->text('discount_reason')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('center_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
