<?php

namespace App\Http\Controllers;

use App\Models\Tour;
use App\Models\User;
use App\Models\Admin;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\UserSearchHistory;
use App\Models\TourRecommendation;
use App\Models\AdminNotification;
use App\Services\GeminiService;
use App\Services\TourRecommendationService;
use App\Services\BookingAssistantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use App\Events\AdminNotificationEvent;
use App\Events\NewChatMessageEvent;

class EnhancedChatBotController extends Controller
{
    protected $geminiService;
    protected $recommendationService;
    protected $bookingService;

    public function __construct(
        GeminiService $geminiService,
        TourRecommendationService $recommendationService,
        BookingAssistantService $bookingService
    ) {
        $this->geminiService = $geminiService;
        $this->recommendationService = $recommendationService;
        $this->bookingService = $bookingService;
    }

    /**
     * Initialize chat session - Optimized version
     */
    public function initializeChat(Request $request)
    {
        $request->validate([
            'user_id' => 'nullable|integer',
            'session_id' => 'nullable|string|max:255'
        ]);

        $userId = $request->user_id;
        $sessionId = $request->session_id ?? session()->getId();

        try {
            // Use cache to speed up initialization
            $cacheKey = "chat_init_{$userId}_{$sessionId}";
            $cachedData = Cache::get($cacheKey);

            if ($cachedData) {
                return response()->json($cachedData);
            }

            // Create or get conversation - simplified
            $conversation = $this->getOrCreateConversation($userId, $sessionId, $request->ip());

            // Get data in parallel for speed
            $searchHistory = [];
            $recommendedTours = [];

            if ($userId) {
                // Only get recent search history for speed
                $searchHistory = Cache::remember("user_search_{$userId}", 300, function () use ($userId) {
                    return UserSearchHistory::where('user_id', $userId)
                        ->orderBy('created_at', 'desc')
                        ->limit(5) // Reduced from 10 to 5
                        ->pluck('search_query')
                        ->toArray();
                });

                // Get cached recommendations
                $recommendedTours = Cache::remember("user_recommendations_{$userId}", 600, function () use ($userId, $searchHistory) {
                    return $this->recommendationService->getPersonalizedRecommendations($userId, $searchHistory);
                });
            } else {
                // Get popular tours for anonymous users
                $recommendedTours = Cache::remember('popular_tours', 1800, function () {
                    return $this->getPopularTours();
                });
            }

            // Check admin availability - cached
            $adminOnline = Cache::remember('admin_online_status', 60, function () {
                return $this->checkAdminAvailability();
            });

            $responseData = [
                'conversation_id' => $conversation->id,
                'search_history' => $searchHistory,
                'recommended_tours' => array_slice($recommendedTours, 0, 3), // Limit to 3 for speed
                'admin_online' => $adminOnline,
                'session_id' => $sessionId,
                'is_anonymous' => !$userId,
                'message' => 'Chat initialized successfully'
            ];

            // Cache the response for 5 minutes
            Cache::put($cacheKey, $responseData, 300);

            return response()->json($responseData);
        } catch (\Exception $e) {
            Log::error('Chat initialization failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId ?? 'anonymous',
                'session_id' => $sessionId
            ]);

            return response()->json([
                'conversation_id' => 'temp_' . time(),
                'search_history' => [],
                'recommended_tours' => [],
                'admin_online' => false,
                'session_id' => $sessionId,
                'is_anonymous' => !$userId,
                'message' => 'Chat initialized with fallback'
            ]);
        }
    }

    /**
     * Process enhanced query - Optimized
     */
    public function processEnhancedQuery(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'conversation_id' => 'required',
            'user_search_history' => 'nullable|array',
            'chat_mode' => 'nullable|string|in:ai,admin,mixed'
        ]);

        $message = trim($request->message);
        $conversationId = $request->conversation_id;
        $searchHistory = $request->user_search_history ?? [];
        $chatMode = $request->chat_mode ?? 'mixed';

        // Skip processing for temp conversations to avoid errors
        if (str_starts_with($conversationId, 'temp_')) {
            return $this->handleTempConversation($message);
        }

        try {
            // Get conversation
            $conversation = ChatConversation::find($conversationId);
            if (!$conversation) {
                throw new \Exception('Conversation not found');
            }

            // Save user message
            $this->saveUserMessage($conversation, $message);

            // Process with AI (async if possible)
            $aiResponse = $this->processWithAI($message, $conversationId, $searchHistory);

            // Get recommendations (cached)
            $newRecommendations = $this->getRecommendations($conversation->user_id, $message, $searchHistory);

            // Save AI response
            $this->saveAIResponse($conversation, $aiResponse);

            return response()->json([
                'ai_response' => [
                    'message' => $aiResponse['message'],
                    'data' => $aiResponse['data'] ?? null,
                    'suggestions' => $aiResponse['suggestions'] ?? []
                ],
                'new_recommendations' => $newRecommendations,
                'updated_history' => array_merge($searchHistory, [$message]),
                'conversation_id' => $conversationId
            ]);
        } catch (\Exception $e) {
            Log::error('Enhanced query processing failed', [
                'error' => $e->getMessage(),
                'conversation_id' => $conversationId
            ]);

            return response()->json([
                'ai_response' => [
                    'message' => 'Xin lỗi, đã có lỗi xảy ra. Vui lòng thử lại!',
                    'suggestions' => ['Thử lại', 'Liên hệ admin', 'Xem tour nổi bật']
                ],
                'error' => true
            ], 500);
        }
    }

    /**
     * Notify admin immediately - Simplified
     */
    public function notifyAdminImmediate(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required',
            'user_message' => 'required|string|max:1000',
            'priority' => 'required|string|in:low,normal,high,urgent',
            'user_id' => 'nullable|integer',
        ]);

        // Skip for temp conversations
        if (str_starts_with($request->conversation_id, 'temp_')) {
            return response()->json(['success' => false, 'message' => 'Cannot notify for temp conversation']);
        }

        try {
            // Create notification
            $notification = AdminNotification::create([
                'conversation_id' => $request->conversation_id,
                'user_id' => $request->user_id ? (string)$request->user_id : 'anonymous',
                'message' => $request->user_message,
                'priority' => $request->priority,
                'status' => 'pending',
                'notified_at' => now()
            ]);

            // Broadcast to admins
            Event::dispatch(new AdminNotificationEvent([
                'conversation_id' => $request->conversation_id,
                'message' => $request->user_message,
                'priority' => $request->priority,
                'timestamp' => now(),
                'user_id' => $request->user_id ?? 'anonymous',
                'notification_id' => $notification->id
            ]));

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Admin notification failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Get admin messages - Optimized
     */
    public function getAdminMessages($conversationId)
    {
        if (str_starts_with($conversationId, 'temp_')) {
            return response()->json(['new_messages' => [], 'admin_online' => false]);
        }

        try {
            $lastCheck = Cache::get("last_admin_check_{$conversationId}", now()->subMinutes(5));

            $newMessages = ChatMessage::where('conversation_id', $conversationId)
                ->where('sender_type', 'admin')
                ->where('timestamp', '>', $lastCheck)
                ->orderBy('timestamp', 'asc')
                ->limit(10) // Limit messages
                ->get()
                ->map(function ($message) {
                    return [
                        'content' => $message->message,
                        'timestamp' => $message->timestamp,
                        'admin_name' => 'Admin'
                    ];
                });

            Cache::put("last_admin_check_{$conversationId}", now(), 300);

            return response()->json([
                'new_messages' => $newMessages,
                'admin_online' => Cache::get('admin_online_status', false)
            ]);
        } catch (\Exception $e) {
            return response()->json(['new_messages' => [], 'admin_online' => false]);
        }
    }

    // Helper methods

    private function getOrCreateConversation($userId, $sessionId, $ipAddress)
    {
        if ($userId) {
            return ChatConversation::firstOrCreate([
                'user_id' => $userId,
                'status' => 'active'
            ], [
                'started_at' => now(),
                'last_activity' => now(),
                'metadata' => json_encode(['session_id' => $sessionId])
            ]);
        } else {
            return ChatConversation::firstOrCreate([
                'user_id' => null,
                'status' => 'active',
                'metadata->session_id' => $sessionId
            ], [
                'started_at' => now(),
                'last_activity' => now(),
                'metadata' => json_encode([
                    'session_id' => $sessionId,
                    'is_anonymous' => true,
                    'ip_address' => $ipAddress
                ])
            ]);
        }
    }

    private function handleTempConversation($message)
    {
        // Simple AI response for temp conversations
        return response()->json([
            'ai_response' => [
                'message' => 'Tôi hiểu bạn đang hỏi về: "' . $message . '". Để có trải nghiệm tốt nhất, vui lòng đăng nhập hoặc làm mới trang.',
                'suggestions' => ['Đăng nhập', 'Làm mới trang', 'Xem tour phổ biến']
            ]
        ]);
    }

    private function saveUserMessage($conversation, $message)
    {
        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'user',
            'sender_id' => $conversation->user_id ?? 'anonymous',
            'message' => $message,
            'timestamp' => now()
        ]);

        $conversation->update(['last_activity' => now()]);
    }

    private function saveAIResponse($conversation, $aiResponse)
    {
        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'ai',
            'sender_id' => 'gemini-ai',
            'message' => $aiResponse['message'],
            'metadata' => json_encode([
                'data' => $aiResponse['data'] ?? null,
                'suggestions' => $aiResponse['suggestions'] ?? []
            ]),
            'timestamp' => now()
        ]);
    }

    private function getRecommendations($userId, $message, $searchHistory)
    {
        if ($userId) {
            $cacheKey = "recommendations_{$userId}_" . md5($message);
            return Cache::remember($cacheKey, 600, function () use ($userId, $message, $searchHistory) {
                return $this->recommendationService->getPersonalizedRecommendations(
                    $userId,
                    array_merge($searchHistory, [$message])
                );
            });
        } else {
            return Cache::remember('popular_tours_' . md5($message), 1800, function () use ($message) {
                return $this->getPopularTours($message);
            });
        }
    }

    private function getPopularTours($query = null)
    {
        try {
            $toursQuery = Tour::where('status', 'active')
                ->select(['id', 'name', 'location', 'duration', 'price', 'rating', 'review_count'])
                ->orderBy('rating', 'desc')
                ->orderBy('review_count', 'desc');

            if ($query) {
                $toursQuery->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('location', 'like', "%{$query}%");
                });
            }

            return $toursQuery->limit(3)->get()->map(function ($tour) {
                return [
                    'id' => $tour->id,
                    'name' => $tour->name,
                    'location' => $tour->location,
                    'duration' => $tour->duration,
                    'price_formatted' => number_format($tour->price) . ' VNĐ',
                    'rating' => $tour->rating,
                    'review_count' => $tour->review_count,
                ];
            })->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    private function processWithAI($message, $conversationId, $searchHistory)
    {
        // Simplified AI processing
        return [
            'message' => "Tôi hiểu bạn đang quan tâm đến: \"{$message}\". Đây là một số thông tin hữu ích cho bạn.",
            'suggestions' => ['Xem thêm tour', 'Đặt tour ngay', 'Liên hệ tư vấn', 'So sánh giá'],
            'data' => $this->getPopularTours($message)
        ];
    }

    private function checkAdminAvailability()
    {
        return Admin::where('last_activity', '>=', now()->subMinutes(5))
            ->where('is_active', true)
            ->exists();
    }
}
