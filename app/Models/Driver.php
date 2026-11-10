<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'status',
    ];

    public function movementDays()
    {
        return $this->hasMany(MovementDay::class);
    }
}
