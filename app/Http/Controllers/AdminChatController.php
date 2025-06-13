<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\AdminNotification;
use App\Models\Admin;
use App\Events\AdminResponseEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminChatController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get all active conversations for admin dashboard
     */
    public function getActiveConversations(Request $request)
    {
        $admin = $request->user();

        if ($admin->getTable() !== 'admins') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $conversations = ChatConversation::with([
                'messages' => function ($query) {
                    $query->orderBy('timestamp', 'desc')->limit(1);
                },
                'adminNotifications' => function ($query) {
                    $query->where('status', '!=', 'resolved')->orderBy('priority', 'desc');
                }
            ])
                ->where('status', 'active')
                ->where('last_activity', '>=', now()->subHours(24))
                ->orderBy('last_activity', 'desc')
                ->paginate(20);

            return response()->json($conversations);
        } catch (\Exception $e) {
            Log::error('Failed to get active conversations', [
                'error' => $e->getMessage(),
                'admin_id' => $admin->id
            ]);

            return response()->json([
                'message' => 'Failed to load conversations'
            ], 500);
        }
    }

    /**
     * Get conversation details with messages
     */
    public function getConversationDetails($conversationId, Request $request)
    {
        $admin = $request->user();

        if ($admin->getTable() !== 'admins') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $conversation = ChatConversation::with([
                'messages' => function ($query) {
                    $query->orderBy('timestamp', 'asc');
                },
                'adminNotifications' => function ($query) {
                    $query->where('status', '!=', 'resolved');
                }
            ])->findOrFail($conversationId);

            // Removed automatic assignment of notifications to the viewing admin
            // This allows all admins to see and handle the same notifications

            return response()->json([
                'conversation' => $conversation,
                'admin_info' => [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'email' => $admin->email
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get conversation details', [
                'error' => $e->getMessage(),
                'conversation_id' => $conversationId,
                'admin_id' => $admin->id
            ]);

            return response()->json([
                'message' => 'Failed to load conversation'
            ], 500);
        }
    }

    /**
     * Send admin response to user
     */
    public function sendAdminResponse(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:chat_conversations,id',
            'message' => 'required|string|max:2000'
        ]);

        $admin = $request->user();

        if ($admin->getTable() !== 'admins') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $conversationId = $request->conversation_id;
            $message = $request->message;

            // Update admin last activity
            $admin->update(['last_activity' => now()]);

            // Save admin message
            $chatMessage = ChatMessage::create([
                'conversation_id' => $conversationId,
                'sender_type' => 'admin',
                'sender_id' => $admin->id,
                'message' => $message,
                'timestamp' => now()
            ]);

            // Update conversation last activity
            ChatConversation::where('id', $conversationId)
                ->update(['last_activity' => now()]);

            // Mark related notifications as resolved without assigning to a specific admin
            AdminNotification::where('conversation_id', $conversationId)
                ->where('status', '!=', 'resolved')
                ->update([
                    'status' => 'resolved',
                    'resolved_at' => now()
                    // Removed assigned_admin_id update
                ]);

            // Broadcast admin response to user
            broadcast(new AdminResponseEvent(
                $conversationId,
                $message,
                $admin->id,
                $admin->name
            ));

            Log::info('Admin response sent', [
                'conversation_id' => $conversationId,
                'admin_id' => $admin->id,
                'message_length' => strlen($message)
            ]);

            return response()->json([
                'message' => 'Response sent successfully',
                'chat_message' => $chatMessage
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send admin response', [
                'error' => $e->getMessage(),
                'conversation_id' => $request->conversation_id,
                'admin_id' => $admin->id
            ]);

            return response()->json([
                'message' => 'Failed to send response'
            ], 500);
        }
    }

    /**
     * Get pending notifications for admin
     */
    public function getPendingNotifications(Request $request)
    {
        $admin = $request->user();

        if ($admin->getTable() !== 'admins') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $notifications = AdminNotification::with([
                'conversation',
                'user'
            ])
                ->where('status', 'pending') // Only pending notifications for all admins
                ->orderBy('priority', 'desc')
                ->orderBy('notified_at', 'asc')
                ->limit(50)
                ->get();

            return response()->json([
                'notifications' => $notifications,
                'total_pending' => AdminNotification::pending()->count(),
                'high_priority_count' => AdminNotification::pending()->highPriority()->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get pending notifications', [
                'error' => $e->getMessage(),
                'admin_id' => $admin->id
            ]);

            return response()->json([
                'message' => 'Failed to load notifications'
            ], 500);
        }
    }

    /**
     * Update admin online status
     */
    public function updateOnlineStatus(Request $request)
    {
        $admin = $request->user();

        if ($admin->getTable() !== 'admins') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $admin->update([
                'last_activity' => now(),
                'is_active' => true
            ]);

            return response()->json([
                'message' => 'Status updated',
                'last_activity' => $admin->last_activity
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update admin status', [
                'error' => $e->getMessage(),
                'admin_id' => $admin->id
            ]);

            return response()->json([
                'message' => 'Failed to update status'
            ], 500);
        }
    }

    /**
     * Get chat statistics for admin dashboard
     */
    public function getChatStatistics(Request $request)
    {
        $admin = $request->user();

        if ($admin->getTable() !== 'admins') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $today = now()->startOfDay();
            $thisWeek = now()->startOfWeek();
            $thisMonth = now()->startOfMonth();

            $statistics = [
                'today' => [
                    'conversations' => ChatConversation::where('started_at', '>=', $today)->count(),
                    'messages' => ChatMessage::where('timestamp', '>=', $today)->count(),
                    'admin_responses' => ChatMessage::where('sender_type', 'admin')
                        ->where('timestamp', '>=', $today)->count(),
                    'notifications' => AdminNotification::where('notified_at', '>=', $today)->count()
                ],
                'this_week' => [
                    'conversations' => ChatConversation::where('started_at', '>=', $thisWeek)->count(),
                    'messages' => ChatMessage::where('timestamp', '>=', $thisWeek)->count(),
                    'admin_responses' => ChatMessage::where('sender_type', 'admin')
                        ->where('timestamp', '>=', $thisWeek)->count(),
                    'notifications' => AdminNotification::where('notified_at', '>=', $thisWeek)->count()
                ],
                'this_month' => [
                    'conversations' => ChatConversation::where('started_at', '>=', $thisMonth)->count(),
                    'messages' => ChatMessage::where('timestamp', '>=', $thisMonth)->count(),
                    'admin_responses' => ChatMessage::where('sender_type', 'admin')
                        ->where('timestamp', '>=', $thisMonth)->count(),
                    'notifications' => AdminNotification::where('notified_at', '>=', $thisMonth)->count()
                ],
                'current_status' => [
                    'active_conversations' => ChatConversation::where('status', 'active')
                        ->where('last_activity', '>=', now()->subHours(1))->count(),
                    'pending_notifications' => AdminNotification::pending()->count(),
                    'high_priority_notifications' => AdminNotification::pending()->highPriority()->count(),
                    'online_admins' => Admin::where('last_activity', '>=', now()->subMinutes(5))
                        ->where('is_active', true)->count()
                ]
            ];

            return response()->json($statistics);
        } catch (\Exception $e) {
            Log::error('Failed to get chat statistics', [
                'error' => $e->getMessage(),
                'admin_id' => $admin->id
            ]);

            return response()->json([
                'message' => 'Failed to load statistics'
            ], 500);
        }
    }

    /**
     * Close conversation
     */
    public function closeConversation($conversationId, Request $request)
    {
        $admin = $request->user();

        if ($admin->getTable() !== 'admins') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $conversation = ChatConversation::findOrFail($conversationId);

            $conversation->update([
                'status' => 'closed',
                'last_activity' => now()
            ]);

            // Resolve all pending notifications for this conversation
            AdminNotification::where('conversation_id', $conversationId)
                ->where('status', '!=', 'resolved')
                ->update([
                    'status' => 'resolved',
                    'resolved_at' => now()
                    // Removed assigned_admin_id update
                ]);

            Log::info('Conversation closed by admin', [
                'conversation_id' => $conversationId,
                'admin_id' => $admin->id
            ]);

            return response()->json([
                'message' => 'Conversation closed successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to close conversation', [
                'error' => $e->getMessage(),
                'conversation_id' => $conversationId,
                'admin_id' => $admin->id
            ]);

            return response()->json([
                'message' => 'Failed to close conversation'
            ], 500);
        }
    }

    /**
     * Claim a notification (optional feature)
     */
    public function claimNotification(Request $request)
    {
        $request->validate([
            'notification_id' => 'required|exists:admin_notifications,id',
            'admin_id' => 'required|exists:admins,id'
        ]);

        $admin = $request->user();

        if ($admin->getTable() !== 'admins' || $admin->id != $request->admin_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $notification = AdminNotification::findOrFail($request->notification_id);
            $notification->claimByAdmin($admin->id);

            return response()->json(['message' => 'Notification claimed successfully']);
        } catch (\Exception $e) {
            Log::error('Failed to claim notification', [
                'error' => $e->getMessage(),
                'notification_id' => $request->notification_id,
                'admin_id' => $admin->id
            ]);

            return response()->json(['message' => 'Failed to claim notification'], 500);
        }
    }
}
