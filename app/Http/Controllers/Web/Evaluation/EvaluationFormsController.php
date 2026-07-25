<?php

namespace App\Http\Controllers\Web\Evaluation;

use App\Http\Controllers\Controller;
use App\Models\EvaluationForm;
use App\Models\EvaluationQuestion;
use Illuminate\Http\Request;

class EvaluationFormsController extends Controller
{
    public function index()
    {
        return view('pages.evaluation.forms', ['forms' => EvaluationForm::withCount('evaluations')->with('questions')->latest()->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name_ar' => ['required', 'string', 'max:255'], 'name_en' => ['required', 'string', 'max:255'], 'description_ar' => ['nullable', 'string'], 'description_en' => ['nullable', 'string'], 'is_active' => ['nullable', 'boolean']]);
        $data += ['created_by' => $request->user()->id, 'updated_by' => $request->user()->id];
        EvaluationForm::create($data);
        return back();
    }

    public function update(Request $request, EvaluationForm $evaluationForm)
    {
        $data = $request->validate(['name_ar' => ['required', 'string', 'max:255'], 'name_en' => ['required', 'string', 'max:255'], 'description_ar' => ['nullable', 'string'], 'description_en' => ['nullable', 'string'], 'is_active' => ['required', 'boolean']]);
        $evaluationForm->update($data + ['updated_by' => $request->user()->id]);
        return back();
    }

    public function storeQuestion(Request $request, EvaluationForm $evaluationForm)
    {
        $data = $this->questionData($request);
        $evaluationForm->questions()->create($data + ['question' => $data['question_ar'], 'answer_type' => 'score_10', 'created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);
        return back();
    }

    public function updateQuestion(Request $request, EvaluationQuestion $evaluationQuestion)
    {
        $data = $this->questionData($request);
        $evaluationQuestion->update($data + ['question' => $data['question_ar'], 'updated_by' => $request->user()->id]);
        return back();
    }

    private function questionData(Request $request): array
    {
        return $request->validate(['question_ar' => ['required', 'string', 'max:500'], 'question_en' => ['required', 'string', 'max:500'], 'minimum_score' => ['required', 'numeric', 'min:0'], 'maximum_score' => ['required', 'numeric', 'gt:minimum_score', 'max:100'], 'weight' => ['required', 'numeric', 'gt:0', 'max:1000'], 'sort_order' => ['required', 'integer', 'min:0'], 'is_required' => ['required', 'boolean'], 'is_active' => ['required', 'boolean']]);
    }
}
