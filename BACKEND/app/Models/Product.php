<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'name',
        'description',
        'price',
        'stock',
        'image_url',
        'images',
        'category',
        'is_active',
    ];

    protected $casts = [
        'seller_id' => 'integer',
        'price'     => 'float',
        'stock'     => 'integer',
        'is_active' => 'boolean',
        'images'    => 'array',
    ];

    // Relationships
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function liveSessions()
    {
        return $this->belongsToMany(LiveSession::class, 'live_session_products')
                    ->withPivot('is_pinned')
                    ->withTimestamps();
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function getAverageRatingAttribute()
    {
        return round($this->reviews()->avg('rating'), 1) ?: 0;
    }

    public function getImageUrlAttribute($value)
    {
        if ($value && !str_starts_with($value, 'http')) {
            return rtrim(config('app.url'), '/') . $value;
        }
        return $value;
    }

    // Scope: only available products
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('stock', '>', 0);
    }
}
