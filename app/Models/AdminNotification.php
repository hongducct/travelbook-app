<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'message',
        'priority',
        'status',
        'assigned_admin_id',
        'notified_at',
        'resolved_at'
    ];

    protected $casts = [
        'notified_at' => 'datetime',
        'resolved_at' => 'datetime'
    ];

    public function conversation()
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    public function assignedAdmin()
    {
        return $this->belongsTo(Admin::class, 'assigned_admin_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope for pending notifications
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for high priority notifications
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['high', 'urgent']);
    }

    /**
     * Assign notification to admin
     */
    public function assignTo($adminId)
    {
        $this->update([
            'assigned_admin_id' => $adminId,
            'status' => 'assigned'
        ]);
    }

    /**
     * Claim notification by admin (optional feature)
     */
    public function claimByAdmin($adminId)
    {
        $this->update([
            'assigned_admin_id' => $adminId,
            'status' => 'assigned'
        ]);
    }

    /**
     * Mark notification as resolved
     */
    public function markResolved()
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now()
        ]);
    }

    /**
     * Get priority color for UI
     */
    public function getPriorityColorAttribute()
    {
        return match ($this->priority) {
            'urgent' => 'red',
            'high' => 'orange',
            'normal' => 'blue',
            'low' => 'gray',
            default => 'blue'
        };
    }

    /**
     * Get priority label
     */
    public function getPriorityLabelAttribute()
    {
        return match ($this->priority) {
            'urgent' => 'Khẩn cấp',
            'high' => 'Cao',
            'normal' => 'Bình thường',
            'low' => 'Thấp',
            default => 'Bình thường'
        };
    }
}
