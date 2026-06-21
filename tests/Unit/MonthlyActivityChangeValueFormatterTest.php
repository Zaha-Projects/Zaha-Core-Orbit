<?php

namespace Tests\Unit;

use App\Support\MonthlyActivityChangeValueFormatter;
use PHPUnit\Framework\TestCase;

class MonthlyActivityChangeValueFormatterTest extends TestCase
{
    public function test_execution_needs_payload_arrays_render_as_readable_text_not_raw_json(): void
    {
        $html = MonthlyActivityChangeValueFormatter::format([
            'gifts' => [
                'count' => null,
                'need_code' => 'gifts',
                'description' => null,
                'delivery_entity' => 'فرع الزرقاء',
                'future_cycle_id' => null,
            ],
            'ceremony' => [
                'items' => [
                    ['name' => 'فقرة افتتاحية', 'time' => '10:00'],
                ],
            ],
            'needs_registry' => [
                'gifts' => ['enabled' => true, 'availability' => 'not_available'],
                'maintenance' => ['enabled' => false, 'availability' => 'not_available'],
            ],
            'availability' => [
                'gifts' => 'not_available',
                'maintenance' => 'not_available',
            ],
            'schema_version' => 2,
            'needs_transport' => false,
            'needs_maintenance_workers' => false,
            'maintenance' => [
                'need_code' => 'maintenance',
                'type' => null,
            ],
            'programs' => [
                'need_trainer' => true,
                'trainer_description' => 'مدرب ورشة عائلية',
                'zaha_time_options' => ['storytelling'],
            ],
        ], 'execution_needs_payload')->toHtml();

        $this->assertStringContainsString('الهدايا والدروع', $html);
        $this->assertStringContainsString('جهة التوفير', $html);
        $this->assertStringContainsString('فرع الزرقاء', $html);
        $this->assertStringContainsString('أجندة الحفل', $html);
        $this->assertStringContainsString('فقرة افتتاحية', $html);
        $this->assertStringContainsString('سرد قصصي', $html);
        $this->assertStringNotContainsString('{', $html);
        $this->assertStringNotContainsString('need_code', $html);
        $this->assertStringNotContainsString('delivery_entity', $html);
        $this->assertStringNotContainsString('Needs', $html);
        $this->assertStringNotContainsString('Enabled', $html);
        $this->assertStringNotContainsString('Schema Version', $html);
        $this->assertStringNotContainsString('Maintenance', $html);
        $this->assertStringNotContainsString('storytelling', $html);
        $this->assertStringNotContainsString('غير متوفر داخل المركز', $html);
    }
}
