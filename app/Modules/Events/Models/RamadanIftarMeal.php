<?php

namespace App\Modules\Events\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RamadanIftarMeal extends Model
{
    use HasFactory;

    protected $fillable = [
        'ramadan_iftar_id', 'description', 'planned_quantity', 'actual_quantity',
        'source_type', 'source_name', 'restaurant_name', 'restaurant_contact',
        'rating', 'rating_notes',
    ];

    protected $casts = [
        'planned_quantity' => 'integer',
        'actual_quantity' => 'integer',
        'rating' => 'integer',
    ];

    public function ramadanIftar()
    {
        return $this->belongsTo(RamadanIftar::class);
    }

    public function items()
    {
        return $this->hasMany(RamadanIftarMealItem::class)->orderBy('sort_order');
    }
}
