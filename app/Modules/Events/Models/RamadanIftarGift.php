<?php

namespace App\Modules\Events\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RamadanIftarGift extends Model
{
    use HasFactory;

    protected $fillable = [
        'ramadan_iftar_id', 'description', 'planned_quantity', 'actual_quantity',
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
