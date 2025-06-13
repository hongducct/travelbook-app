<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'started_at',
        'last_activity',
        'metadata'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'last_activity' => 'datetime',
        'metadata' => 'array'
    ];

    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function adminNotifications()
    {
        return $this->hasMany(AdminNotification::class, 'conversation_id');
    }
}
