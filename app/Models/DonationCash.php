<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DonationCash extends Model
{
    use HasFactory;

    protected $fillable = [
        'donor_type',
        'donor_name',
        'contact_person',
        'phone',
        'day',
        'date',
        'amount',
        'payment_method',
        'receipt_no',
        'purpose_type',
        'monthly_activity_id',
        'finance_status',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function monthlyActivity()
    {
        return $this->belongsTo(MonthlyActivity::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'payable');
    }
}
