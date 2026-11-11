<?php

namespace App\Modules\Movement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Plan §7.2 / §13.1. Not to be confused with the legacy App\Models\Vehicle (retired in Phase 5).
 * Manager-only data: fleet_number, plate_number and per-vehicle status are never shown to ordinary users (D7).
 */
class Vehicle extends Model
{
    use SoftDeletes;

    public const STATUS_OPERATIONAL = 'operational';
    public const STATUS_MAINTENANCE = 'maintenance';
    public const STATUS_OUT_OF_SERVICE = 'out_of_service';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_PENDING_VERIFICATION = 'pending_verification';
    public const STATUSES = [self::STATUS_OPERATIONAL, self::STATUS_MAINTENANCE, self::STATUS_OUT_OF_SERVICE, self::STATUS_INACTIVE, self::STATUS_PENDING_VERIFICATION];

    protected $table = 'movement_vehicles';

    protected $fillable = [
        'fleet_number', 'plate_number', 'vehicle_type_id', 'manufacturer', 'model', 'model_year', 'passenger_capacity',
        'operational_status', 'status_reason', 'status_changed_at', 'status_changed_by', 'home_location_id', 'legacy_source_id', 'notes',
    ];

    protected $casts = ['model_year' => 'integer', 'passenger_capacity' => 'integer', 'status_changed_at' => 'datetime', 'legacy_source_id' => 'integer'];

    public function vehicleType()
    {
        return $this->belongsTo(VehicleType::class, 'vehicle_type_id')->withTrashed();
    }

    public function homeLocation()
    {
        return $this->belongsTo(Location::class, 'home_location_id')->withTrashed();
    }

    public function statusChanger()
    {
        return $this->belongsTo(User::class, 'status_changed_by');
    }

    public function unavailabilities()
    {
        return $this->hasMany(Unavailability::class, 'vehicle_id');
    }

    /** Type and capacity must be known before a verified status can be set (Revision 4). */
    public function isClassified(): bool
    {
        return $this->vehicle_type_id !== null && (int) $this->passenger_capacity >= 1;
    }
}
