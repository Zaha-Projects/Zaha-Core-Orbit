<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepartmentUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_key',
        'name',
        'role_name',
        'color_hex',
        'icon',
    ];
}
