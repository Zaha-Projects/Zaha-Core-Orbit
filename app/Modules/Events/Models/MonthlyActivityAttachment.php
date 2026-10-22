<?php

namespace App\Modules\Events\Models;

use App\Modules\Events\Models\MonthlyActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyActivityAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'monthly_activity_id',
        'file_type',
        'title',
        'file_path',
        'uploaded_by',
    ];

    public function monthlyActivity()
    {
        return $this->belongsTo(MonthlyActivity::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
