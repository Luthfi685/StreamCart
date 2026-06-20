<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'status',
    ];

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function messages()
    {
        return $this->hasMany(SupportMessage::class, 'support_room_id');
    }
}
