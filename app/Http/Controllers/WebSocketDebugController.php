<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Events\NewChatMessageEvent;
use App\Events\AdminNotificationEvent;
use App\Events\UserTypingEvent;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\AdminNotification;

class WebSocketDebugController extends Controller
{
    /**
     * Send message via WebSocket with detailed logging
     */
    public function sendMessage(Request $request)
    {
        $startTime = microtime(true);
        Log::info('WebSocket sendMessage request received', [
            'time' => date('Y-m-d H:i:s.u'),
            'conversation_id' => $request->conversation_id,
            'sender_type' => $request->sender_type,
            'message_length' => strlen($request->message ?? ''),
            'request_ip' => $request->ip()
        ]);

        $request->validate([
            'conversation_id' => 'required',
            'message' => 'required|string|max:2000',
            'sender_type' => 'required|in:user,admin,ai',
            'sender_id' => 'nullable',
        ]);

        try {
            // Find conversation
            $conversation = ChatConversation::find($request->conversation_id);
            if (!$conversation) {
                Log::warning('WebSocket: Conversation not found', [
                    'conversation_id' => $request->conversation_id
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Conversation not found'
                ], 404);
            }

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

            // Log before broadcasting
            Log::info('WebSocket: Broadcasting message', [
                'message_id' => $chatMessage->id,
                'conversation_id' => $request->conversation_id,
                'sender_type' => $request->sender_type,
                'time_before_broadcast' => microtime(true) - $startTime
            ]);

            // Broadcast message immediately
            event(new NewChatMessageEvent($request->conversation_id, [
                'message' => $request->message,
                'sender_type' => $request->sender_type,
                'sender_id' => $request->sender_id,
                'sender_name' => $senderName,
                'timestamp' => $chatMessage->timestamp,
                'metadata' => $request->metadata ?? []
            ]));

            $endTime = microtime(true);
            $processingTime = $endTime - $startTime;

            Log::info('WebSocket message sent successfully', [
                'message_id' => $chatMessage->id,
                'processing_time' => $processingTime,
                'timestamp' => date('Y-m-d H:i:s.u')
            ]);

            return response()->json([
                'success' => true,
                'message_id' => $chatMessage->id,
                'timestamp' => $chatMessage->timestamp,
                'processing_time_ms' => round($processingTime * 1000, 2)
            ]);
        } catch (\Exception $e) {
            Log::error('WebSocket message send failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'conversation_id' => $request->conversation_id,
                'processing_time' => microtime(true) - $startTime
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to send message: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle typing indicator with logging
     */
    public function handleTyping(Request $request)
    {
        $startTime = microtime(true);
        Log::info('WebSocket typing indicator request received', [
            'time' => date('Y-m-d H:i:s.u'),
            'conversation_id' => $request->conversation_id,
            'user_id' => $request->user_id,
            'is_typing' => $request->is_typing,
            'user_type' => $request->user_type
        ]);

        $request->validate([
            'conversation_id' => 'required',
            'user_id' => 'required',
            'is_typing' => 'required|boolean',
            'user_type' => 'required|in:user,admin'
        ]);

        try {
            // Log before broadcasting
            Log::info('WebSocket: Broadcasting typing indicator', [
                'conversation_id' => $request->conversation_id,
                'user_id' => $request->user_id,
                'is_typing' => $request->is_typing,
                'time_before_broadcast' => microtime(true) - $startTime
            ]);

            event(new UserTypingEvent(
                $request->conversation_id,
                $request->user_id,
                $request->is_typing,
                $request->user_type
            ));

            $endTime = microtime(true);
            $processingTime = $endTime - $startTime;

            Log::info('WebSocket typing indicator sent successfully', [
                'processing_time' => $processingTime,
                'timestamp' => date('Y-m-d H:i:s.u')
            ]);

            return response()->json([
                'success' => true,
                'processing_time_ms' => round($processingTime * 1000, 2)
            ]);
        } catch (\Exception $e) {
            Log::error('WebSocket typing indicator failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'conversation_id' => $request->conversation_id,
                'processing_time' => microtime(true) - $startTime
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to send typing indicator: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send admin notification with logging
     */
    public function sendAdminNotification(Request $request)
    {
        $startTime = microtime(true);
        Log::info('WebSocket admin notification request received', [
            'time' => date('Y-m-d H:i:s.u'),
            'conversation_id' => $request->conversation_id,
            'user_id' => $request->user_id,
            'priority' => $request->priority
        ]);

        $request->validate([
            'conversation_id' => 'required',
            'user_id' => 'nullable',
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

            // Log before broadcasting
            Log::info('WebSocket: Broadcasting admin notification', [
                'notification_id' => $notification->id,
                'conversation_id' => $request->conversation_id,
                'time_before_broadcast' => microtime(true) - $startTime
            ]);

            // Broadcast to all admins
            event(new AdminNotificationEvent([
                'id' => $notification->id,
                'conversation_id' => $request->conversation_id,
                'user_id' => $request->user_id,
                'message' => $request->message,
                'priority' => $request->priority,
                'timestamp' => now()
            ]));

            $endTime = microtime(true);
            $processingTime = $endTime - $startTime;

            Log::info('WebSocket admin notification sent successfully', [
                'notification_id' => $notification->id,
                'processing_time' => $processingTime,
                'timestamp' => date('Y-m-d H:i:s.u')
            ]);

            return response()->json([
                'success' => true,
                'notification_id' => $notification->id,
                'processing_time_ms' => round($processingTime * 1000, 2)
            ]);
        } catch (\Exception $e) {
            Log::error('WebSocket admin notification failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'conversation_id' => $request->conversation_id,
                'processing_time' => microtime(true) - $startTime
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to send admin notification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test WebSocket connection
     */
    public function testConnection()
    {
        $startTime = microtime(true);
        Log::info('WebSocket test connection request received', [
            'time' => date('Y-m-d H:i:s.u')
        ]);

        try {
            // Test event broadcast
            event(new \App\Events\TestEvent([
                'message' => 'Test connection successful',
                'timestamp' => now()
            ]));

            $endTime = microtime(true);
            $processingTime = $endTime - $startTime;

            Log::info('WebSocket test connection successful', [
                'processing_time' => $processingTime,
                'timestamp' => date('Y-m-d H:i:s.u')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'WebSocket connection test successful',
                'processing_time_ms' => round($processingTime * 1000, 2),
                'server_time' => now()->format('Y-m-d H:i:s.u')
            ]);
        } catch (\Exception $e) {
            Log::error('WebSocket test connection failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'processing_time' => microtime(true) - $startTime
            ]);

            return response()->json([
                'success' => false,
                'error' => 'WebSocket test connection failed: ' . $e->getMessage()
            ], 500);
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
