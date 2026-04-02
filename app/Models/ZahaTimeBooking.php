<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZahaTimeBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'received_at',
        'booking_date',
        'time_from',
        'time_to',
        'entity_type',
        'contact_person',
        'phone',
        'children_count',
        'payment_cash_ref',
        'payment_electronic_ref',
        'discount_amount',
        'discount_reason',
        'status',
        'branch_id',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'booking_date' => 'date',
        'time_from' => 'datetime:H:i',
        'time_to' => 'datetime:H:i',
        'discount_amount' => 'decimal:2',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }


    public function payments()
    {
        return $this->morphMany(Payment::class, 'payable');
    }
}
