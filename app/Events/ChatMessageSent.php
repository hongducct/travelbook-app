<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class ChatMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithBroadcasting;

    public $conversationId;
    public $message;
    public $senderType;
    public $senderId;

    public function __construct($conversationId, $message, $senderType, $senderId)
    {
        $this->conversationId = $conversationId;
        $this->message = $message;
        $this->senderType = $senderType;
        $this->senderId = $senderId;
    }

    public function broadcastOn()
    {
        return new Channel('chat.' . $this->conversationId);
    }

    public function broadcastAs()
    {
        return 'message.sent';
    }

    public function broadcastWith()
    {
        return [
            'conversation_id' => $this->conversationId,
            'message' => $this->message,
            'sender_type' => $this->senderType,
            'sender_id' => $this->senderId,
            'timestamp' => now()->toISOString(),
        ];
    }
}
