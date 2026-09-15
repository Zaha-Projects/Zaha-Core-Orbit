<?php

namespace App\Modules\Events\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class EventGuidanceAcknowledgement extends Model
{
    protected $fillable = ['user_id', 'event_guidance_version_id', 'acknowledged_at'];
    protected $casts = ['acknowledged_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }
    public function guidanceVersion() { return $this->belongsTo(EventGuidanceVersion::class, 'event_guidance_version_id'); }
}
