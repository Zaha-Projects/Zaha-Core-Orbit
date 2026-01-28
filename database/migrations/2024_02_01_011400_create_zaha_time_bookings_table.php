<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('zaha_time_bookings')) {
            return;
        }

        Schema::create('zaha_time_bookings', function (Blueprint $table) {
            $table->id();
            $table->timestamp('received_at');
            $table->date('booking_date');
            $table->time('time_from');
            $table->time('time_to');
            $table->string('entity_type');
            $table->string('contact_person');
            $table->string('phone');
            $table->unsignedInteger('children_count')->default(0);
            $table->string('payment_cash_ref')->nullable();
            $table->string('payment_electronic_ref')->nullable();
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
        Schema::dropIfExists('zaha_time_bookings');
    }
};
