<?php

namespace App\Modules\Events\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class BazaarTableDiscount extends Model
{
    protected $fillable = ['original_amount', 'discount_type', 'discount_value', 'final_amount', 'reason', 'status', 'requested_by', 'approved_by', 'requested_at', 'approved_at', 'decision_note'];
    protected $casts = ['original_amount' => 'decimal:2', 'discount_value' => 'decimal:2', 'final_amount' => 'decimal:2', 'requested_at' => 'datetime', 'approved_at' => 'datetime'];
    public function table() { return $this->belongsTo(BazaarTable::class, 'bazaar_table_id'); }
    public function requester() { return $this->belongsTo(User::class, 'requested_by'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
}
