<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\AdminNotification;
use App\Events\NewChatMessageEvent;
use App\Events\AdminNotificationEvent;
use App\Events\UserTypingEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebSocketChatController extends Controller
{
    /**
     * Send message via WebSocket
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|integer',
            'message' => 'required|string|max:2000',
            'sender_type' => 'required|in:user,admin,ai',
            'sender_id' => 'nullable',
        ]);

        try {
            $conversation = ChatConversation::findOrFail($request->conversation_id);

            // Save message to database
            $chatMessage = ChatMessage::create([
                'conversation_id' => $request->conversation_id,
                'sender_type' => $request->sender_type,
                'sender_id' => $request->sender_id,
                'message' => $request->message,
                'metadata' => json_encode($request->metadata ?? []),
                'timestamp' => now()
            ]);

            // Update conversation
            $conversation->update(['last_activity' => now()]);

            // Get sender name
            $senderName = $this->getSenderName($request->sender_type, $request->sender_id);

            // Broadcast message immediately
            broadcast(new NewChatMessageEvent($request->conversation_id, [
                'message' => $request->message,
                'sender_type' => $request->sender_type,
                'sender_id' => $request->sender_id,
                'sender_name' => $senderName,
                'timestamp' => $chatMessage->timestamp,
                'metadata' => $request->metadata ?? []
            ]));

            return response()->json([
                'success' => true,
                'message_id' => $chatMessage->id,
                'timestamp' => $chatMessage->timestamp
            ]);
        } catch (\Exception $e) {
            Log::error('WebSocket message send failed', [
                'error' => $e->getMessage(),
                'conversation_id' => $request->conversation_id
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to send message'
            ], 500);
        }
    }

    /**
     * Handle typing indicator
     */
    public function handleTyping(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|integer',
            'user_id' => 'required',
            'is_typing' => 'required|boolean',
            'user_type' => 'required|in:user,admin'
        ]);

        try {
            broadcast(new UserTypingEvent(
                $request->conversation_id,
                $request->user_id,
                $request->is_typing,
                $request->user_type
            ));

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Typing indicator failed', [
                'error' => $e->getMessage(),
                'conversation_id' => $request->conversation_id
            ]);

            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Send admin notification
     */
    public function sendAdminNotification(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|integer',
            'user_id' => 'required|integer',
            'message' => 'required|string',
            'priority' => 'required|in:low,normal,high,urgent'
        ]);

        try {
            // Create notification
            $notification = AdminNotification::create([
                'conversation_id' => $request->conversation_id,
                'user_id' => $request->user_id,
                'message' => $request->message,
                'priority' => $request->priority,
                'status' => 'pending',
                'notified_at' => now()
            ]);

            // Broadcast to all admins
            broadcast(new AdminNotificationEvent([
                'id' => $notification->id,
                'conversation_id' => $request->conversation_id,
                'user_id' => $request->user_id,
                'message' => $request->message,
                'priority' => $request->priority,
                'timestamp' => now()
            ]));

            return response()->json([
                'success' => true,
                'notification_id' => $notification->id
            ]);
        } catch (\Exception $e) {
            Log::error('Admin notification failed', [
                'error' => $e->getMessage(),
                'conversation_id' => $request->conversation_id
            ]);

            return response()->json(['success' => false], 500);
        }
    }

    private function getSenderName($senderType, $senderId)
    {
        switch ($senderType) {
            case 'admin':
                $admin = \App\Models\Admin::find($senderId);
                return $admin ? $admin->name : 'Admin';
            case 'user':
                $user = \App\Models\User::find($senderId);
                return $user ? $user->name : 'User';
            case 'ai':
                return 'AI Assistant';
            default:
                return 'Unknown';
        }
    }
}
