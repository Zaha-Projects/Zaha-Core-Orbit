<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationQuestion extends Model
{
    use HasFactory;

    protected $fillable = ['evaluation_form_id', 'question', 'question_ar', 'question_en', 'description_ar', 'description_en', 'answer_type', 'minimum_score', 'maximum_score', 'weight', 'is_required', 'is_active', 'sort_order', 'created_by', 'updated_by'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_required' => 'boolean',
    ];

    public function form() { return $this->belongsTo(EvaluationForm::class, 'evaluation_form_id'); }
}
