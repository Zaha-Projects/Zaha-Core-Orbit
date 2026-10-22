<?php

namespace Tests\Feature;

use App\Modules\Events\Models\ExecutionNeedType;
use App\Modules\Events\Models\EventGuidanceAcknowledgement;
use App\Modules\Events\Models\EventGuidanceVersion;
use App\Modules\Events\Services\RamadanGuidanceAcceptanceService;
use Database\Seeders\CanonicalExecutionNeedTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RamadanIftarBusinessReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_execution_need_catalogue_exposes_context_and_mandatory_metadata(): void
    {
        $this->seed(CanonicalExecutionNeedTypeSeeder::class);

        $team = ExecutionNeedType::query()->where('code', 'execution_team')->firstOrFail();
        $this->assertTrue($team->is_ramadan_iftar);
        $this->assertTrue($team->isMandatoryForRamadan());
        $this->assertTrue($team->is_monthly_activity);
        $this->assertFalse($team->isMandatoryForMonthly());
        $this->assertTrue(ExecutionNeedType::query()->forRamadanIftars()->whereKey($team)->exists());
    }

    public function test_guidance_acknowledgement_is_bound_to_user_and_exact_version(): void
    {
        $version = EventGuidanceVersion::query()->create([
            'code' => EventGuidanceVersion::RAMADAN_IFTAR, 'version_number' => 1,
            'title' => 'Guidance', 'content' => '[]', 'is_active' => true, 'published_at' => now(),
        ]);
        $user = \App\Models\User::factory()->create();
        $request = \Illuminate\Http\Request::create('/guidance', 'POST');
        $request->setUserResolver(fn () => $user);
        $request->setLaravelSession($this->app['session.store']);

        $service = $this->app->make(RamadanGuidanceAcceptanceService::class);
        $service->present($request, $version);
        $service->acceptPresented($request);

        $this->assertDatabaseHas('event_guidance_acknowledgements', [
            'user_id' => $user->id, 'event_guidance_version_id' => $version->id,
        ]);
        $this->assertCount(1, EventGuidanceAcknowledgement::all());

        $version->update(['is_active' => false]);
        EventGuidanceVersion::query()->create([
            'code' => EventGuidanceVersion::RAMADAN_IFTAR, 'version_number' => 2,
            'title' => 'New guidance', 'content' => '[]', 'is_active' => true, 'published_at' => now(),
        ]);
        $this->assertFalse($service->hasAcceptedCurrent($request));
    }

    public function test_ramadan_form_does_not_offer_a_branch_field(): void
    {
        $source = file_get_contents(resource_path('views/pages/events/ramadan/_form.blade.php'));
        $this->assertStringNotContainsString('name="branch_id"', $source);
    }
}
