<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostExecutionVerification extends Model
{
    use HasFactory;

    protected $fillable = ['monthly_activity_id', 'branch_id', 'field_key', 'field_label', 'value_type', 'original_value', 'corrected_value', 'status', 'note', 'verified_by', 'verified_at'];
    protected $casts = ['original_value' => 'array', 'corrected_value' => 'array', 'verified_at' => 'datetime'];

    public function activity() { return $this->belongsTo(MonthlyActivity::class, 'monthly_activity_id'); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function verifier() { return $this->belongsTo(User::class, 'verified_by'); }
}
