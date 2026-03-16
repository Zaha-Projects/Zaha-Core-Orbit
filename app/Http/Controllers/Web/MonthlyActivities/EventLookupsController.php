<?php

namespace App\Http\Controllers\Web\MonthlyActivities;

use App\Http\Controllers\Controller;
use App\Models\EvaluationQuestion;
use App\Models\TargetGroup;
use Illuminate\Http\Request;

class EventLookupsController extends Controller
{
    public function index()
    {
        $targetGroups = TargetGroup::orderBy('sort_order')->get();
        $evaluationQuestions = EvaluationQuestion::orderBy('sort_order')->get();

        return view('pages.monthly_activities.lookups.index', compact('targetGroups', 'evaluationQuestions'));
    }

    public function storeTargetGroup(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_other' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        TargetGroup::create([
            'name' => $data['name'],
            'is_other' => (bool) ($data['is_other'] ?? false),
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => true,
        ]);

        return back()->with('status', 'تم إضافة فئة مستهدفة.');
    }

    public function storeEvaluationQuestion(Request $request)
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'answer_type' => ['required', 'in:score_5,text'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        EvaluationQuestion::create([
            'question' => $data['question'],
            'answer_type' => $data['answer_type'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => true,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', 'تم إضافة سؤال تقييم.');
    }
}
