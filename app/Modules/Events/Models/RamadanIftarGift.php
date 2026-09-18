<?php

namespace App\Modules\Events\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RamadanIftarGift extends Model
{
    use HasFactory;

    public const TYPE_GIFTS = 'gifts';
    public const TYPE_SHIELDS = 'shields';
    public const TYPE_BOTH = 'both';

    public static function types(): array
    {
        return RamadanIftarGiftType::query()->active()->orderBy('sort_order')->orderBy('id')->pluck('code')->all();
    }

    protected $fillable = [
        'ramadan_iftar_id', 'gift_type', 'description', 'planned_quantity', 'actual_quantity',
        'has_supporting_entity', 'supporting_entity_name', 'unit_value',
        'estimated_total_value',
    ];

    protected $casts = [
        'planned_quantity' => 'integer',
        'actual_quantity' => 'integer',
        'has_supporting_entity' => 'boolean',
        'unit_value' => 'decimal:2',
        'estimated_total_value' => 'decimal:2',
    ];

    public function ramadanIftar()
    {
        return $this->belongsTo(RamadanIftar::class);
    }
}
