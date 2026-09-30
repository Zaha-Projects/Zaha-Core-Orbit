<?php

namespace Database\Seeders;

use App\Modules\Events\Models\BeneficiarySegment;
use Illuminate\Database\Seeder;

class BeneficiarySegmentSeeder extends Seeder
{
    public function run(): void
    {
        $segments = [
            ['code' => 'orphans', 'name_ar' => 'أيتام', 'name_en' => 'Orphans'],
            ['code' => 'women', 'name_ar' => 'سيدات', 'name_en' => 'Women'],
            ['code' => 'local_community', 'name_ar' => 'مجتمع محلي', 'name_en' => 'Local community'],
            ['code' => 'special_needs', 'name_ar' => 'ذوي احتياجات خاصة', 'name_en' => 'People with special needs'],
            ['code' => 'other', 'name_ar' => 'أخرى', 'name_en' => 'Other', 'is_other' => true],
        ];

        foreach ($segments as $index => $segment) {
            BeneficiarySegment::query()->updateOrCreate(['code' => $segment['code']], [
                'name_ar' => $segment['name_ar'],
                'name_en' => $segment['name_en'],
                'dimension' => BeneficiarySegment::DIMENSION_SOCIAL,
                'minimum_age' => null,
                'maximum_age' => null,
                'is_other' => (bool) ($segment['is_other'] ?? false),
                'is_active' => true,
                'sort_order' => ($index + 1) * 10,
            ]);
        }

        BeneficiarySegment::query()->whereNotIn('code', collect($segments)->pluck('code'))->update(['is_active' => false]);
    }
}
