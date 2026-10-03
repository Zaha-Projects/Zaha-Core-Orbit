<?php

namespace App\Modules\Events\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class BazaarTable extends Model
{
    protected $fillable = ['table_number', 'rental_type', 'tenant_name', 'tenant_phone', 'community_organization_id', 'table_liaison_name', 'table_liaison_phone', 'planned_material_description', 'planned_rental_amount', 'liaison_user_id', 'notes', 'was_booked', 'actual_renter_matches', 'actual_tenant_name', 'actual_tenant_phone', 'actual_community_organization_id', 'renter_change_note', 'actual_material_description', 'material_matches', 'material_mismatch_note', 'amount_due', 'is_paid', 'amount_paid', 'payment_status', 'actual_notes'];
    protected $casts = ['was_booked' => 'boolean', 'actual_renter_matches' => 'boolean', 'material_matches' => 'boolean', 'is_paid' => 'boolean', 'planned_rental_amount' => 'decimal:2', 'amount_due' => 'decimal:2', 'amount_paid' => 'decimal:2'];
    public function bazaar() { return $this->belongsTo(Bazaar::class); }
    public function organization() { return $this->belongsTo(CommunityOrganization::class, 'community_organization_id'); }
    public function actualOrganization() { return $this->belongsTo(CommunityOrganization::class, 'actual_community_organization_id'); }
    public function liaison() { return $this->belongsTo(User::class, 'liaison_user_id'); }
    public function discounts() { return $this->hasMany(BazaarTableDiscount::class); }
    public function currentDiscount() { return $this->hasOne(BazaarTableDiscount::class)->latestOfMany(); }
}
