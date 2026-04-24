<?php

namespace Database\Seeders;

use App\Models\AgendaApproval;
use App\Models\AgendaEvent;
use App\Models\AgendaParticipation;
use App\Models\Branch;
use App\Models\Department;
use App\Models\EventCategory;
use App\Models\MonthlyActivity;
use App\Models\MonthlyActivityApproval;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EnterpriseDemoSeeder extends Seeder
{
    public function run(): void
    {
        $branches = Branch::all();
        $departments = Department::all();
        $categories = EventCategory::all();
        $creator = User::query()->first();

        if (! $creator || $branches->isEmpty() || $departments->isEmpty()) {
            return;
        }

        foreach ([now()->year - 1, now()->year] as $year) {
            for ($m = 1; $m <= 12; $m++) {
                for ($i = 1; $i <= rand(2, 5); $i++) {
                    $date = Carbon::create($year, $m, rand(1, 25));
                    $department = $departments->random();
                    $departmentCategories = $categories->where('department_id', $department->id);
                    $category = $departmentCategories->isNotEmpty()
                        ? $departmentCategories->random()
                        : null;

                    $event = AgendaEvent::create([
                        'event_date' => $date->toDateString(),
                        'event_day' => $date->format('l'),
                        'month' => $m,
                        'day' => (int) $date->format('d'),
                        'event_name' => "Enterprise Event {$year}-{$m}-{$i}",
                        'department_id' => $department->id,
                        'event_category_id' => $category?->id,
                        'event_category' => null,
                        'plan_type' => collect(['unified', 'non_unified'])->random(),
                        'event_type' => collect(['mandatory', 'optional'])->random(),
                        'status' => collect(['submitted', 'relations_approved', 'published', 'changes_requested'])->random(),
                        'relations_approval_status' => collect(['pending', 'approved', 'changes_requested'])->random(),
                        'executive_approval_status' => collect(['pending', 'approved', 'changes_requested'])->random(),
                        'created_by' => $creator->id,
                    ]);

                    foreach ($branches->random(rand(1, min(3, $branches->count()))) as $branch) {
                        AgendaParticipation::create([
                            'agenda_event_id' => $event->id,
                            'entity_type' => 'branch',
                            'entity_id' => $branch->id,
                            'participation_status' => collect(['participant', 'not_participant', 'unspecified'])->random(),
                            'updated_by' => $creator->id,
                        ]);
                    }

                    AgendaApproval::create([
                        'agenda_event_id' => $event->id,
                        'step' => 'relations_review',
                        'decision' => collect(['approved', 'changes_requested'])->random(),
                        'approved_by' => $creator->id,
                        'approved_at' => now(),
                    ]);

                    $activity = MonthlyActivity::create([
                        'month' => $m,
                        'day' => (int) $date->format('d'),
                        'title' => "Activity {$year}-{$m}-{$i}",
                        'proposed_date' => $date->toDateString(),
                        'is_in_agenda' => true,
                        'agenda_event_id' => $event->id,
                        'location_type' => 'onsite',
                        'status' => collect(['draft', 'submitted', 'in_review', 'approved', 'changes_requested'])->random(),
                        'branch_id' => $branches->random()->id,
                        'created_by' => $creator->id,
                        'executive_approval_status' => collect(['pending', 'approved', 'changes_requested'])->random(),
                        'programs_manager_approval_status' => collect(['pending', 'approved', 'changes_requested'])->random(),
                        'programs_officer_approval_status' => collect(['pending', 'approved', 'changes_requested'])->random(),
                        'relations_manager_approval_status' => collect(['pending', 'approved', 'changes_requested'])->random(),
                        'relations_officer_approval_status' => collect(['pending', 'approved', 'changes_requested'])->random(),
                        'actual_date' => rand(0,1) ? $date->copy()->addDays(rand(0,3))->toDateString() : null,
                    ]);

                    MonthlyActivityApproval::create([
                        'monthly_activity_id' => $activity->id,
                        'step' => 'relations_officer_review',
                        'decision' => collect(['approved', 'changes_requested'])->random(),
                        'approved_by' => $creator->id,
                        'approved_at' => now(),
                    ]);
                }
            }
        }
    }
}
