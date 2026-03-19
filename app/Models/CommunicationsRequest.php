<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommunicationsRequest extends Model
{
    use HasFactory;

    protected $fillable = ['event_id', 'status', 'notes', 'media_files'];

    protected $casts = ['media_files' => 'array'];

    public function event()
    {
        return $this->belongsTo(MonthlyActivity::class, 'event_id');
    }
}
