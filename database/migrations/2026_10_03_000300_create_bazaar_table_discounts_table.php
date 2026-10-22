<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bazaar_table_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bazaar_table_id')->constrained()->cascadeOnDelete();
            $table->decimal('original_amount', 12, 2);
            $table->enum('discount_type', ['amount', 'percentage']);
            $table->decimal('discount_value', 12, 2);
            $table->decimal('final_amount', 12, 2)->nullable();
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'returned', 'rejected'])->default('pending')->index();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at');
            $table->timestamp('approved_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bazaar_table_discounts');
    }
};
