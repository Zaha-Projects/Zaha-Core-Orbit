<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityEvaluation extends Model
{
    use HasFactory;

    protected $fillable = ['monthly_activity_id', 'evaluation_form_id', 'branch_id', 'evaluated_by', 'weighted_points', 'weight_total', 'normalized_score', 'visibility', 'notes', 'submitted_at', 'visibility_updated_by', 'visibility_updated_at'];
    protected $casts = ['submitted_at' => 'datetime', 'visibility_updated_at' => 'datetime', 'normalized_score' => 'decimal:2'];

    public function activity() { return $this->belongsTo(MonthlyActivity::class, 'monthly_activity_id'); }
    public function form() { return $this->belongsTo(EvaluationForm::class, 'evaluation_form_id'); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function evaluator() { return $this->belongsTo(User::class, 'evaluated_by'); }
    public function answers() { return $this->hasMany(ActivityEvaluationAnswer::class); }
}
