<?php

namespace Database\Seeders;

use App\Models\EvaluationForm;
use App\Models\EvaluationQuestion;
use Illuminate\Database\Seeder;

class ActivityEvaluationFormSeeder extends Seeder
{
    public function run(): void
    {
        $form = EvaluationForm::query()->firstOrCreate(
            ['name_en' => 'Activity Evaluation Form'],
            [
                'name_ar' => 'نموذج تقييم الفعالية',
                'description_ar' => 'النموذج القياسي لتقييم الفعاليات بعد التنفيذ.',
                'description_en' => 'Standard post-execution activity evaluation.',
                'is_active' => true,
            ]
        );

        $questions = [
            ['مدى تحقيق الفعالية لأهدافها', 'Achievement of activity objectives'],
            ['مستوى تنظيم الفعالية', 'Activity organization'],
            ['مدى الالتزام بالخطة المعتمدة', 'Compliance with the approved plan'],
            ['مستوى تفاعل المشاركين', 'Participant engagement'],
            ['ملاءمة مكان تنفيذ الفعالية', 'Suitability of the venue'],
            ['كفاءة فريق التنفيذ', 'Execution team efficiency'],
            ['جودة إدارة الوقت', 'Time management quality'],
            ['جودة المخرجات والنتائج', 'Quality of outputs and outcomes'],
            ['دقة بيانات إكمال ما بعد التنفيذ', 'Post-execution data accuracy'],
            ['التقييم العام للفعالية', 'Overall activity evaluation'],
        ];

        foreach ($questions as $index => $labels) {
            EvaluationQuestion::query()->firstOrCreate(
                ['evaluation_form_id' => $form->id, 'question_ar' => $labels[0]],
                [
                    'question' => $labels[0],
                    'question_en' => $labels[1],
                    'answer_type' => 'score_10',
                    'minimum_score' => 1,
                    'maximum_score' => 10,
                    'weight' => 1,
                    'sort_order' => $index + 1,
                    'is_required' => true,
                    'is_active' => true,
                ]
            );
        }
    }
}
