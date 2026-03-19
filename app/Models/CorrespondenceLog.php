<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorrespondenceLog extends Model
{
    use HasFactory;

    protected $fillable = ['monthly_activity_id', 'status', 'notes', 'created_by'];
}
