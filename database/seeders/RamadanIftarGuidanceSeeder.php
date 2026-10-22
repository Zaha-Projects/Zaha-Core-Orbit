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
                return; // Administrator ownership begins after the initial insert.
            }

            $inserted = DB::table('event_guidance_versions')->insertOrIgnore([
                'code' => EventGuidanceVersion::RAMADAN_IFTAR,
                'version_number' => ((int) $versions->max('version_number')) + 1,
                'source_sha256' => $document['source_sha256'],
                'title' => $document['title'],
                'content' => json_encode($document['sections'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                // Seeders provide initial content; they never displace an administrator's current version.
                'is_active' => $versions->isEmpty(), 'published_at' => $versions->isEmpty() ? now() : null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }, 5);
    }
}
