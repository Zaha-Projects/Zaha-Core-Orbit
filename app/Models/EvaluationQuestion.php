<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationQuestion extends Model
{
    use HasFactory;

    protected $fillable = ['question', 'answer_type', 'is_active', 'sort_order', 'created_by'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
