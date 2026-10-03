<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('target_groups', fn (Blueprint $table) => $table->boolean('is_bazaar')->default(true)->after('is_ramadan_iftar'));
        DB::table('execution_need_types')->orderBy('id')->get(['id', 'module_config', 'is_monthly_activity'])->each(function ($need): void {
            $config = json_decode($need->module_config ?: '{}', true) ?: [];
            $config['bazaar'] ??= ['available' => (bool) $need->is_monthly_activity, 'required' => false];
            DB::table('execution_need_types')->where('id', $need->id)->update(['module_config' => json_encode($config, JSON_UNESCAPED_UNICODE)]);
        });

        Schema::create('bazaars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('relations_officer_id')->constrained('users')->restrictOnDelete();
            $table->string('name');
            $table->date('bazaar_date');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->enum('location_type', ['inside', 'outside']);
            $table->string('location_name');
            $table->text('location_details')->nullable();
            $table->string('map_url', 2048)->nullable();
            $table->unsignedInteger('planned_table_count');
            $table->unsignedInteger('actual_occupied_table_count')->nullable();
            $table->string('status')->default('draft')->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('execution_started_at')->nullable();
            $table->timestamp('post_execution_submitted_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_note')->nullable();
            $table->timestamps();
        });

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
        Schema::dropIfExists('bazaar_tables');
        Schema::dropIfExists('bazaars');
        DB::table('execution_need_types')->orderBy('id')->get(['id', 'module_config'])->each(function ($need): void {
            $config = json_decode($need->module_config ?: '{}', true) ?: [];
            unset($config['bazaar']);
            DB::table('execution_need_types')->where('id', $need->id)->update(['module_config' => json_encode($config, JSON_UNESCAPED_UNICODE)]);
        });
        Schema::table('target_groups', fn (Blueprint $table) => $table->dropColumn('is_bazaar'));
    }
};
