<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationForm extends Model
{
    use HasFactory;

    protected $fillable = ['name_ar', 'name_en', 'description_ar', 'description_en', 'is_active', 'created_by', 'updated_by'];
    protected $casts = ['is_active' => 'boolean'];

    public function questions()
    {
        return $this->hasMany(EvaluationQuestion::class)->orderBy('sort_order');
    }

    public function evaluations()
    {
        return $this->hasMany(ActivityEvaluation::class);
    }
}
