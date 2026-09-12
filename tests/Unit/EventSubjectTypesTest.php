<?php

namespace Tests\Unit;

use App\Models\MonthlyActivity;
use App\Modules\Events\Models\EventSubjectTypes;
use App\Modules\Events\Models\RamadanIftar;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class EventSubjectTypesTest extends TestCase
{
    public function test_reserved_aliases_are_exact_and_stable(): void
    {
        $this->assertSame(
            ['monthly_activity', 'ramadan_iftar'],
            EventSubjectTypes::reserved()
        );
    }

    public function test_real_event_subject_models_are_registered_and_resolve(): void
    {
        $this->assertSame(
            [
                EventSubjectTypes::MONTHLY_ACTIVITY => MonthlyActivity::class,
                EventSubjectTypes::RAMADAN_IFTAR => RamadanIftar::class,
            ],
            EventSubjectTypes::registeredModels()
        );
        $this->assertSame(
            MonthlyActivity::class,
            EventSubjectTypes::modelFor(EventSubjectTypes::MONTHLY_ACTIVITY)
        );
        $this->assertSame(
            RamadanIftar::class,
            EventSubjectTypes::modelFor(EventSubjectTypes::RAMADAN_IFTAR)
        );
    }

    /** @dataProvider unsupportedSubjectTypes */
    public function test_unregistered_subject_types_are_rejected(string $type): void
    {
        $this->expectException(InvalidArgumentException::class);

        EventSubjectTypes::modelFor($type);
    }

    public function unsupportedSubjectTypes(): array
    {
        return [
            'arbitrary model class' => ['App\\Models\\User'],
            'unknown alias' => ['unknown_event'],
        ];
    }
}
