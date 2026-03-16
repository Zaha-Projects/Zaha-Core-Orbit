<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyActivityFollowup extends Model
{
    use HasFactory;

    protected $fillable = ['monthly_activity_id', 'remarks', 'created_by'];
}
