<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_activities', function (Blueprint $table): void {
            $table->boolean('executive_review_required')
                ->default(false)
                ->after('executive_approval_status');
        });

        DB::table('roles')
            ->where('guard_name', 'web')
            ->where('name', 'branch_relations_manager')
            ->update([
                'name_ar' => 'رئيس فرع',
                'name_en' => 'Supervisor',
            ]);

        $this->alignMonthlyWorkflow();
    }

    public function down(): void
    {
        Schema::table('monthly_activities', function (Blueprint $table): void {
            $table->dropColumn('executive_review_required');
        });
    }

    private function alignMonthlyWorkflow(): void
    {
        $workflowId = DB::table('workflows')
            ->where('code', 'monthly_activity_approval')
            ->value('id');

        if (! $workflowId) {
            return;
        }

        DB::table('workflow_steps')
            ->where('workflow_id', $workflowId)
            ->whereIn('step_key', [
                'monthly_programs_manager_review',
                'monthly_workshops_secretary_review',
                'monthly_communication_head_review',
            ])
            ->delete();

        DB::table('workflow_steps')
            ->where('workflow_id', $workflowId)
            ->update([
                'step_order' => DB::raw('step_order + 100'),
                'approval_level' => DB::raw('approval_level + 100'),
            ]);

        $roleIds = $this->roleIds([
            'relations_officer',
            'branch_relations_manager',
            'branch_coordinator',
            'relations_manager',
            'executive_manager',
        ]);

        if (DB::table('workflow_steps')->where('workflow_id', $workflowId)->where('step_key', 'monthly_relations_manager_review')->exists()) {
            DB::table('workflow_steps')
                ->where('workflow_id', $workflowId)
                ->where('step_key', 'monthly_relations_officer_review')
                ->delete();
        } else {
            DB::table('workflow_steps')
                ->where('workflow_id', $workflowId)
                ->where('step_key', 'monthly_relations_officer_review')
                ->update(['step_key' => 'monthly_relations_manager_review']);
        }

        $this->upsertWorkflowStep($workflowId, 'monthly_relations_officer_submit', [
            'step_order' => 1,
            'approval_level' => 1,
            'name_ar' => 'إنشاء وإرسال مسؤول العلاقات',
            'name_en' => 'Relations Officer Draft & Submit',
            'step_type' => 'sub',
            'role_id' => $roleIds['relations_officer'] ?? null,
            'condition_field' => 'monthly_created_by_branch_relations',
            'condition_value' => '1',
            'is_editable' => true,
        ]);

        $this->upsertWorkflowStep($workflowId, 'monthly_branch_relations_manager_review', [
            'step_order' => 2,
            'approval_level' => 2,
            'name_ar' => 'اعتماد رئيس الفرع',
            'name_en' => 'Supervisor Approval',
            'step_type' => 'main',
            'role_id' => $roleIds['branch_relations_manager'] ?? null,
            'condition_field' => 'monthly_created_by_branch_relations',
            'condition_value' => '1',
            'is_editable' => false,
        ]);

        $this->upsertWorkflowStep($workflowId, 'monthly_branch_coordinator_review', [
            'step_order' => 3,
            'approval_level' => 3,
            'name_ar' => 'اعتماد منسق الفروع',
            'name_en' => 'Branch Coordinator Approval',
            'step_type' => 'main',
            'role_id' => $roleIds['branch_coordinator'] ?? null,
            'condition_field' => 'monthly_branch_coordinator_required',
            'condition_value' => '1',
            'is_editable' => false,
        ]);

        $this->upsertWorkflowStep($workflowId, 'monthly_relations_manager_review', [
            'step_order' => 4,
            'approval_level' => 4,
            'name_ar' => 'اعتماد مدير العلاقات الرئيسي',
            'name_en' => 'Primary Relations Manager Approval',
            'step_type' => 'main',
            'role_id' => $roleIds['relations_manager'] ?? null,
            'condition_field' => null,
            'condition_value' => null,
            'is_editable' => false,
        ]);

        $this->upsertWorkflowStep($workflowId, 'monthly_executive_manager_final_approval', [
            'step_order' => 5,
            'approval_level' => 5,
            'name_ar' => 'الاعتماد النهائي من المدير التنفيذي',
            'name_en' => 'Executive Manager Final Approval',
            'step_type' => 'main',
            'role_id' => $roleIds['executive_manager'] ?? null,
            'condition_field' => 'executive_review_required',
            'condition_value' => '1',
            'is_editable' => false,
        ]);
    }

    /**
     * @param  array<int, string>  $roleNames
     * @return array<string, int>
     */
    private function roleIds(array $roleNames): array
    {
        return DB::table('roles')
            ->where('guard_name', 'web')
            ->whereIn('name', $roleNames)
            ->pluck('id', 'name')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    private function upsertWorkflowStep(int $workflowId, string $stepKey, array $values): void
    {
        DB::table('workflow_steps')->updateOrInsert(
            [
                'workflow_id' => $workflowId,
                'step_key' => $stepKey,
            ],
            array_merge($values, [
                'permission_id' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ])
        );
    }
};
