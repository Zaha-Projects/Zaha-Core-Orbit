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
        foreach ($this->workflowDefinitions() as $workflow) {
            $this->seedWorkflow(
                code: $workflow['code'],
                module: $workflow['module'],
                nameAr: $workflow['name_ar'],
                nameEn: $workflow['name_en'],
                steps: $workflow['steps'],
            );
        }
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
                    'condition_field' => $step['condition_field'] ?? null,
                    'condition_value' => $step['condition_value'] ?? null,
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

    /**
     * @return array<int, array{code:string,module:string,name_ar:string,name_en:string,steps:array<int, array<string, mixed>>}>
     */
    private function workflowDefinitions(): array
    {
        return [
            [
                'code' => 'agenda_approval',
                'module' => 'agenda',
                'name_ar' => 'سير اعتماد الأجندة السنوية',
                'name_en' => 'Annual Agenda Approval Workflow',
                'steps' => [
                    [
                        'step_key' => 'agenda_relations_officer_submit',
                        'step_order' => 1,
                        'approval_level' => 1,
                        'name_ar' => 'إنشاء وإرسال مسؤول العلاقات الرئيسي',
                        'name_en' => 'Primary Relations Officer Draft & Submit',
                        'step_type' => 'sub',
                        'role' => 'relations_officer',
                        'is_editable' => true,
                    ],
                    [
                        'step_key' => 'agenda_relations_manager_review',
                        'step_order' => 2,
                        'approval_level' => 2,
                        'name_ar' => 'اعتماد مدير العلاقات الرئيسي',
                        'name_en' => 'Primary Relations Manager Approval',
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
                ],
            ],
            [
                'code' => 'monthly_activity_approval',
                'module' => 'monthly_activities',
                'name_ar' => 'سير اعتماد الخطة الشهرية',
                'name_en' => 'Monthly Plan Approval Workflow',
                'steps' => [
                    [
                        'step_key' => 'monthly_branch_relations_officer_submit',
                        'step_order' => 1,
                        'approval_level' => 1,
                        'name_ar' => 'إنشاء وإرسال مسؤول علاقات الفرع',
                        'name_en' => 'Branch Relations Officer Draft & Submit',
                        'step_type' => 'sub',
                        'role' => 'branch_relations_officer',
                        'is_editable' => true,
                    ],
                    [
                        'step_key' => 'monthly_branch_relations_manager_review',
                        'step_order' => 2,
                        'approval_level' => 2,
                        'name_ar' => 'اعتماد مدير علاقات الفرع',
                        'name_en' => 'Branch Relations Manager Approval',
                        'step_type' => 'main',
                        'role' => 'branch_relations_manager',
                        'is_editable' => false,
                    ],
                    [
                        'step_key' => 'monthly_branch_coordinator_review',
                        'step_order' => 3,
                        'approval_level' => 3,
                        'name_ar' => 'اعتماد منسق الفروع',
                        'name_en' => 'Branch Coordinator Approval',
                        'step_type' => 'main',
                        'role' => 'branch_coordinator',
                        'is_editable' => false,
                    ],
                    [
                        'step_key' => 'monthly_relations_officer_review',
                        'step_order' => 4,
                        'approval_level' => 4,
                        'name_ar' => 'اعتماد مسؤول العلاقات الرئيسي',
                        'name_en' => 'Primary Relations Officer Approval',
                        'step_type' => 'main',
                        'role' => 'relations_officer',
                        'is_editable' => false,
                    ],
                    [
                        'step_key' => 'monthly_programs_manager_review',
                        'step_order' => 5,
                        'approval_level' => 5,
                        'name_ar' => 'اعتماد مدير البرامج',
                        'name_en' => 'Programs Manager Approval',
                        'step_type' => 'main',
                        'role' => 'programs_manager',
                        'condition_field' => 'requires_programs',
                        'condition_value' => '1',
                        'is_editable' => false,
                    ],
                    [
                        'step_key' => 'monthly_workshops_secretary_review',
                        'step_order' => 6,
                        'approval_level' => 6,
                        'name_ar' => 'اعتماد سكرتير الورش',
                        'name_en' => 'Workshops Secretary Approval',
                        'step_type' => 'main',
                        'role' => 'workshops_secretary',
                        'condition_field' => 'requires_workshops',
                        'condition_value' => '1',
                        'is_editable' => false,
                    ],
                    [
                        'step_key' => 'monthly_communication_head_review',
                        'step_order' => 7,
                        'approval_level' => 7,
                        'name_ar' => 'اعتماد رئيس قسم الاتصال',
                        'name_en' => 'Communication Head Approval',
                        'step_type' => 'main',
                        'role' => 'communication_head',
                        'condition_field' => 'requires_communications',
                        'condition_value' => '1',
                        'is_editable' => false,
                    ],
                    [
                        'step_key' => 'monthly_relations_manager_review',
                        'step_order' => 8,
                        'approval_level' => 8,
                        'name_ar' => 'اعتماد مدير العلاقات الرئيسي',
                        'name_en' => 'Primary Relations Manager Approval',
                        'step_type' => 'main',
                        'role' => 'relations_manager',
                        'is_editable' => false,
                    ],
                    [
                        'step_key' => 'monthly_executive_manager_final_approval',
                        'step_order' => 9,
                        'approval_level' => 9,
                        'name_ar' => 'الاعتماد النهائي من المدير التنفيذي',
                        'name_en' => 'Executive Manager Final Approval',
                        'step_type' => 'main',
                        'role' => 'executive_manager',
                        'is_editable' => false,
                    ],
                ],
            ],
        ];
    }
}