<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {

        Schema::create('donations_cash', function (Blueprint $table) {
            $table->id();
            $table->string('donor_type');
            $table->string('donor_name');
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->unsignedTinyInteger('day')->nullable();
            $table->date('date');
            $table->decimal('amount', 12, 2);
            $table->string('payment_method');
            $table->string('receipt_no')->nullable();
            $table->string('purpose_type');
            $table->foreignId('monthly_activity_id')->nullable()->constrained('monthly_activities')->nullOnDelete();
            $table->string('finance_status')->default('pending');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations_cash');
    }
};
