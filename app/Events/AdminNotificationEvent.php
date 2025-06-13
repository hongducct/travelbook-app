<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdminNotificationEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $notificationData;

    /**
     * Create a new event instance.
     */
    public function __construct($notificationData)
    {
        $this->notificationData = $notificationData;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            // Thay đổi từ PrivateChannel sang Channel để không cần authentication
            new Channel('admin.notifications'),
            new Channel('admin.dashboard')
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => uniqid(),
            'conversation_id' => $this->notificationData['conversation_id'],
            'message' => $this->notificationData['message'],
            'priority' => $this->notificationData['priority'] ?? 'normal',
            'timestamp' => $this->notificationData['timestamp'] ?? now()->toISOString(),
            'user_id' => $this->notificationData['user_id'] ?? null,
            'type' => 'chat_notification'
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'admin.notification';
    }
}
