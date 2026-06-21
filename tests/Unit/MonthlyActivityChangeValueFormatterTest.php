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
        ], 'execution_needs_payload')->toHtml();

        $this->assertStringContainsString('الهدايا والدروع', $html);
        $this->assertStringContainsString('جهة التوفير', $html);
        $this->assertStringContainsString('فرع الزرقاء', $html);
        $this->assertStringContainsString('أجندة الحفل', $html);
        $this->assertStringContainsString('فقرة افتتاحية', $html);
        $this->assertStringNotContainsString('{', $html);
        $this->assertStringNotContainsString('need_code', $html);
        $this->assertStringNotContainsString('delivery_entity', $html);
    }
}
