<?php

namespace App\Modules\Events\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RamadanIftarMealItem extends Model
{
    use HasFactory;

    public const TYPE_MAIN = 'main';
    public const TYPE_SIDE = 'side';
    public const TYPE_DRINK = 'drink';
    public const TYPE_DESSERT = 'dessert';
    public const TYPE_OTHER = 'other';

    protected $fillable = [
        'ramadan_iftar_meal_id', 'name', 'item_type', 'quantity', 'notes', 'sort_order',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'sort_order' => 'integer',
    ];

    public static function types(): array
    {
        return [self::TYPE_MAIN, self::TYPE_SIDE, self::TYPE_DRINK, self::TYPE_DESSERT, self::TYPE_OTHER];
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function meal()
    {
        return $this->belongsTo(RamadanIftarMeal::class, 'ramadan_iftar_meal_id');
    }
}
