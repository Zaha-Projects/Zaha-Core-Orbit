<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'city',
        'address',
        'color_hex',
        'icon',
    ];


    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'branch_user_assignments')
            ->withTimestamps();
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
