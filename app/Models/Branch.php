<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'city',
        'address',
    ];

    public function centers()
    {
        return $this->hasMany(Center::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function monthlyActivities()
    {
        return $this->hasMany(MonthlyActivity::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function zahaTimeBookings()
    {
        return $this->hasMany(ZahaTimeBooking::class);
    }

    public function maintenanceRequests()
    {
        return $this->hasMany(MaintenanceRequest::class);
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }
}
