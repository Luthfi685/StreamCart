<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'buyer_id',
        'seller_id',
        'live_session_id',
        'total_price',
        'shipping_fee',
        'status',
        // pending | checking_admin | success | fail | processed | completed | cancelled
        'shipping_address',
        'shipping_province',
        'shipping_city',
        'shipping_district',
        'shipping_courier',
        'shipping_tracking_number',
        'notes',
        // Payment info
        'payment_bank_name',
        'payment_bank_account',
        'payment_bank_account_name',
        'payment_proof',
        'payment_proof_uploaded_at',
        'admin_payment_note',
        'payment_verified_by',
        'payment_verified_at',
        // Escrow / Komisi
        'platform_fee_percent',
        'platform_fee_amount',
        'seller_net_amount',
        'completed_at',
        'cancel_reason',
        'cancel_requested_by',
        'is_refunded',
        'refund_bank_name',
        'refund_bank_account',
        'refund_bank_account_name',
        'refund_proof',
        'refund_processed_at',
    ];

    protected $casts = [
        'buyer_id'                    => 'integer',
        'seller_id'                   => 'integer',
        'live_session_id'             => 'integer',
        'total_price'                 => 'float',
        'platform_fee_percent'        => 'float',
        'platform_fee_amount'         => 'float',
        'seller_net_amount'           => 'float',
        'payment_proof_uploaded_at'   => 'datetime',
        'payment_verified_at'         => 'datetime',
        'completed_at'                => 'datetime',
    ];

    // Relationships
    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function liveSession()
    {
        return $this->belongsTo(LiveSession::class);
    }

    // Status helpers
    public function isPending(): bool        { return $this->status === 'pending'; }
    public function isCheckingAdmin(): bool  { return $this->status === 'checking_admin'; }
    public function isSuccess(): bool        { return $this->status === 'success'; }
    public function isFail(): bool           { return $this->status === 'fail'; }
    public function isProcessed(): bool      { return $this->status === 'processed'; }
    public function isCompleted(): bool      { return $this->status === 'completed'; }
    public function isCancelled(): bool      { return $this->status === 'cancelled'; }
    public function isPendingCancel(): bool  { return $this->status === 'pending_cancel'; }

    // Payment proof URL helper
    public function getPaymentProofUrlAttribute(): ?string
    {
        return $this->payment_proof ? asset('storage/' . $this->payment_proof) : null;
    }

    // Scope
    public function scopeForSeller($query, int $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }

    public function scopeForBuyer($query, int $buyerId)
    {
        return $query->where('buyer_id', $buyerId);
    }
}
