<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyActivityEvaluationResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'monthly_activity_id',
        'evaluation_question_id',
        'answer_value',
        'score',
        'note',
        'created_by',
    ];

    public function question()
    {
        return $this->belongsTo(EvaluationQuestion::class, 'evaluation_question_id');
    }
}
