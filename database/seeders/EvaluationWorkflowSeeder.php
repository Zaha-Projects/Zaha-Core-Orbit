<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\EvaluationForm;
use App\Models\EvaluationQuestion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EvaluationWorkflowSeeder extends Seeder
{
    // Development-only credential; override seeded accounts in deployed environments.
    public const DEVELOPMENT_PASSWORD = 'Password123!';

    public function run(): void
    {
        User::role('followup_officer')->get()->each->removeRole('followup_officer');

        Branch::query()->each(function (Branch $branch) {
            $slug = Str::slug($branch->name) ?: (string) $branch->id;
            $user = User::query()->updateOrCreate(['email' => "followup.branch.{$slug}@zaha.local"], ['name' => "مسؤول متابعة - {$branch->name}", 'branch_id' => $branch->id, 'status' => 'active', 'password' => Hash::make(self::DEVELOPMENT_PASSWORD)]);
            $user->syncRoles(['followup_officer']);
            $user->assignedBranches()->sync([$branch->id]);
        });

        foreach (range(1, 3) as $number) {
            $user = User::query()->updateOrCreate(['email' => "evaluation.officer.{$number}@zaha.local"], ['name' => "مسؤول التقييم {$number}", 'branch_id' => null, 'status' => 'active', 'password' => Hash::make(self::DEVELOPMENT_PASSWORD)]);
            $user->syncRoles(['evaluation_officer']);
            $user->assignedBranches()->sync([]);
        }

        $form = EvaluationForm::query()->updateOrCreate(['name_en' => 'Activity Evaluation Form'], ['name_ar' => 'نموذج تقييم الفعالية', 'description_ar' => 'النموذج القياسي لتقييم الفعاليات بعد التنفيذ.', 'description_en' => 'Standard post-execution activity evaluation.', 'is_active' => true]);
        $questions = [
            ['مدى تحقيق الفعالية لأهدافها', 'Achievement of activity objectives'], ['مستوى تنظيم الفعالية', 'Activity organization'],
            ['مدى الالتزام بالخطة المعتمدة', 'Compliance with the approved plan'], ['مستوى تفاعل المشاركين', 'Participant engagement'],
            ['ملاءمة مكان تنفيذ الفعالية', 'Suitability of the venue'], ['كفاءة فريق التنفيذ', 'Execution team efficiency'],
            ['جودة إدارة الوقت', 'Time management quality'], ['جودة المخرجات والنتائج', 'Quality of outputs and outcomes'],
            ['دقة بيانات إكمال ما بعد التنفيذ', 'Post-execution data accuracy'], ['التقييم العام للفعالية', 'Overall activity evaluation'],
        ];
        foreach ($questions as $index => $labels) {
            EvaluationQuestion::query()->updateOrCreate(['evaluation_form_id' => $form->id, 'question_ar' => $labels[0]], ['question' => $labels[0], 'question_en' => $labels[1], 'answer_type' => 'score_10', 'minimum_score' => 1, 'maximum_score' => 10, 'weight' => 1, 'sort_order' => $index + 1, 'is_required' => true, 'is_active' => true]);
        }
    }
}
