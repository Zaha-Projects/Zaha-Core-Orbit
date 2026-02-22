<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'received_at',
        'booking_date',
        'time_from',
        'time_to',
        'received_by',
        'customer_name',
        'facility_name',
        'payment_type',
        'receipt_ref',
        'paid_at',
        'discount_amount',
        'discount_reason',
        'status',
        'branch_id',
        'center_id',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'booking_date' => 'date',
        'time_from' => 'datetime:H:i',
        'time_to' => 'datetime:H:i',
        'paid_at' => 'datetime',
        'discount_amount' => 'decimal:2',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function center()
    {
        return $this->belongsTo(Center::class);
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'payable');
    }
}
