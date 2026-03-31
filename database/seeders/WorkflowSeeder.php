<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class WorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedWorkflow(
            code: 'agenda_approval',
            module: 'agenda',
            nameAr: 'سير اعتماد الأجندة',
            nameEn: 'Agenda Approval Workflow',
            steps: [
                [
                    'step_key' => 'agenda_relations_officer_submit',
                    'step_order' => 1,
                    'approval_level' => 1,
                    'name_ar' => 'إرسال مسؤول العلاقات',
                    'name_en' => 'Relations Officer Submission',
                    'step_type' => 'sub',
                    'role' => 'relations_officer',
                    'is_editable' => true,
                ],
                [
                    'step_key' => 'agenda_relations_manager_review',
                    'step_order' => 2,
                    'approval_level' => 2,
                    'name_ar' => 'مراجعة مدير العلاقات',
                    'name_en' => 'Relations Manager Review',
                    'step_type' => 'main',
                    'role' => 'relations_manager',
                    'is_editable' => false,
                ],
                [
                    'step_key' => 'agenda_executive_manager_final_approval',
                    'step_order' => 3,
                    'approval_level' => 3,
                    'name_ar' => 'الاعتماد النهائي من المدير التنفيذي',
                    'name_en' => 'Executive Manager Final Approval',
                    'step_type' => 'main',
                    'role' => 'executive_manager',
                    'is_editable' => false,
                ],
            ]
        );

        $this->seedWorkflow(
            code: 'monthly_activity_approval',
            module: 'monthly_activity',
            nameAr: 'سير اعتماد الأنشطة الشهرية',
            nameEn: 'Monthly Activities Approval Workflow',
            steps: [
                [
                    'step_key' => 'monthly_branch_relations_officer_submit',
                    'step_order' => 1,
                    'approval_level' => 1,
                    'name_ar' => 'إرسال مسؤول علاقات الفروع',
                    'name_en' => 'Branch Relations Officer Submission',
                    'step_type' => 'sub',
                    'role' => 'branch_relations_officer',
                    'is_editable' => true,
                ],
                [
                    'step_key' => 'monthly_relations_manager_review',
                    'step_order' => 2,
                    'approval_level' => 2,
                    'name_ar' => 'مراجعة مدير العلاقات',
                    'name_en' => 'Relations Manager Review',
                    'step_type' => 'main',
                    'role' => 'relations_manager',
                    'is_editable' => false,
                ],
                [
                    'step_key' => 'monthly_programs_manager_review',
                    'step_order' => 3,
                    'approval_level' => 3,
                    'name_ar' => 'مراجعة مدير البرامج',
                    'name_en' => 'Programs Manager Review',
                    'step_type' => 'main',
                    'role' => 'programs_manager',
                    'is_editable' => false,
                ],
                [
                    'step_key' => 'monthly_executive_manager_final_approval',
                    'step_order' => 4,
                    'approval_level' => 4,
                    'name_ar' => 'الاعتماد النهائي من المدير التنفيذي',
                    'name_en' => 'Executive Manager Final Approval',
                    'step_type' => 'main',
                    'role' => 'executive_manager',
                    'is_editable' => false,
                ],
            ]
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $steps
     */
    private function seedWorkflow(string $code, string $module, string $nameAr, string $nameEn, array $steps): void
    {
        $this->validateSteps($code, $steps);

        $workflow = Workflow::query()->updateOrCreate(
            ['code' => $code],
            [
                'module' => $module,
                'name_ar' => $nameAr,
                'name_en' => $nameEn,
                'is_active' => true,
            ]
        );

        $stepKeys = collect($steps)->pluck('step_key')->all();
        WorkflowStep::query()
            ->where('workflow_id', $workflow->id)
            ->whereNotIn('step_key', $stepKeys)
            ->delete();

        foreach ($steps as $step) {
            $role = Role::query()
                ->where('guard_name', 'web')
                ->where('name', $step['role'])
                ->first();

            if (! $role) {
                throw new InvalidArgumentException("Role [{$step['role']}] is missing for workflow [{$code}].");
            }

            WorkflowStep::query()->updateOrCreate(
                [
                    'workflow_id' => $workflow->id,
                    'step_key' => $step['step_key'],
                ],
                [
                    'step_order' => $step['step_order'],
                    'approval_level' => $step['approval_level'],
                    'name_ar' => $step['name_ar'],
                    'name_en' => $step['name_en'],
                    'step_type' => $step['step_type'],
                    'role_id' => $role->id,
                    'permission_id' => null,
                    'is_editable' => $step['is_editable'],
                ]
            );
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $steps
     */
    private function validateSteps(string $workflowCode, array $steps): void
    {
        $stepsCollection = collect($steps);

        $this->assertUnique($workflowCode, 'step_key', $stepsCollection->pluck('step_key'));
        $this->assertUnique($workflowCode, 'step_order', $stepsCollection->pluck('step_order'));
        $this->assertUnique($workflowCode, 'approval_level', $stepsCollection->pluck('approval_level'));

        $paired = $stepsCollection
            ->map(fn (array $step): string => $step['step_order'] . ':' . $step['approval_level']);

        $this->assertUnique($workflowCode, 'step_order + approval_level', $paired);
    }

    private function assertUnique(string $workflowCode, string $field, Collection $values): void
    {
        $duplicates = $values
            ->countBy()
            ->filter(fn (int $count): bool => $count > 1)
            ->keys()
            ->values();

        if ($duplicates->isNotEmpty()) {
            throw new InvalidArgumentException(
                "Workflow [{$workflowCode}] contains duplicate {$field}: " . $duplicates->implode(', ')
            );
        }
    }
}
