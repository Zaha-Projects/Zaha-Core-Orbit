<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ramadan_periods', function (Blueprint $table) {
            $table->unsignedSmallInteger('hijri_year')->nullable()->after('year');
            $table->date('suggested_start_date')->nullable()->after('end_date');
            $table->date('suggested_end_date')->nullable()->after('suggested_start_date');
            $table->string('calculation_source', 40)->nullable()->after('suggested_end_date');
            $table->timestamp('synced_at')->nullable()->after('calculation_source');
            $table->boolean('is_confirmed')->default(false)->after('synced_at');
        });
        DB::table('ramadan_periods')->where('is_active', true)->update(['is_confirmed' => true]);

        Schema::table('ramadan_iftars', function (Blueprint $table) {
            $table->unsignedBigInteger('ramadan_period_id')->nullable()->after('branch_id');
            $table->foreign('ramadan_period_id', 'ri_period_fk')->references('id')->on('ramadan_periods')->nullOnDelete();
            $table->index(['ramadan_period_id', 'planned_date'], 'ri_period_date_idx');
        });

        // Backfill only an unambiguous date match. Unmatched historical rows remain readable.
        $periods = DB::table('ramadan_periods')->orderBy('id')->get();
        DB::table('ramadan_iftars')->whereNull('ramadan_period_id')->orderBy('id')
            ->chunkById(500, function ($iftars) use ($periods): void {
                foreach ($iftars as $iftar) {
                    $matches = $periods->filter(fn ($period) => $iftar->planned_date >= $period->start_date && $iftar->planned_date <= $period->end_date);
                    if ($matches->count() === 1) {
                        DB::table('ramadan_iftars')->where('id', $iftar->id)->update(['ramadan_period_id' => $matches->first()->id]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('ramadan_iftars', function (Blueprint $table) {
            $table->dropForeign('ri_period_fk');
            $table->dropIndex('ri_period_date_idx');
            $table->dropColumn('ramadan_period_id');
        });
        Schema::table('ramadan_periods', fn (Blueprint $table) => $table->dropColumn(['hijri_year', 'suggested_start_date', 'suggested_end_date', 'calculation_source', 'synced_at', 'is_confirmed']));
    }
};
