<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Snapshot of the existing monthly form catalogue. No seeder is required
        // to keep pre-deployment monthly forms usable. Canonical admin choices win.
        $definitions = [
            'volunteers' => true,
            'official_correspondence' => true,
            'media_coverage' => true,
            'supplies' => true,
            'official_sponsorship' => false,
            'external_partners' => false,
            'ceremony_agenda' => false,
            'transport' => true,
            'maintenance_workers' => true,
            'gifts_shields' => true,
            'programs_participation' => false,
            'certificates' => false,
            'thanks_letters' => false,
            'invitations' => true,
        ];
        foreach ($definitions as $code => $ramadan) {
            DB::table('execution_need_types')->where('code', $code)->where('is_canonical', false)
                ->update(['is_canonical' => true, 'is_monthly_activity' => true, 'is_ramadan_iftar' => $ramadan]);
        }
    }

    public function down(): void
    {
        // Reference rows may be in use. Never delete them during rollback.
    }
};
