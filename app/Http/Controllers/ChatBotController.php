<?php

namespace App\Http\Controllers;

use App\Models\Tour;
use App\Models\Location;
use App\Models\TravelType;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatBotController extends Controller
{
    protected $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Process ANY chatbot queries with enhanced AI capabilities
     */
    public function processQuery(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'conversation_id' => 'nullable|string'
        ]);

        $message = trim($request->message);
        $conversationId = $request->conversation_id;

        Log::info('Enhanced ChatBot Query', [
            'message' => $message,
            'conversation_id' => $conversationId,
            'timestamp' => now()
        ]);

        try {
            // Always use AI for intelligent responses
            return $this->processWithEnhancedAI($message, $conversationId);
        } catch (\Exception $e) {
            Log::error('ChatBot Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json($this->getIntelligentFallbackResponse($message));
        }
    }

    /**
     * Process with enhanced AI capabilities
     */
    private function processWithEnhancedAI($message, $conversationId = null)
    {
        try {
            // Analyze user intent and gather context
            $context = $this->buildComprehensiveContext($message);

            Log::info('Processing with AI', [
                'message' => $message,
                'context_type' => $context['query_type'] ?? 'unknown',
                'has_tours' => !empty($context['tours'])
            ]);

            // Generate AI response with full context
            $aiResponse = $this->geminiService->generateIntelligentResponse($message, $context);

            // Get relevant data based on the query type
            $responseData = $this->getRelevantData($message, $aiResponse['response_type']);

            $response = [
                'message' => $aiResponse['message'],
                'data' => $responseData,
                'suggestions' => $aiResponse['suggestions'],
                'ai_powered' => $aiResponse['ai_powered'] ?? true,
                'response_type' => $aiResponse['response_type'],
                'context_used' => !empty($context),
                'timestamp' => now()->toISOString()
            ];

            // Add debug info if fallback was used
            if (isset($aiResponse['fallback_used']) && $aiResponse['fallback_used']) {
                $response['fallback_used'] = true;
                Log::warning('Fallback response used for message: ' . $message);
            }

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Enhanced AI Processing Error: ' . $e->getMessage(), [
                'message' => $message,
                'trace' => $e->getTraceAsString()
            ]);

            // Final fallback
            return response()->json($this->getEmergencyFallbackResponse($message));
        }
    }

    /**
     * Emergency fallback when everything fails
     */
    private function getEmergencyFallbackResponse($message)
    {
        $message = strtolower(trim($message));

        // Handle basic math
        if (preg_match('/(\d+)\s*\+\s*(\d+)/', $message, $matches)) {
            $result = intval($matches[1]) + intval($matches[2]);
            return [
                'message' => "Kết quả: {$matches[1]} + {$matches[2]} = {$result} 😊\n\nTôi đã tính toán cục bộ vì AI đang bảo trì. Bạn có câu hỏi nào khác không?",
                'suggestions' => [
                    'Phép tính khác',
                    'Hỏi về du lịch',
                    'Thử lại sau',
                    'Liên hệ hỗ trợ'
                ],
                'data' => [],
                'ai_powered' => false,
                'emergency_fallback' => true
            ];
        }

        // Handle greetings
        if (preg_match('/\b(chào|hello|hi)\b/', $message)) {
            return [
                'message' => 'Xin chào! 👋 Tôi là TravelBot. AI đang tạm thời bảo trì, nhưng tôi vẫn có thể giúp bạn tìm tour du lịch và giải toán cơ bản!',
                'suggestions' => [
                    'Xem tour nổi bật',
                    'Tính toán đơn giản',
                    'Tìm tour theo địa điểm',
                    'Liên hệ hỗ trợ'
                ],
                'data' => $this->getFeaturedTours(),
                'ai_powered' => false,
                'emergency_fallback' => true
            ];
        }

        return [
            'message' => 'Xin lỗi, hệ thống AI đang tạm thời bảo trì. Tôi vẫn có thể giúp bạn với các câu hỏi cơ bản về du lịch và toán học đơn giản!',
            'suggestions' => [
                'Xem tour du lịch',
                'Phép tính cơ bản',
                'Thử lại sau',
                'Liên hệ hỗ trợ'
            ],
            'data' => $this->getFeaturedTours(),
            'ai_powered' => false,
            'emergency_fallback' => true
        ];
    }

    /**
     * Build comprehensive context for AI
     */
    private function buildComprehensiveContext($message)
    {
        $context = [
            'user_intent' => $this->analyzeUserIntent($message),
            'query_type' => $this->detectQueryType($message),
            'mentioned_entities' => $this->extractEntities($message),
            'tours' => [],
            'locations' => [],
            'current_season' => $this->getCurrentSeason(),
            'time_context' => now()->format('H:i')
        ];

        // Add travel-specific context if relevant
        if ($this->isTravelRelated($message)) {
            $context['tours'] = $this->getRelevantTours($message);
            $context['locations'] = Location::pluck('name')->toArray();
            $context['popular_destinations'] = $this->getPopularDestinations();
        }

        return $context;
    }

    /**
     * Analyze user intent from the message
     */
    private function analyzeUserIntent($message)
    {
        $message = strtolower($message);

        // Calculation intent
        if (preg_match('/[\d\+\-\*\/=]/', $message)) {
            return 'wants_calculation';
        }

        // Information seeking
        if (strpos($message, 'tại sao') !== false || strpos($message, 'như thế nào') !== false) {
            return 'seeks_explanation';
        }

        // Travel planning
        if (strpos($message, 'tour') !== false || strpos($message, 'du lịch') !== false) {
            return 'planning_travel';
        }

        // Greeting
        if (preg_match('/\b(chào|hello|hi|xin chào)\b/', $message)) {
            return 'greeting';
        }

        // Comparison
        if (strpos($message, 'so sánh') !== false || strpos($message, 'khác nhau') !== false) {
            return 'wants_comparison';
        }

        return 'general_inquiry';
    }

    /**
     * Detect query type for better response handling
     */
    private function detectQueryType($message)
    {
        $message = strtolower($message);

        $types = [
            'math' => ['toán', 'tính', '+', '-', '*', '/', '=', 'phương trình'],
            'travel' => ['tour', 'du lịch', 'điểm đến', 'khách sạn', 'vé máy bay'],
            'cooking' => ['nấu', 'món', 'công thức', 'nguyên liệu', 'chế biến'],
            'science' => ['khoa học', 'vật lý', 'hóa học', 'sinh học', 'địa lý'],
            'history' => ['lịch sử', 'năm', 'thế kỷ', 'chiến tranh', 'vua'],
            'general' => ['tại sao', 'như thế nào', 'là gì', 'có phải']
        ];

        foreach ($types as $type => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($message, $keyword) !== false) {
                    return $type;
                }
            }
        }

        return 'general';
    }

    /**
     * Extract entities (locations, numbers, etc.) from message
     */
    private function extractEntities($message)
    {
        $entities = [
            'numbers' => [],
            'locations' => [],
            'dates' => [],
            'prices' => []
        ];

        // Extract numbers
        preg_match_all('/\d+/', $message, $numbers);
        $entities['numbers'] = array_map('intval', $numbers[0]);

        // Extract locations
        try {
            $locations = Location::pluck('name')->toArray();
            foreach ($locations as $location) {
                if (stripos($message, $location) !== false) {
                    $entities['locations'][] = $location;
                }
            }
        } catch (\Exception $e) {
            Log::error('Error extracting locations: ' . $e->getMessage());
        }

        // Extract price mentions
        preg_match_all('/(\d+)\s*(triệu|tr|nghìn|k|đồng)/i', $message, $priceMatches);
        if (!empty($priceMatches[0])) {
            $entities['prices'] = $priceMatches[0];
        }

        return $entities;
    }

    /**
     * Get relevant data based on response type
     */
    private function getRelevantData($message, $responseType)
    {
        switch ($responseType) {
            case 'travel':
                return $this->getSmartTourRecommendations($message);

            case 'calculation':
                return []; // No additional data needed for math

            case 'greeting':
                return $this->getFeaturedTours();

            default:
                return $this->isTravelRelated($message) ?
                    $this->getSmartTourRecommendations($message) : [];
        }
    }

    /**
     * Check if message is travel-related
     */
    private function isTravelRelated($message)
    {
        $message = strtolower($message);

        $travelKeywords = [
            'tour',
            'du lịch',
            'travel',
            'điểm đến',
            'destination',
            'khách sạn',
            'hotel',
            'vé máy bay',
            'flight',
            'nghỉ dưỡng',
            'resort',
            'booking',
            'đặt phòng',
            'sapa',
            'hạ long',
            'đà nẵng',
            'nha trang',
            'phú quốc',
            'hội an',
            'vịnh',
            'núi',
            'biển',
            'thác',
            'chùa',
            'đền',
            'lịch trình',
            'itinerary',
            'guide',
            'hướng dẫn viên',
            'xe bus',
            'giá tour',
            'chi phí',
            'ngân sách'
        ];

        foreach ($travelKeywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get relevant tours with improved matching
     */
    private function getRelevantTours($message)
    {
        try {
            $message = strtolower($message);

            $query = Tour::with(['location', 'travelType', 'prices' => function ($q) {
                $q->orderBy('date', 'desc');
            }, 'images', 'reviews']);

            // Apply smart filters based on message content
            $locations = Location::all();
            $relevantLocations = $locations->filter(function ($location) use ($message) {
                return strpos($message, strtolower($location->name)) !== false;
            });

            if ($relevantLocations->isNotEmpty()) {
                $query->whereIn('location_id', $relevantLocations->pluck('id'));
            }

            // Price filtering
            preg_match_all('/(\d+)(?:\s*(?:triệu|tr|nghìn|k))?/i', $message, $priceMatches);
            if (!empty($priceMatches[1])) {
                $maxPrice = max(array_map('intval', $priceMatches[1]));
                if ($maxPrice < 100) $maxPrice *= 1000000; // Convert triệu to VND
                else if ($maxPrice < 10000) $maxPrice *= 1000; // Convert nghìn to VND

                $query->whereHas('prices', function ($q) use ($maxPrice) {
                    $q->where('price', '<=', $maxPrice);
                });
            }

            // Duration filtering
            preg_match('/(\d+)\s*ngày/i', $message, $dayMatches);
            if (!empty($dayMatches[1])) {
                $days = intval($dayMatches[1]);
                $query->whereBetween('days', [$days - 1, $days + 1]);
            }

            $tours = $query->limit(5)->get()->map(function ($tour) {
                return $this->formatTourData($tour);
            });

            return $tours->toArray();
        } catch (\Exception $e) {
            Log::error('Error getting relevant tours: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get smart tour recommendations
     */
    private function getSmartTourRecommendations($message)
    {
        $tours = $this->getRelevantTours($message);
        return !empty($tours) ? $tours : $this->getFeaturedTours();
    }

    /**
     * Format tour data for response
     */
    private function formatTourData($tour)
    {
        try {
            $latestPrice = $tour->prices->first();
            $avgRating = $tour->reviews->avg('rating') ?? 0;

            return [
                'id' => $tour->id,
                'name' => $tour->name,
                'location' => $tour->location->name ?? '',
                'category' => $tour->travelType->name ?? '',
                'duration' => "{$tour->days} ngày {$tour->nights} đêm",
                'price' => $latestPrice?->price ?? 0,
                'price_formatted' => number_format($latestPrice?->price ?? 0) . ' VNĐ',
                'image' => $tour->images()->where('is_primary', true)->first()?->image_url ??
                    $tour->images()->first()?->image_url ?? '',
                'rating' => round($avgRating, 1),
                'review_count' => $tour->reviews->count(),
                'highlights' => $this->extractTourHighlights($tour)
            ];
        } catch (\Exception $e) {
            Log::error('Error formatting tour data: ' . $e->getMessage());
            return [
                'id' => $tour->id ?? 0,
                'name' => $tour->name ?? 'Tour không xác định',
                'location' => '',
                'category' => '',
                'duration' => '0 ngày 0 đêm',
                'price' => 0,
                'price_formatted' => '0 VNĐ',
                'image' => '',
                'rating' => 0,
                'review_count' => 0
            ];
        }
    }

    /**
     * Extract tour highlights
     */
    private function extractTourHighlights($tour)
    {
        $highlights = [];

        $avgRating = $tour->reviews->avg('rating') ?? 0;
        if ($avgRating >= 4.5) {
            $highlights[] = "⭐ Đánh giá cao (" . round($avgRating, 1) . "/5)";
        }

        if ($tour->images->count() > 5) {
            $highlights[] = "📸 Nhiều hình ảnh đẹp";
        }

        $latestPrice = $tour->prices->first();
        if ($latestPrice && $latestPrice->price < 2000000) {
            $highlights[] = "💰 Giá tốt";
        }

        return $highlights;
    }

    /**
     * Get current season
     */
    private function getCurrentSeason()
    {
        $month = now()->month;
        if (in_array($month, [12, 1, 2])) return 'Mùa đông';
        if (in_array($month, [3, 4, 5])) return 'Mùa xuân';
        if (in_array($month, [6, 7, 8])) return 'Mùa hè';
        return 'Mùa thu';
    }

    /**
     * Get popular destinations
     */
    private function getPopularDestinations()
    {
        try {
            return Tour::select('location_id')
                ->whereHas('location')
                ->groupBy('location_id')
                ->orderByRaw('COUNT(*) DESC')
                ->limit(5)
                ->with('location:id,name')
                ->get()
                ->pluck('location.name')
                ->filter()
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Error getting popular destinations: ' . $e->getMessage());
            return ['Hà Nội', 'Sapa', 'Đà Nẵng', 'Hội An', 'Nha Trang'];
        }
    }

    /**
     * Get featured tours for fallback
     */
    private function getFeaturedTours()
    {
        try {
            return Tour::with(['location', 'images', 'travelType', 'prices' => function ($q) {
                $q->orderBy('date', 'desc');
            }, 'reviews'])
                ->limit(4)
                ->get()
                ->map(function ($tour) {
                    return $this->formatTourData($tour);
                })
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Error getting featured tours: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Intelligent fallback response
     */
    private function getIntelligentFallbackResponse($message)
    {
        $responseType = $this->detectQueryType($message);

        $fallbackMessages = [
            'math' => 'Xin lỗi, tôi gặp chút vấn đề kỹ thuật khi tính toán. Bạn có thể thử hỏi lại không?',
            'travel' => 'Tôi đang gặp chút vấn đề kỹ thuật, nhưng vẫn có thể giúp bạn tìm tour phù hợp!',
            'cooking' => 'Xin lỗi về sự cố kỹ thuật. Tôi vẫn có thể chia sẻ một số mẹo nấu ăn cơ bản!',
            'general' => 'Tôi đang gặp chút vấn đề kỹ thuật, nhưng vẫn sẵn sàng hỗ trợ bạn!'
        ];

        $suggestions = [
            'math' => ['Thử câu hỏi toán khác', 'Hỏi về du lịch', 'Câu hỏi tổng quát', 'Liên hệ hỗ trợ'],
            'travel' => ['Xem tour nổi bật', 'Tìm theo địa điểm', 'So sánh giá tour', 'Liên hệ tư vấn'],
            'cooking' => ['Công thức đơn giản', 'Mẹo nấu ăn', 'Hỏi về du lịch', 'Câu hỏi khác'],
            'general' => ['Hỏi về du lịch', 'Câu hỏi toán học', 'Kiến thức tổng quát', 'Liên hệ hỗ trợ']
        ];

        return [
            'message' => $fallbackMessages[$responseType] ?? $fallbackMessages['general'],
            'suggestions' => $suggestions[$responseType] ?? $suggestions['general'],
            'data' => $responseType === 'travel' ? $this->getFeaturedTours() : [],
            'context_type' => 'intelligent_fallback',
            'response_type' => $responseType
        ];
    }

    /**
     * Get detailed tour info (existing method)
     */
    public function getTourDetails($id)
    {
        try {
            $tour = Tour::with([
                'location',
                'travelType',
                'images',
                'availabilities' => function ($query) {
                    $query->where('date', '>=', now()->toDateString())
                        ->where('is_active', true)
                        ->orderBy('date', 'asc');
                },
                'features',
                'itineraries' => function ($query) {
                    $query->orderBy('day');
                },
                'reviews' => function ($query) {
                    $query->where('status', 'approved')->with('user:id,username');
                }
            ])->findOrFail($id);

            $response = [
                'tour' => $this->formatTourData($tour),
                'details' => [
                    'description' => $tour->description,
                    'features' => $tour->features->pluck('name'),
                    'next_departures' => $tour->availabilities->take(3)->map(function ($avail) {
                        return [
                            'date' => $avail->date,
                            'available_slots' => $avail->available_slots,
                            'max_guests' => $avail->max_guests
                        ];
                    }),
                    'itinerary_preview' => $tour->itineraries->take(3)->map(function ($day) {
                        return [
                            'day' => $day->day,
                            'title' => $day->title,
                            'description' => Str::limit($day->description ?? '', 100)
                        ];
                    }),
                    'recent_reviews' => $tour->reviews->take(2)->map(function ($review) {
                        return [
                            'rating' => $review->rating,
                            'comment' => Str::limit($review->comment ?? '', 100),
                            'user' => $review->user->username ?? 'Khách hàng'
                        ];
                    })
                ]
            ];

            return response()->json([
                'message' => 'Đây là thông tin chi tiết về tour:',
                'data' => $response,
                'suggestions' => [
                    'Xem lịch trình đầy đủ',
                    'Kiểm tra ngày khởi hành',
                    'Đọc đánh giá',
                    'Đặt tour ngay'
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting tour details: ' . $e->getMessage());
            return response()->json([
                'message' => 'Không tìm thấy thông tin tour này.',
                'suggestions' => [
                    'Tìm tour khác',
                    'Xem tour nổi bật',
                    'Liên hệ hỗ trợ'
                ]
            ], 404);
        }
    }
}
