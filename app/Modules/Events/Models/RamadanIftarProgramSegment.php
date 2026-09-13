<?php

namespace App\Modules\Events\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RamadanIftarProgramSegment extends Model
{
    use HasFactory;

    public const STATUS_PLANNED = 'planned';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'ramadan_iftar_id', 'name', 'starts_at', 'ends_at', 'duration_minutes',
        'sort_order', 'executor_user_id', 'external_executor_name',
        'execution_status', 'actual_notes',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'sort_order' => 'integer',
    ];

    public static function statuses(): array
    {
        return [self::STATUS_PLANNED, self::STATUS_COMPLETED, self::STATUS_CANCELLED];
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function ramadanIftar()
    {
        return $this->belongsTo(RamadanIftar::class);
    }

    public function executor()
    {
        return $this->belongsTo(User::class, 'executor_user_id');
    }
}
