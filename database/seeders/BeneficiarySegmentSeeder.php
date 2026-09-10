<?php

namespace Database\Seeders;

use App\Modules\Events\Models\BeneficiarySegment;
use Illuminate\Database\Seeder;

class BeneficiarySegmentSeeder extends Seeder
{
    public function run(): void
    {
        $segments = [
            ['code' => 'children', 'name_ar' => 'أطفال', 'name_en' => 'Children', 'dimension' => BeneficiarySegment::DIMENSION_AGE],
            ['code' => 'adolescents', 'name_ar' => 'يافعون', 'name_en' => 'Adolescents', 'dimension' => BeneficiarySegment::DIMENSION_AGE],
            ['code' => 'youth', 'name_ar' => 'شباب', 'name_en' => 'Youth', 'dimension' => BeneficiarySegment::DIMENSION_AGE],
            ['code' => 'women', 'name_ar' => 'سيدات', 'name_en' => 'Women', 'dimension' => BeneficiarySegment::DIMENSION_GENDER],
            ['code' => 'other', 'name_ar' => 'أخرى', 'name_en' => 'Other', 'dimension' => BeneficiarySegment::DIMENSION_OTHER, 'is_other' => true],
        ];

        foreach ($segments as $index => $segment) {
            BeneficiarySegment::query()->updateOrCreate(
                ['code' => $segment['code']],
                [
                    'name_ar' => $segment['name_ar'],
                    'name_en' => $segment['name_en'],
                    'dimension' => $segment['dimension'],
                    'minimum_age' => null,
                    'maximum_age' => null,
                    'is_other' => (bool) ($segment['is_other'] ?? false),
                    'is_active' => true,
                    'sort_order' => ($index + 1) * 10,
                ]
            );
        }
    }
}
