<?php

namespace App\Modules\Movement\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Plan §7.3 / §13.2–13.3. Not to be confused with the legacy App\Models\Driver (retired in Phase 5).
 * Personal data (name, phone, licence) is manager-only (D7, movement.drivers.view_details).
 */
class Driver extends Model
{
    use SoftDeletes;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_PENDING_VERIFICATION = 'pending_verification';
    public const STATUSES = [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_PENDING_VERIFICATION];

    public const LICENCE_OK = 'ok';
    public const LICENCE_EXPIRING = 'expiring';
    public const LICENCE_EXPIRED = 'expired';
    public const LICENCE_UNKNOWN = 'unknown';
    /** D8: "expiring" means within this many days. */
    public const LICENCE_WARNING_DAYS = 30;

    protected $table = 'movement_drivers';

    protected $fillable = ['user_id', 'name', 'phone', 'licence_number', 'licence_category', 'licence_expires_on', 'employment_status', 'legacy_source_id', 'notes'];

    protected $casts = ['licence_expires_on' => 'date', 'legacy_source_id' => 'integer'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function unavailabilities()
    {
        return $this->hasMany(Unavailability::class, 'driver_id');
    }

    /** Licence flag for manager views (D8). Dates compare in the business timezone. */
    public function licenceState(?CarbonImmutable $today = null): string
    {
        if (! $this->licence_expires_on) {
            return self::LICENCE_UNKNOWN;
        }

        $today ??= CarbonImmutable::now(config('movement.timezone'))->startOfDay();
        $expires = CarbonImmutable::parse($this->licence_expires_on->format('Y-m-d'), config('movement.timezone'));

        if ($expires->lt($today)) {
            return self::LICENCE_EXPIRED;
        }

        return $expires->lte($today->addDays(self::LICENCE_WARNING_DAYS)) ? self::LICENCE_EXPIRING : self::LICENCE_OK;
    }
}
