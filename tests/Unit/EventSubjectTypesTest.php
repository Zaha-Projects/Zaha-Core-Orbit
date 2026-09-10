<?php

namespace Tests\Unit;

use App\Models\MonthlyActivity;
use App\Modules\Events\Models\EventSubjectTypes;
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

    public function test_only_monthly_activity_is_registered_and_resolves(): void
    {
        $this->assertSame(
            [EventSubjectTypes::MONTHLY_ACTIVITY => MonthlyActivity::class],
            EventSubjectTypes::registeredModels()
        );
        $this->assertSame(
            MonthlyActivity::class,
            EventSubjectTypes::modelFor(EventSubjectTypes::MONTHLY_ACTIVITY)
        );
        $this->assertArrayNotHasKey(
            EventSubjectTypes::RAMADAN_IFTAR,
            EventSubjectTypes::registeredModels()
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
            'reserved Ramadan alias' => [EventSubjectTypes::RAMADAN_IFTAR],
            'arbitrary model class' => ['App\\Models\\User'],
            'unknown alias' => ['unknown_event'],
        ];
    }
}
