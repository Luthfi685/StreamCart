<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'title',
        'description',
        'thumbnail',
        'stream_url',
        'status',          // scheduled | live | finished
        'viewer_count',
        'likes_count',
        'pinned_product_id',
        'pinned_products',
        'scheduled_at',
        'bank_name',
        'bank_account',
        'bank_account_name',
    ];

    protected $casts = [
        'seller_id'     => 'integer',
        'viewer_count'  => 'integer',
        'likes_count'   => 'integer',
        'scheduled_at'  => 'datetime',
        'pinned_products' => 'array',
    ];

    // Relationships
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'live_session_products')
                    ->withPivot('is_pinned')
                    ->withTimestamps();
    }

    public function pinnedProduct()
    {
        return $this->belongsTo(Product::class, 'pinned_product_id');
    }

    public function chatMessages()
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // Scopes
    public function scopeLive($query)
    {
        return $query->where('status', 'live');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['live', 'scheduled']);
    }
}
