<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bazaar_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bazaar_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('table_number');
            $table->enum('rental_type', ['individual', 'organization']);
            $table->string('tenant_name')->nullable();
            $table->string('tenant_phone', 25)->nullable();
            $table->foreignId('community_organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('table_liaison_name')->nullable();
            $table->string('table_liaison_phone', 25)->nullable();
            $table->text('planned_material_description');
            $table->decimal('planned_rental_amount', 12, 2);
            $table->foreignId('liaison_user_id')->constrained('users')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->boolean('was_booked')->nullable();
            $table->boolean('actual_renter_matches')->nullable();
            $table->string('actual_tenant_name')->nullable();
            $table->string('actual_tenant_phone', 25)->nullable();
            $table->foreignId('actual_community_organization_id')->nullable()->constrained('community_organizations')->nullOnDelete();
            $table->text('renter_change_note')->nullable();
            $table->text('actual_material_description')->nullable();
            $table->boolean('material_matches')->nullable();
            $table->text('material_mismatch_note')->nullable();
            $table->decimal('amount_due', 12, 2)->nullable();
            $table->boolean('is_paid')->nullable();
            $table->decimal('amount_paid', 12, 2)->nullable();
            $table->enum('payment_status', ['paid', 'partial', 'unpaid', 'approved_discount'])->nullable();
            $table->text('actual_notes')->nullable();
            $table->timestamps();
            $table->unique(['bazaar_id', 'table_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bazaar_tables');
    }
};
