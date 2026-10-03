<?php

namespace App\Modules\Events\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TargetGroup extends Model
{
    public const TYPE_AGE = 'age';
    public const TYPE_COMMUNITY = 'community';

    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'is_other',
        'is_active',
        'is_monthly_activity',
        'is_ramadan_iftar',
        'is_bazaar',
        'sort_order',
    ];

    protected $casts = [
        'is_other' => 'boolean',
        'is_active' => 'boolean',
        'is_monthly_activity' => 'boolean',
        'is_ramadan_iftar' => 'boolean',
        'is_bazaar' => 'boolean',
        'sort_order' => 'integer',
    ];


    public static function types(): array
    {
        return [self::TYPE_AGE, self::TYPE_COMMUNITY];
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function oppositeType(): string
    {
        return $this->type === self::TYPE_AGE ? self::TYPE_COMMUNITY : self::TYPE_AGE;
    }

    public function ramadanSelections()
    {
        return $this->hasMany(SubjectTargetGroup::class);
    }

    public function ramadanClassifications()
    {
        return $this->hasMany(SubjectTargetGroup::class, 'classification_target_group_id');
    }

    public function volunteerRequirements()
    {
        return $this->hasMany(SubjectVolunteerRequirement::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForMonthlyActivities($query)
    {
        return $query->where('is_monthly_activity', true);
    }

    public function scopeForRamadanIftars($query)
    {
        return $query->where('is_ramadan_iftar', true);
    }

    public function scopeForBazaars($query)
    {
        return $query->where('is_bazaar', true);
    }
}
