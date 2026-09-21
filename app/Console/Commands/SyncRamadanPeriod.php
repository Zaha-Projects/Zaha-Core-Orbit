<?php

namespace App\Console\Commands;

use App\Modules\Events\Models\RamadanPeriod;
use App\Modules\Events\Services\RamadanPeriodSyncService;
use Illuminate\Console\Command;

class SyncRamadanPeriod extends Command
{
    protected $signature = 'ramadan:sync-period {year? : Gregorian year containing Ramadan day 1}';
    protected $description = 'Create or refresh a non-authoritative Ramadan period proposal';

    public function handle(RamadanPeriodSyncService $sync): int
    {
        $year = $this->argument('year');
        $year = $year === null ? max(now()->year, ((int) RamadanPeriod::query()->max('year')) + 1) : (int) $year;
        $period = $sync->sync($year);
        $this->info("Proposal synced for {$period->year}: {$period->suggested_start_date->toDateString()} - {$period->suggested_end_date->toDateString()} ({$period->calculation_source}).");
        $this->warn('Proposal only: review, confirm, and activate it explicitly in Ramadan Admin.');
        return self::SUCCESS;
    }
}
