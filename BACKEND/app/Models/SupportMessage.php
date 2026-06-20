<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'support_room_id',
        'sender_id',
        'sender_role',
        'message',
        'is_read',
    ];

    public function room()
    {
        return $this->belongsTo(SupportRoom::class, 'support_room_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
