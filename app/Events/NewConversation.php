<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class NewConversation implements ShouldBroadcast
{
    use Dispatchable, InteractsWithBroadcasting;

    public $conversationId;

    public function __construct($conversationId)
    {
        $this->conversationId = $conversationId;
    }

    public function broadcastOn()
    {
        return new Channel('admin.conversations'); // Sử dụng channel mong muốn
    }

    public function broadcastAs()
    {
        return 'new.conversation';
    }

    public function broadcastWith()
    {
        return ['conversation_id' => $this->conversationId];
    }
}