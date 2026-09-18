<?php

namespace Database\Seeders;

use App\Modules\Events\Models\EventGuidanceVersion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RamadanIftarGuidanceSeeder extends Seeder
{
    public function run(): void
    {
        $source = public_path('تعليمات افطارات رمضان 2026.pdf');
        $document = json_decode(file_get_contents(__DIR__.'/data/ramadan-guidance.json'), true, 512, JSON_THROW_ON_ERROR);
        if (! is_file($source) || hash_file('sha256', $source) !== $document['source_sha256']) {
            throw new \RuntimeException('Missing or changed guidance PDF: '.$source.'. Re-extract and review the source before seeding.');
        }

        DB::transaction(function () use ($document): void {
            $versions = DB::table('event_guidance_versions')
                ->where('code', EventGuidanceVersion::RAMADAN_IFTAR)->lockForUpdate()->get();
            if ($versions->contains('source_sha256', $document['source_sha256'])) {
                DB::table('event_guidance_versions')
                    ->where('code', EventGuidanceVersion::RAMADAN_IFTAR)
                    ->where('source_sha256', $document['source_sha256'])
                    ->update(['title' => 'تعليمات عامة لإفطارات رمضان', 'updated_at' => now()]);
                return;
            }

            $inserted = DB::table('event_guidance_versions')->insertOrIgnore([
                'code' => EventGuidanceVersion::RAMADAN_IFTAR,
                'version_number' => ((int) $versions->max('version_number')) + 1,
                'source_sha256' => $document['source_sha256'],
                'title' => 'تعليمات عامة لإفطارات رمضان',
                'content' => json_encode($document['sections'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                'is_active' => true, 'published_at' => now(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            if ($inserted) {
                // Preserve historical text and acknowledgements; publish a new version.
                DB::table('event_guidance_versions')->whereIn('id', $versions->pluck('id'))
                    ->update(['is_active' => false, 'updated_at' => now()]);
            }
        }, 5);
    }
}
