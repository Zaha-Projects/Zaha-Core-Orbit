<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityEvaluationAnswer extends Model
{
    use HasFactory;

    protected $fillable = ['activity_evaluation_id', 'evaluation_question_id', 'question_ar', 'question_en', 'minimum_score', 'maximum_score', 'weight', 'score', 'weighted_score', 'question_sort_order', 'note'];
    public function evaluation() { return $this->belongsTo(ActivityEvaluation::class, 'activity_evaluation_id'); }
    public function question() { return $this->belongsTo(EvaluationQuestion::class); }
}
