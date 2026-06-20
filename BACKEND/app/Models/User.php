<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',           // buyer | seller | admin
        'is_banned',
        'ban_reason',
        'store_name',
        'store_description',
        'store_logo',
        'bank_name',
        'bank_account',
        'bank_account_name',
        'phone',
        'address',
        'avatar',
        'otp_code',
        'otp_expires_at',
        'is_2fa_enabled',
        'google_id',
        'transaction_pin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'is_banned'         => 'boolean',
        'is_2fa_enabled'    => 'boolean',
    ];

    // Relationships
    public function products()
    {
        return $this->hasMany(Product::class, 'seller_id');
    }

    public function liveSessions()
    {
        return $this->hasMany(LiveSession::class, 'seller_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'buyer_id');
    }

    public function chatMessages()
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    // Role Helpers
    public function isBuyer(): bool  { return $this->role === 'buyer'; }
    public function isSeller(): bool { return $this->role === 'seller'; }
    public function isAdmin(): bool  { return $this->role === 'admin'; }
}
