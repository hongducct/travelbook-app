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
     * Process ANY chatbot queries - not just travel related
     */
    public function processQuery(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'conversation_id' => 'nullable|string'
        ]);

        $message = trim($request->message);
        $conversationId = $request->conversation_id;

        Log::info('ChatBot Query', [
            'message' => $message,
            'conversation_id' => $conversationId
        ]);

        try {
            // Step 1: Detect if this is travel-related
            $isTravelRelated = $this->isTravelRelated($message);

            if ($isTravelRelated) {
                Log::info('Processing as travel query');
                return $this->processTravelQuery($message, $conversationId);
            } else {
                Log::info('Processing as general query');
                return $this->processGeneralQuery($message, $conversationId);
            }
        } catch (\Exception $e) {
            Log::error('ChatBot Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json($this->getUniversalFallbackResponse($message));
        }
    }
    /**
     * Detect if message is travel-related
     */
    private function isTravelRelated($message)
    {
        $message = strtolower($message);

        // Travel keywords
        $travelKeywords = [
            // Vietnamese
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
            'lịch sử',
            'văn hóa',
            'lịch trình',
            'itinerary',
            'guide',
            'hướng dẫn viên',
            'xe bus',
            'giá tour',
            'chi phí',
            'ngân sách',
            'budget',
            'khuyến mãi',
            'discount',
            // English  
            'vacation',
            'holiday',
            'trip',
            'journey',
            'sightseeing',
            'backpack',
            'hostel',
            'airbnb',
            'cruise',
            'beach',
            'mountain',
            'city tour'
        ];

        foreach ($travelKeywords as $keyword) {
            if (Str::contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Process travel-related queries (your existing logic)
     */
    private function processTravelQuery($message, $conversationId)
    {
        // Step 1: Try rule-based response first (faster, cheaper)
        if (!$this->geminiService->shouldUseAI($message)) {
            $ruleBasedResponse = $this->tryRuleBasedResponse($message);
            if ($ruleBasedResponse) {
                Log::info('Used rule-based travel response');
                return response()->json($ruleBasedResponse);
            }
        }

        // Step 2: Use AI with travel context
        Log::info('Using AI for travel query');
        $tourContext = $this->getComprehensiveTourData($message);
        $aiResponse = $this->geminiService->generateChatResponse($message, $tourContext);
        $tourData = $this->getSmartTourRecommendations($message);

        return response()->json([
            'message' => $aiResponse['message'],
            'data' => $tourData,
            'suggestions' => $aiResponse['suggestions'] ?? $this->getTravelSuggestions(),
            'ai_powered' => true,
            'context_type' => 'travel',
            'context_provided' => !empty($tourContext['tours'])
        ]);
    }

    /**
     * Process general queries (math, science, etc.)
     */
    private function processGeneralQuery($message, $conversationId)
    {
        try {
            // For general queries, use AI without travel context
            $generalContext = $this->getGeneralContext($message);
            $aiResponse = $this->geminiService->generateGeneralResponse($message, $generalContext);

            return response()->json([
                'message' => $aiResponse['message'] ?? $aiResponse,
                'suggestions' => $aiResponse['suggestions'] ?? $this->getGeneralSuggestions($message),
                'ai_powered' => true,
                'context_type' => 'general',
                'data' => [] // No tour data for general queries
            ]);
        } catch (\Exception $e) {
            Log::error('General query processing error: ' . $e->getMessage());

            // Fallback for general queries
            return response()->json([
                'message' => $this->getSimpleAIResponse($message),
                'suggestions' => $this->getGeneralSuggestions($message),
                'ai_powered' => false,
                'context_type' => 'general_fallback'
            ]);
        }
    }

    /**
     * Get context for general queries
     */
    private function getGeneralContext($message)
    {
        $message = strtolower($message);
        $context = [
            'query_type' => 'general',
            'language' => 'vietnamese', // Detect language if needed
            'topics' => []
        ];

        // Detect topic categories
        if (preg_match('/\b(\d+|\+|\-|\*|\/|=|toán|math|tính|calculate)\b/', $message)) {
            $context['topics'][] = 'mathematics';
        }

        if (Str::contains($message, ['khoa học', 'science', 'vật lý', 'physics', 'hóa học', 'chemistry'])) {
            $context['topics'][] = 'science';
        }

        if (Str::contains($message, ['lịch sử', 'history', 'năm', 'thế kỷ', 'chiến tranh'])) {
            $context['topics'][] = 'history';
        }

        if (Str::contains($message, ['nấu ăn', 'cooking', 'món ăn', 'recipe', 'công thức'])) {
            $context['topics'][] = 'cooking';
        }

        return $context;
    }

    /**
     * Get simple AI response for fallback
     */
    private function getSimpleAIResponse($message)
    {
        $message = strtolower($message);

        // Math detection
        if (preg_match('/(\d+)\s*[\+\-\*\/]\s*(\d+)/', $message, $matches)) {
            return "Tôi thấy bạn hỏi về toán. Để tính toán chính xác, bạn có thể sử dụng máy tính hoặc cho tôi biết phép tính cụ thể hơn.";
        }

        // Science questions
        if (Str::contains($message, ['tại sao', 'why', 'như thế nào', 'how'])) {
            return "Đây là câu hỏi thú vị! Tôi sẽ cố gắng trả lời dựa trên kiến thức của mình. Bạn có thể hỏi cụ thể hơn không?";
        }

        // General response
        return "Tôi hiểu câu hỏi của bạn. Mặc dù tôi chuyên về du lịch, tôi cũng có thể thảo luận về nhiều chủ đề khác. Bạn có thể hỏi cụ thể hơn không?";
    }

    /**
     * Get suggestions for general queries
     */
    private function getGeneralSuggestions($message)
    {
        $message = strtolower($message);

        if (Str::contains($message, ['toán', 'math', 'tính'])) {
            return [
                'Giải phương trình',
                'Tính toán cơ bản',
                'Giải bài tập',
                'Công thức toán học'
            ];
        }

        if (Str::contains($message, ['nấu ăn', 'cooking', 'món'])) {
            return [
                'Công thức nấu ăn',
                'Cách chế biến',
                'Nguyên liệu cần thiết',
                'Mẹo nấu ăn'
            ];
        }

        if (Str::contains($message, ['lịch sử', 'history'])) {
            return [
                'Sự kiện lịch sử',
                'Nhân vật nổi tiếng',
                'Niên đại',
                'Văn hóa'
            ];
        }

        return [
            'Hỏi về kiến thức tổng quát',
            'Giải thích khái niệm',
            'Tư vấn học tập',
            'Quay lại chủ đề du lịch'
        ];
    }

    /**
     * Get travel-specific suggestions
     */
    private function getTravelSuggestions()
    {
        return [
            'Tìm tour theo địa điểm',
            'So sánh giá tour',
            'Xem lịch khởi hành',
            'Tư vấn tour phù hợp'
        ];
    }

    /**
     * Universal fallback response
     */
    private function getUniversalFallbackResponse($message)
    {
        return [
            'message' => 'Xin lỗi, tôi đang gặp chút vấn đề kỹ thuật. Tuy nhiên, tôi vẫn có thể hỗ trợ bạn về nhiều chủ đề khác nhau!',
            'suggestions' => [
                'Hỏi về du lịch',
                'Câu hỏi tổng quát',
                'Toán học cơ bản',
                'Liên hệ hỗ trợ'
            ],
            'data' => [],
            'context_type' => 'error_fallback'
        ];
    }
    /**
     * Try rule-based response for simple queries
     */
    private function tryRuleBasedResponse($message)
    {
        $message = strtolower(trim($message));

        // Greeting
        if ($this->matchesPattern($message, ['xin chào', 'hello', 'hi', 'chào', 'hey'])) {
            return [
                'message' => 'Xin chào! Tôi là trợ lý du lịch AI thông minh. Tôi có thể giúp bạn:
• Tìm tour theo địa điểm, giá cả, thời gian
• So sánh và tư vấn tour phù hợp
• Kiểm tra lịch trình, ngày khởi hành
• Trả lời mọi câu hỏi về du lịch

Hãy hỏi tôi bất cứ điều gì nhé! 😊',
                'suggestions' => [
                    'Tìm tour Hà Nội giá rẻ',
                    'Tour Sapa 3 ngày 2 đêm',
                    'So sánh tour Đà Nẵng',
                    'Tour nào đang hot?'
                ],
                'data' => $this->getFeaturedTours()
            ];
        }

        // Thank you
        if ($this->matchesPattern($message, ['cảm ơn', 'thanks', 'thank you', 'cám ơn'])) {
            return [
                'message' => 'Rất vui được hỗ trợ bạn! Nếu có thêm câu hỏi gì về du lịch, đừng ngần ngại hỏi tôi nhé. Chúc bạn có chuyến đi tuyệt vời! 🌟',
                'suggestions' => [
                    'Tìm tour khác',
                    'Kiểm tra giá tour',
                    'Xem lịch khởi hành',
                    'Liên hệ tư vấn'
                ]
            ];
        }

        // Specific price queries with clear numbers
        if (preg_match('/(?:giá|chi phí|price).*?(\d+).*?(triệu|tr|nghìn|k|đồng)/i', $message, $matches)) {
            return $this->handlePriceInquiry($message);
        }

        // Specific location queries
        try {
            $locations = Location::pluck('name')->toArray();
            foreach ($locations as $location) {
                if (Str::contains($message, strtolower($location))) {
                    return $this->handleLocationSearch($message);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in location search: ' . $e->getMessage());
        }

        return null; // Let AI handle complex queries
    }

    /**
     * Process query with AI
     */
    private function processWithAI($message, $conversationId = null)
    {
        try {
            // Get comprehensive tour data for context
            $tourContext = $this->getComprehensiveTourData($message);

            // Generate AI response with enhanced context
            $aiResponse = $this->geminiService->generateChatResponse($message, $tourContext);

            // Get relevant tour data for response
            $tourData = $this->getSmartTourRecommendations($message);

            return [
                'message' => $aiResponse['message'],
                'data' => $tourData,
                'suggestions' => $aiResponse['suggestions'] ?? $this->getDefaultSuggestions(),
                'ai_powered' => true,
                'context_provided' => !empty($tourContext['tours'])
            ];
        } catch (\Exception $e) {
            Log::error('AI Processing Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get comprehensive tour data for AI context
     */
    private function getComprehensiveTourData($message)
    {
        try {
            $message = strtolower($message);
            Log::info('Processing message for context', ['message' => $message]);

            // Extract locations mentioned
            $locations = Location::all();
            $relevantLocations = $locations->filter(function ($location) use ($message) {
                return Str::contains($message, strtolower($location->name));
            });

            // Extract price range with better parsing
            preg_match_all('/(\d+)(?:\s*(?:triệu|tr|nghìn|k|đồng|vnd|million|thousand))?/i', $message, $priceMatches);
            $mentionedPrices = array_map('intval', $priceMatches[1]);

            // Extract duration with better parsing
            preg_match('/(\d+)\s*(?:ngày|day)/i', $message, $dayMatches);
            preg_match('/(\d+)\s*(?:đêm|night)/i', $message, $nightMatches);
            $days = isset($dayMatches[1]) ? intval($dayMatches[1]) : null;
            $nights = isset($nightMatches[1]) ? intval($nightMatches[1]) : null;

            // Detect travel preferences
            $preferences = $this->detectTravelPreferences($message);

            // Build comprehensive context query
            $query = Tour::with(['location', 'travelType', 'prices' => function ($q) {
                $q->orderBy('date', 'desc');
            }, 'images', 'reviews']);

            // Apply smart filters
            if ($relevantLocations->isNotEmpty()) {
                $query->whereIn('location_id', $relevantLocations->pluck('id'));
            }

            if (!empty($mentionedPrices)) {
                $maxPrice = max($mentionedPrices);
                // Smart price conversion
                if ($maxPrice < 100) $maxPrice *= 1000000; // Convert triệu to VND
                else if ($maxPrice < 10000) $maxPrice *= 1000; // Convert nghìn to VND

                $query->whereHas('prices', function ($q) use ($maxPrice) {
                    $q->where('price', '<=', $maxPrice);
                });
            }

            if ($days) {
                $query->where('days', '>=', $days - 1)->where('days', '<=', $days + 1);
            }
            if ($nights) {
                $query->where('nights', '>=', $nights - 1)->where('nights', '<=', $nights + 1);
            }

            // Add preference-based filtering
            if (!empty($preferences['travel_types'])) {
                $query->whereHas('travelType', function ($q) use ($preferences) {
                    $q->whereIn('name', $preferences['travel_types']);
                });
            }

            $tours = $query->limit(10)->get()->map(function ($tour) {
                $latestPrice = $tour->prices->first();
                return [
                    'id' => $tour->id,
                    'name' => $tour->name,
                    'location' => $tour->location->name ?? '',
                    'category' => $tour->travelType->name ?? '',
                    'duration' => "{$tour->days} ngày {$tour->nights} đêm",
                    'price' => $latestPrice?->price ?? 0,
                    'price_formatted' => number_format($latestPrice?->price ?? 0) . ' VNĐ',
                    'description' => Str::limit($tour->description ?? '', 200),
                    'rating' => round($tour->reviews->avg('rating') ?? 0, 1),
                    'review_count' => $tour->reviews->count(),
                    'image_count' => $tour->images->count(),
                    'highlights' => $this->extractTourHighlights($tour)
                ];
            });

            return [
                'tours' => $tours->toArray(),
                'locations' => $locations->pluck('name')->toArray(),
                'price_ranges' => $this->getPriceRanges(),
                'travel_types' => TravelType::pluck('name')->toArray(),
                'query_context' => [
                    'mentioned_locations' => $relevantLocations->pluck('name')->toArray(),
                    'mentioned_prices' => $mentionedPrices,
                    'duration' => compact('days', 'nights'),
                    'preferences' => $preferences,
                    'season' => $this->getCurrentSeason(),
                    'popular_destinations' => $this->getPopularDestinations()
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Error getting comprehensive tour data: ' . $e->getMessage());
            return [
                'tours' => [],
                'locations' => [],
                'price_ranges' => [],
                'travel_types' => [],
                'query_context' => []
            ];
        }
    }

    /**
     * Get smart tour recommendations
     */
    private function getSmartTourRecommendations($message)
    {
        try {
            $message = strtolower($message);

            $query = Tour::with(['location', 'images', 'travelType', 'prices' => function ($q) {
                $q->orderBy('date', 'desc');
            }, 'reviews']);

            // Simple relevance scoring
            $tours = $query->get()->map(function ($tour) use ($message) {
                $score = 0;

                // Score by name match
                if ($tour->name) {
                    similar_text(strtolower($tour->name), $message, $nameScore);
                    $score += $nameScore * 0.1;
                }

                // Score by location match
                if ($tour->location && Str::contains($message, strtolower($tour->location->name))) {
                    $score += 10;
                }

                // Score by category match
                if ($tour->travelType && Str::contains($message, strtolower($tour->travelType->name))) {
                    $score += 5;
                }

                // Score by description keywords
                $keywords = ['biển', 'núi', 'thành phố', 'văn hóa', 'lịch sử', 'thiên nhiên', 'nghỉ dưỡng'];
                foreach ($keywords as $keyword) {
                    if (
                        Str::contains($message, $keyword) &&
                        Str::contains(strtolower($tour->description ?? ''), $keyword)
                    ) {
                        $score += 2;
                    }
                }

                return [
                    'tour' => $this->formatTourData($tour),
                    'score' => $score
                ];
            })->filter(function ($item) {
                return $item['score'] > 3; // Only return tours with decent relevance
            })->sortByDesc('score')->take(5)->pluck('tour');

            return $tours->isEmpty() ? $this->getFeaturedTours() : $tours->toArray();
        } catch (\Exception $e) {
            Log::error('Error getting smart tour recommendations: ' . $e->getMessage());
            return $this->getFeaturedTours();
        }
    }

    /**
     * Detect travel preferences from message
     */
    private function detectTravelPreferences($message)
    {
        $preferences = [
            'travel_types' => [],
            'activities' => [],
            'budget_level' => null,
            'group_type' => null
        ];

        // Travel type detection
        $travelTypeMap = [
            'biển' => 'Du lịch biển',
            'núi' => 'Du lịch núi',
            'thành phố' => 'Du lịch thành phố',
            'văn hóa' => 'Du lịch văn hóa',
            'lịch sử' => 'Du lịch lịch sử',
            'thiên nhiên' => 'Du lịch sinh thái',
            'nghỉ dưỡng' => 'Nghỉ dưỡng',
            'phiêu lưu' => 'Du lịch phiêu lưu'
        ];

        foreach ($travelTypeMap as $keyword => $type) {
            if (Str::contains($message, $keyword)) {
                $preferences['travel_types'][] = $type;
            }
        }

        // Budget level detection
        if (preg_match('/\b(rẻ|tiết kiệm|budget|giá tốt)\b/i', $message)) {
            $preferences['budget_level'] = 'budget';
        } elseif (preg_match('/\b(cao cấp|luxury|sang trọng|vip)\b/i', $message)) {
            $preferences['budget_level'] = 'luxury';
        }

        // Group type detection
        if (preg_match('/\b(gia đình|family|trẻ em)\b/i', $message)) {
            $preferences['group_type'] = 'family';
        } elseif (preg_match('/\b(cặp đôi|couple|honeymoon|tình yêu)\b/i', $message)) {
            $preferences['group_type'] = 'couple';
        } elseif (preg_match('/\b(bạn bè|friends|nhóm)\b/i', $message)) {
            $preferences['group_type'] = 'friends';
        }

        return $preferences;
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
     * Handle price inquiry
     */
    private function handlePriceInquiry($message)
    {
        try {
            preg_match_all('/\d+/', $message, $numbers);
            $numbers = array_map('intval', $numbers[0]);

            if (!empty($numbers)) {
                $maxPrice = max($numbers) * (Str::contains($message, ['triệu', 'tr']) ? 1000000 : 1000);

                $tours = Tour::with(['location', 'images', 'travelType', 'prices' => function ($q) {
                    $q->orderBy('date', 'desc');
                }])
                    ->whereHas('prices', function ($query) use ($maxPrice) {
                        $query->where('price', '<=', $maxPrice);
                    })
                    ->limit(5)
                    ->get()
                    ->map(function ($tour) {
                        return $this->formatTourData($tour);
                    });

                if ($tours->count() > 0) {
                    return [
                        'message' => "Tôi tìm thấy {$tours->count()} tour phù hợp với ngân sách của bạn:",
                        'data' => $tours->toArray(),
                        'suggestions' => [
                            'Xem chi tiết tour',
                            'Tìm tour khác',
                            'So sánh giá',
                            'Kiểm tra lịch khởi hành'
                        ]
                    ];
                }
            }

            $priceRanges = $this->getPriceRanges();
            return [
                'message' => 'Đây là thông tin về các mức giá tour hiện có:',
                'data' => $priceRanges,
                'suggestions' => [
                    'Tour dưới 1 triệu',
                    'Tour từ 1-3 triệu',
                    'Tour cao cấp trên 5 triệu'
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Error handling price inquiry: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Handle location search
     */
    private function handleLocationSearch($message)
    {
        try {
            $locations = Location::all();
            $foundLocation = null;

            foreach ($locations as $location) {
                if (Str::contains($message, strtolower($location->name))) {
                    $foundLocation = $location;
                    break;
                }
            }

            if ($foundLocation) {
                $tours = Tour::with(['location', 'images', 'travelType', 'prices' => function ($q) {
                    $q->orderBy('date', 'desc');
                }])
                    ->where('location_id', $foundLocation->id)
                    ->limit(5)
                    ->get()
                    ->map(function ($tour) {
                        return $this->formatTourData($tour);
                    });

                return [
                    'message' => "Tôi tìm thấy {$tours->count()} tour tại {$foundLocation->name}:",
                    'data' => $tours->toArray(),
                    'suggestions' => [
                        'Xem thêm tour ' . $foundLocation->name,
                        'So sánh giá tour',
                        'Kiểm tra lịch khởi hành',
                        'Tư vấn tour phù hợp'
                    ]
                ];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error handling location search: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Format tour data
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
                'review_count' => $tour->reviews->count()
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
     * Get price ranges
     */
    private function getPriceRanges()
    {
        try {
            $ranges = [
                ['range' => 'Dưới 1 triệu', 'min' => 0, 'max' => 1000000],
                ['range' => '1 - 3 triệu', 'min' => 1000000, 'max' => 3000000],
                ['range' => '3 - 5 triệu', 'min' => 3000000, 'max' => 5000000],
                ['range' => 'Trên 5 triệu', 'min' => 5000000, 'max' => PHP_INT_MAX]
            ];

            foreach ($ranges as &$range) {
                $count = Tour::whereHas('prices', function ($query) use ($range) {
                    $query->whereBetween('price', [$range['min'], $range['max']]);
                })->count();
                $range['count'] = $count;
            }

            return $ranges;
        } catch (\Exception $e) {
            Log::error('Error getting price ranges: ' . $e->getMessage());
            return [];
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
     * Get fallback response
     */
    private function getFallbackResponse($message = '')
    {
        $suggestions = [
            'Tìm tour theo địa điểm',
            'Xem tour giá tốt',
            'Tour 3-4 ngày',
            'Liên hệ tư vấn viên'
        ];

        // Try to provide relevant suggestions based on message
        if (!empty($message)) {
            $message = strtolower($message);
            if (Str::contains($message, ['giá', 'price', 'rẻ'])) {
                $suggestions = ['Tour giá rẻ', 'So sánh giá tour', 'Khuyến mãi tour', 'Tour tiết kiệm'];
            } elseif (Str::contains($message, ['sapa', 'hạ long', 'đà nẵng', 'nha trang'])) {
                $suggestions = ['Tour Sapa', 'Tour Hạ Long', 'Tour Đà Nẵng', 'Tour Nha Trang'];
            }
        }

        return [
            'message' => 'Xin lỗi, tôi đang gặp chút vấn đề kỹ thuật. Tuy nhiên, tôi vẫn sẵn sàng hỗ trợ bạn! Hãy thử hỏi cụ thể về tour bạn quan tâm nhé.',
            'suggestions' => $suggestions,
            'data' => $this->getFeaturedTours()
        ];
    }

    /**
     * Get default suggestions
     */
    private function getDefaultSuggestions()
    {
        return [
            'Xem tour nổi bật',
            'Tư vấn tour phù hợp',
            'Kiểm tra lịch khởi hành',
            'Liên hệ đặt tour'
        ];
    }

    /**
     * Match patterns helper
     */
    private function matchesPattern($message, $patterns)
    {
        foreach ($patterns as $pattern) {
            if (Str::contains($message, $pattern)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get detailed tour info
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
