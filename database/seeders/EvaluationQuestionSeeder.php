<?php

namespace Database\Seeders;

use App\Models\EvaluationQuestion;
use Illuminate\Database\Seeder;

class EvaluationQuestionSeeder extends Seeder
{
    public function run(): void
    {
        $questions = [
            'مدى رضا المستفيدين عن جودة الفعالية',
            'مدى تحقيق أهداف الفعالية',
            'كفاءة تنظيم وتنفيذ الفعالية',
        ];

        foreach ($questions as $index => $question) {
            EvaluationQuestion::firstOrCreate(
                ['question' => $question],
                ['answer_type' => 'score_5', 'sort_order' => $index + 1]
            );
        }
    }
}
