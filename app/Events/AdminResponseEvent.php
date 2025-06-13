<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdminResponseEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversationId;
    public $adminMessage;
    public $adminId;
    public $adminName;

    /**
     * Create a new event instance.
     */
    public function __construct($conversationId, $adminMessage, $adminId, $adminName = null)
    {
        $this->conversationId = $conversationId;
        $this->adminMessage = $adminMessage;
        $this->adminId = $adminId;
        $this->adminName = $adminName ?? 'Admin';
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            // Thay đổi từ PrivateChannel sang Channel để không cần authentication
            new Channel('chat.' . $this->conversationId)
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'message' => $this->adminMessage,
            'admin_id' => $this->adminId,
            'admin_name' => $this->adminName,
            'timestamp' => now()->toISOString(),
            'sender_type' => 'admin'
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'admin.response';
    }
}
