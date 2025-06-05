<?php

namespace App\Services;

use App\Models\Tour;
use App\Models\Location;
use App\Models\TravelType;
use App\Models\Feature;
use App\Models\TourAvailability;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GeminiService
{
    private $apiKey;
    private $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-pro-preview-05-06:generateContent';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key') ?? env('GEMINI_API_KEY');
    }

    /**
     * Generate intelligent response for any type of query
     */
    public function generateIntelligentResponse($message, $context = [])
    {
        try {
            // Enhance context with tour system knowledge
            $enhancedContext = $this->enhanceContextWithTourSystem($message, $context);

            // Build prompt with enhanced context
            $prompt = $this->buildIntelligentPrompt($message, $enhancedContext);

            // Call Gemini API
            $response = $this->callGeminiAPI($prompt);

            return [
                'message' => $response['message'],
                'suggestions' => $this->generateContextualSuggestions($message, $enhancedContext),
                'response_type' => $this->detectResponseType($message),
                'ai_powered' => true,
                'model_used' => 'gemini-2.5-pro-preview-05-06',
                'enhanced_context' => !empty($enhancedContext['tour_system_data'])
            ];
        } catch (\Exception $e) {
            Log::error('AI response error, using fallback: ' . $e->getMessage());

            // Use fallback response when AI fails
            $fallback = $this->generateFallbackResponse($message, $context);
            return [
                'message' => $fallback['message'],
                'suggestions' => $fallback['suggestions'],
                'response_type' => $this->detectResponseType($message),
                'ai_powered' => false,
                'fallback_used' => true
            ];
        }
    }

    /**
     * Enhance context with tour system knowledge
     */
    private function enhanceContextWithTourSystem($message, $context)
    {
        $enhancedContext = $context;
        $enhancedContext['tour_system_data'] = [];

        try {
            // Extract entities from message
            $entities = $this->extractEntities($message);
            $enhancedContext['entities'] = $entities;

            // Add tour system data based on message content
            if ($this->isTravelRelated($message)) {
                // Get relevant tours with detailed information
                $tours = $this->getDetailedTourData($message, $entities);
                if (!empty($tours)) {
                    $enhancedContext['tour_system_data']['tours'] = $tours;
                }

                // Get location information if locations are mentioned
                if (!empty($entities['locations'])) {
                    $locationData = $this->getLocationData($entities['locations']);
                    if (!empty($locationData)) {
                        $enhancedContext['tour_system_data']['locations'] = $locationData;
                    }
                }

                // Get travel type information if travel types are mentioned
                if (!empty($entities['travel_types'])) {
                    $travelTypeData = $this->getTravelTypeData($entities['travel_types']);
                    if (!empty($travelTypeData)) {
                        $enhancedContext['tour_system_data']['travel_types'] = $travelTypeData;
                    }
                }

                // Get feature information if features are mentioned
                if (!empty($entities['features'])) {
                    $featureData = $this->getFeatureData($entities['features']);
                    if (!empty($featureData)) {
                        $enhancedContext['tour_system_data']['features'] = $featureData;
                    }
                }

                // Get availability information if dates are mentioned
                if (!empty($entities['dates'])) {
                    $availabilityData = $this->getAvailabilityData($entities['dates']);
                    if (!empty($availabilityData)) {
                        $enhancedContext['tour_system_data']['availabilities'] = $availabilityData;
                    }
                }

                // Add statistics about tours
                $enhancedContext['tour_system_data']['statistics'] = $this->getTourStatistics();
            }

            Log::info('Enhanced context created', [
                'has_tours' => !empty($enhancedContext['tour_system_data']['tours']),
                'has_locations' => !empty($enhancedContext['tour_system_data']['locations']),
                'has_travel_types' => !empty($enhancedContext['tour_system_data']['travel_types']),
                'has_features' => !empty($enhancedContext['tour_system_data']['features']),
                'has_availabilities' => !empty($enhancedContext['tour_system_data']['availabilities']),
                'has_statistics' => !empty($enhancedContext['tour_system_data']['statistics'])
            ]);
        } catch (\Exception $e) {
            Log::error('Error enhancing context: ' . $e->getMessage());
        }

        return $enhancedContext;
    }

    /**
     * Extract entities from message with improved detection
     */
    private function extractEntities($message)
    {
        $entities = [
            'locations' => [],
            'travel_types' => [],
            'features' => [],
            'dates' => [],
            'price_ranges' => [],
            'durations' => [],
            'numbers' => []
        ];

        $message = mb_strtolower($message);

        // Extract locations
        try {
            $locations = Location::select('id', 'name', 'city', 'country')->get();
            foreach ($locations as $location) {
                if (Str::contains($message, mb_strtolower($location->name))) {
                    $entities['locations'][] = [
                        'id' => $location->id,
                        'name' => $location->name,
                        'city' => $location->city,
                        'country' => $location->country
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('Error extracting locations: ' . $e->getMessage());
        }

        // Extract travel types
        try {
            $travelTypes = TravelType::select('id', 'name')->get();
            foreach ($travelTypes as $travelType) {
                if (Str::contains($message, mb_strtolower($travelType->name))) {
                    $entities['travel_types'][] = [
                        'id' => $travelType->id,
                        'name' => $travelType->name
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('Error extracting travel types: ' . $e->getMessage());
        }

        // Extract features
        try {
            $features = Feature::select('id', 'name')->where('is_active', true)->get();
            foreach ($features as $feature) {
                if (Str::contains($message, mb_strtolower($feature->name))) {
                    $entities['features'][] = [
                        'id' => $feature->id,
                        'name' => $feature->name
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('Error extracting features: ' . $e->getMessage());
        }

        // Extract dates (today, tomorrow, next week, specific dates)
        if (preg_match('/\b(hôm nay|ngày mai|tuần sau|tuần tới|cuối tuần|cuối tháng)\b/', $message)) {
            if (Str::contains($message, 'hôm nay')) {
                $entities['dates'][] = now()->toDateString();
            }
            if (Str::contains($message, 'ngày mai')) {
                $entities['dates'][] = now()->addDay()->toDateString();
            }
            if (Str::contains($message, ['tuần sau', 'tuần tới'])) {
                $entities['dates'][] = now()->addWeek()->toDateString();
            }
            if (Str::contains($message, 'cuối tuần')) {
                $entities['dates'][] = now()->endOfWeek()->toDateString();
            }
            if (Str::contains($message, 'cuối tháng')) {
                $entities['dates'][] = now()->endOfMonth()->toDateString();
            }
        }

        // Extract specific dates (dd/mm/yyyy or dd-mm-yyyy)
        preg_match_all('/\b(\d{1,2})[\/\-](\d{1,2})(?:[\/\-](\d{2,4}))?\b/', $message, $dateMatches);
        if (!empty($dateMatches[0])) {
            foreach ($dateMatches[0] as $index => $match) {
                $day = $dateMatches[1][$index];
                $month = $dateMatches[2][$index];
                $year = !empty($dateMatches[3][$index]) ? $dateMatches[3][$index] : now()->year;

                // Handle 2-digit year
                if (strlen($year) == 2) {
                    $year = '20' . $year;
                }

                try {
                    $date = \Carbon\Carbon::createFromDate($year, $month, $day)->toDateString();
                    $entities['dates'][] = $date;
                } catch (\Exception $e) {
                    // Invalid date, ignore
                }
            }
        }

        // Extract price ranges
        preg_match_all('/(\d+)\s*(triệu|tr|nghìn|k|đồng|vnd)/i', $message, $priceMatches);
        if (!empty($priceMatches[0])) {
            foreach ($priceMatches[0] as $index => $match) {
                $amount = (int)$priceMatches[1][$index];
                $unit = mb_strtolower($priceMatches[2][$index]);

                // Convert to VND
                if (in_array($unit, ['triệu', 'tr'])) {
                    $amount *= 1000000;
                } elseif (in_array($unit, ['nghìn', 'k'])) {
                    $amount *= 1000;
                }

                $entities['price_ranges'][] = $amount;
            }
        }

        // Extract durations (days, nights)
        preg_match_all('/(\d+)\s*(ngày|đêm|day|night)/i', $message, $durationMatches);
        if (!empty($durationMatches[0])) {
            foreach ($durationMatches[0] as $index => $match) {
                $number = (int)$durationMatches[1][$index];
                $unit = mb_strtolower($durationMatches[2][$index]);

                if (in_array($unit, ['ngày', 'day'])) {
                    $entities['durations']['days'] = $number;
                } elseif (in_array($unit, ['đêm', 'night'])) {
                    $entities['durations']['nights'] = $number;
                }
            }
        }

        // Extract numbers
        preg_match_all('/\b\d+\b/', $message, $numberMatches);
        if (!empty($numberMatches[0])) {
            $entities['numbers'] = array_map('intval', $numberMatches[0]);
        }

        return $entities;
    }

    /**
     * Get detailed tour data based on message and entities
     */
    private function getDetailedTourData($message, $entities)
    {
        try {
            $query = Tour::with([
                'location:id,name,city,country',
                'travelType:id,name',
                'features:id,name',
                'images' => function ($q) {
                    $q->where('is_primary', true)->select('id', 'tour_id', 'image_url');
                },
                'prices' => function ($q) {
                    $q->orderBy('date', 'desc')->limit(1);
                },
                'availabilities' => function ($q) {
                    $q->where('date', '>=', now()->toDateString())
                        ->where('is_active', true)
                        ->orderBy('date', 'asc')
                        ->limit(3);
                },
                'itineraries' => function ($q) {
                    $q->orderBy('day')->select('id', 'tour_id', 'day', 'title', 'description');
                },
                'reviews' => function ($q) {
                    $q->where('status', 'approved')->limit(3);
                }
            ]);

            // Apply filters based on entities
            if (!empty($entities['locations'])) {
                $locationIds = array_column($entities['locations'], 'id');
                $query->whereIn('location_id', $locationIds);
            }

            if (!empty($entities['travel_types'])) {
                $travelTypeIds = array_column($entities['travel_types'], 'id');
                $query->whereIn('travel_type_id', $travelTypeIds);
            }

            if (!empty($entities['features'])) {
                $featureIds = array_column($entities['features'], 'id');
                $query->whereHas('features', function ($q) use ($featureIds) {
                    $q->whereIn('features.id', $featureIds);
                });
            }

            if (!empty($entities['price_ranges'])) {
                $maxPrice = max($entities['price_ranges']);
                $query->whereHas('prices', function ($q) use ($maxPrice) {
                    $q->where('price', '<=', $maxPrice);
                });
            }

            if (!empty($entities['durations']['days'])) {
                $days = $entities['durations']['days'];
                $query->where('days', '>=', $days - 1)
                    ->where('days', '<=', $days + 1);
            }

            if (!empty($entities['dates'])) {
                $dates = $entities['dates'];
                $query->whereHas('availabilities', function ($q) use ($dates) {
                    $q->whereIn('date', $dates)
                        ->where('is_active', true)
                        ->where('available_slots', '>', 0);
                });
            }

            // If no specific filters, get popular tours
            if (
                empty($entities['locations']) &&
                empty($entities['travel_types']) &&
                empty($entities['features']) &&
                empty($entities['price_ranges']) &&
                empty($entities['durations']) &&
                empty($entities['dates'])
            ) {

                if (Str::contains($message, ['phổ biến', 'nổi bật', 'hot', 'popular'])) {
                    $query->withCount('reviews')
                        ->orderBy('reviews_count', 'desc');
                } else {
                    $query->latest();
                }
            }

            // Get tours with limit
            $tours = $query->limit(5)->get();

            // Transform tours to include only necessary data
            return $tours->map(function ($tour) {
                $latestPrice = $tour->prices->first();
                $avgRating = $tour->reviews->avg('rating') ?? 0;

                $tourData = [
                    'id' => $tour->id,
                    'name' => $tour->name,
                    'description' => Str::limit($tour->description, 100),
                    'location' => $tour->location ? [
                        'name' => $tour->location->name,
                        'city' => $tour->location->city,
                        'country' => $tour->location->country
                    ] : null,
                    'travel_type' => $tour->travelType ? $tour->travelType->name : null,
                    'duration' => "{$tour->days} ngày {$tour->nights} đêm",
                    'price' => $latestPrice ? $latestPrice->price : null,
                    'price_formatted' => $latestPrice ? number_format($latestPrice->price) . ' VNĐ' : 'Liên hệ',
                    'rating' => round($avgRating, 1),
                    'review_count' => $tour->reviews->count(),
                    'features' => $tour->features->pluck('name')->toArray(),
                    'image' => $tour->images->first() ? $tour->images->first()->image_url : null,
                    'next_availabilities' => $tour->availabilities->map(function ($avail) {
                        return [
                            'date' => $avail->date,
                            'available_slots' => $avail->available_slots
                        ];
                    })->toArray(),
                    'itinerary_preview' => $tour->itineraries->take(2)->map(function ($day) {
                        return [
                            'day' => $day->day,
                            'title' => $day->title
                        ];
                    })->toArray()
                ];

                return $tourData;
            })->toArray();
        } catch (\Exception $e) {
            Log::error('Error getting detailed tour data: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get location data
     */
    private function getLocationData($locations)
    {
        try {
            $locationIds = array_column($locations, 'id');

            // Get tour counts for each location
            $tourCounts = Tour::whereIn('location_id', $locationIds)
                ->groupBy('location_id')
                ->selectRaw('location_id, COUNT(*) as total')
                ->pluck('total', 'location_id')
                ->toArray();

            // Get average prices for each location
            $avgPrices = DB::table('tours')
                ->join('tour_prices', 'tours.id', '=', 'tour_prices.tour_id')
                ->whereIn('tours.location_id', $locationIds)
                ->groupBy('tours.location_id')
                ->selectRaw('tours.location_id, AVG(tour_prices.price) as avg_price')
                ->pluck('avg_price', 'location_id')
                ->toArray();

            // Enhance location data
            $enhancedLocations = [];
            foreach ($locations as $location) {
                $enhancedLocations[] = [
                    'id' => $location['id'],
                    'name' => $location['name'],
                    'city' => $location['city'],
                    'country' => $location['country'],
                    'tour_count' => $tourCounts[$location['id']] ?? 0,
                    'avg_price' => isset($avgPrices[$location['id']]) ?
                        number_format(round($avgPrices[$location['id']])) . ' VNĐ' : 'Không có dữ liệu'
                ];
            }

            return $enhancedLocations;
        } catch (\Exception $e) {
            Log::error('Error getting location data: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get travel type data
     */
    private function getTravelTypeData($travelTypes)
    {
        try {
            $travelTypeIds = array_column($travelTypes, 'id');

            // Get tour counts for each travel type
            $tourCounts = Tour::whereIn('travel_type_id', $travelTypeIds)
                ->groupBy('travel_type_id')
                ->selectRaw('travel_type_id, COUNT(*) as total')
                ->pluck('total', 'travel_type_id')
                ->toArray();

            // Enhance travel type data
            $enhancedTravelTypes = [];
            foreach ($travelTypes as $travelType) {
                $enhancedTravelTypes[] = [
                    'id' => $travelType['id'],
                    'name' => $travelType['name'],
                    'tour_count' => $tourCounts[$travelType['id']] ?? 0
                ];
            }

            return $enhancedTravelTypes;
        } catch (\Exception $e) {
            Log::error('Error getting travel type data: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get feature data
     */
    private function getFeatureData($features)
    {
        try {
            $featureIds = array_column($features, 'id');

            // Get tour counts for each feature
            $tourCounts = DB::table('feature_tour')
                ->whereIn('feature_id', $featureIds)
                ->groupBy('feature_id')
                ->selectRaw('feature_id, COUNT(DISTINCT tour_id) as total')
                ->pluck('total', 'feature_id')
                ->toArray();

            // Enhance feature data
            $enhancedFeatures = [];
            foreach ($features as $feature) {
                $enhancedFeatures[] = [
                    'id' => $feature['id'],
                    'name' => $feature['name'],
                    'tour_count' => $tourCounts[$feature['id']] ?? 0
                ];
            }

            return $enhancedFeatures;
        } catch (\Exception $e) {
            Log::error('Error getting feature data: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get availability data
     */
    private function getAvailabilityData($dates)
    {
        try {
            // Get tours available on the specified dates
            $availabilities = TourAvailability::whereIn('date', $dates)
                ->where('is_active', true)
                ->where('available_slots', '>', 0)
                ->with(['tour:id,name,location_id,travel_type_id', 'tour.location:id,name', 'tour.travelType:id,name'])
                ->get();

            // Group by date
            $availabilityByDate = [];
            foreach ($dates as $date) {
                $toursOnDate = $availabilities->filter(function ($avail) use ($date) {
                    return $avail->date == $date;
                })->map(function ($avail) {
                    return [
                        'tour_id' => $avail->tour_id,
                        'tour_name' => $avail->tour->name,
                        'location' => $avail->tour->location ? $avail->tour->location->name : null,
                        'travel_type' => $avail->tour->travelType ? $avail->tour->travelType->name : null,
                        'available_slots' => $avail->available_slots
                    ];
                })->values()->toArray();

                $availabilityByDate[] = [
                    'date' => $date,
                    'formatted_date' => \Carbon\Carbon::parse($date)->format('d/m/Y'),
                    'day_of_week' => \Carbon\Carbon::parse($date)->locale('vi')->dayName,
                    'tour_count' => count($toursOnDate),
                    'tours' => $toursOnDate
                ];
            }

            return $availabilityByDate;
        } catch (\Exception $e) {
            Log::error('Error getting availability data: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get tour statistics
     */
    private function getTourStatistics()
    {
        try {
            $statistics = [
                'total_tours' => Tour::count(),
                'total_locations' => Location::count(),
                'total_travel_types' => TravelType::count(),
                'popular_locations' => Location::withCount('tours')
                    ->orderBy('tours_count', 'desc')
                    ->limit(5)
                    ->get(['id', 'name', 'tours_count'])
                    ->map(function ($loc) {
                        return [
                            'name' => $loc->name,
                            'tour_count' => $loc->tours_count
                        ];
                    })->toArray(),
                'popular_travel_types' => TravelType::withCount('tours')
                    ->orderBy('tours_count', 'desc')
                    ->limit(5)
                    ->get(['id', 'name', 'tours_count'])
                    ->map(function ($type) {
                        return [
                            'name' => $type->name,
                            'tour_count' => $type->tours_count
                        ];
                    })->toArray(),
                'price_ranges' => [
                    'min' => Tour::join('tour_prices', 'tours.id', '=', 'tour_prices.tour_id')
                        ->min('tour_prices.price'),
                    'max' => Tour::join('tour_prices', 'tours.id', '=', 'tour_prices.tour_id')
                        ->max('tour_prices.price'),
                    'avg' => Tour::join('tour_prices', 'tours.id', '=', 'tour_prices.tour_id')
                        ->avg('tour_prices.price')
                ]
            ];

            return $statistics;
        } catch (\Exception $e) {
            Log::error('Error getting tour statistics: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Build intelligent prompt optimized for Gemini 2.5 Pro with tour system knowledge
     */
    private function buildIntelligentPrompt($message, $context)
    {
        $systemPrompt = "Bạn là TravelBot - một AI assistant thông minh và thân thiện chuyên về du lịch Việt Nam. Khả năng của bạn:

🎯 **Chuyên môn chính:**
- Tư vấn tour du lịch Việt Nam (địa điểm, giá cả, lịch trình)
- Phân tích và so sánh các tour phù hợp
- Đưa ra gợi ý dựa trên ngân sách và sở thích
- Cung cấp thông tin chi tiết về các địa điểm du lịch
- Tư vấn thời điểm tốt nhất để đi du lịch

🧠 **Kiến thức đa dạng:**
- Toán học: Tính toán chính xác, giải thích rõ ràng
- Khoa học: Giải thích hiện tượng tự nhiên, công nghệ
- Lịch sử & văn hóa: Đặc biệt về Việt Nam
- Nấu ăn: Món ăn truyền thống và hiện đại
- Kiến thức tổng quát

💬 **Phong cách giao tiếp:**
- Thân thiện, nhiệt tình, có cá tính
- Sử dụng emoji phù hợp để tạo không khí vui vẻ
- Trả lời ngắn gọn (3-5 câu) nhưng đầy đủ thông tin
- Luôn đưa ra gợi ý hữu ích cho câu hỏi tiếp theo

🎯 **Nguyên tắc quan trọng:**
- Luôn trả lời bằng tiếng Việt
- Với toán học: Tính chính xác + giải thích cách làm
- Với du lịch: Tận dụng dữ liệu tour có sẵn
- Với câu hỏi chung: Trả lời chính xác, dễ hiểu
- Thể hiện sự hiểu biết sâu sắc và kinh nghiệm thực tế";

        // Add tour system knowledge
        $tourSystemInfo = "";
        if (!empty($context['tour_system_data'])) {
            $tourSystemInfo .= "\n\n📊 **Dữ liệu hệ thống tour du lịch:**\n";

            // Add tour information
            if (!empty($context['tour_system_data']['tours'])) {
                $tourSystemInfo .= "\n🏝️ **Tour phù hợp:**\n";
                foreach (array_slice($context['tour_system_data']['tours'], 0, 3) as $index => $tour) {
                    $tourSystemInfo .= ($index + 1) . ". **{$tour['name']}** - {$tour['location']['name']} - {$tour['duration']} - {$tour['price_formatted']}\n";
                    $tourSystemInfo .= "   - Loại tour: {$tour['travel_type']}\n";
                    $tourSystemInfo .= "   - Đánh giá: " . ($tour['rating'] > 0 ? "{$tour['rating']}/5 ({$tour['review_count']} đánh giá)" : "Chưa có đánh giá") . "\n";
                    if (!empty($tour['features'])) {
                        $tourSystemInfo .= "   - Tiện ích: " . implode(', ', array_slice($tour['features'], 0, 3)) . "\n";
                    }
                    if (!empty($tour['next_availabilities'])) {
                        $dates = array_column($tour['next_availabilities'], 'date');
                        $formattedDates = array_map(function ($date) {
                            return \Carbon\Carbon::parse($date)->format('d/m/Y');
                        }, $dates);
                        $tourSystemInfo .= "   - Ngày khởi hành gần nhất: " . implode(', ', $formattedDates) . "\n";
                    }
                }

                if (count($context['tour_system_data']['tours']) > 3) {
                    $tourSystemInfo .= "...và " . (count($context['tour_system_data']['tours']) - 3) . " tour khác\n";
                }
            }

            // Add location information
            if (!empty($context['tour_system_data']['locations'])) {
                $tourSystemInfo .= "\n📍 **Thông tin địa điểm:**\n";
                foreach ($context['tour_system_data']['locations'] as $location) {
                    $tourSystemInfo .= "- **{$location['name']}** ({$location['city']}, {$location['country']}): {$location['tour_count']} tour, giá trung bình: {$location['avg_price']}\n";
                }
            }

            // Add travel type information
            if (!empty($context['tour_system_data']['travel_types'])) {
                $tourSystemInfo .= "\n🚌 **Loại hình du lịch:**\n";
                foreach ($context['tour_system_data']['travel_types'] as $travelType) {
                    $tourSystemInfo .= "- **{$travelType['name']}**: {$travelType['tour_count']} tour\n";
                }
            }

            // Add availability information
            if (!empty($context['tour_system_data']['availabilities'])) {
                $tourSystemInfo .= "\n📅 **Lịch khởi hành:**\n";
                foreach ($context['tour_system_data']['availabilities'] as $availability) {
                    $tourSystemInfo .= "- **{$availability['formatted_date']}** ({$availability['day_of_week']}): {$availability['tour_count']} tour khả dụng\n";
                }
            }

            // Add statistics
            if (!empty($context['tour_system_data']['statistics'])) {
                $stats = $context['tour_system_data']['statistics'];
                $tourSystemInfo .= "\n📈 **Thống kê tổng quan:**\n";
                $tourSystemInfo .= "- Tổng số tour: {$stats['total_tours']}\n";
                $tourSystemInfo .= "- Tổng số địa điểm: {$stats['total_locations']}\n";
                $tourSystemInfo .= "- Tổng số loại hình du lịch: {$stats['total_travel_types']}\n";

                if (!empty($stats['price_ranges'])) {
                    $minPrice = number_format($stats['price_ranges']['min']) . ' VNĐ';
                    $maxPrice = number_format($stats['price_ranges']['max']) . ' VNĐ';
                    $avgPrice = number_format(round($stats['price_ranges']['avg'])) . ' VNĐ';
                    $tourSystemInfo .= "- Khoảng giá: {$minPrice} - {$maxPrice} (trung bình: {$avgPrice})\n";
                }

                if (!empty($stats['popular_locations'])) {
                    $tourSystemInfo .= "- Địa điểm phổ biến nhất: {$stats['popular_locations'][0]['name']} ({$stats['popular_locations'][0]['tour_count']} tour)\n";
                }
            }
        }

        // Add context information
        $contextInfo = "";
        if (!empty($context['user_intent'])) {
            $contextInfo .= "\n🎯 **Ý định người dùng:** " . $context['user_intent'];
        }

        if (!empty($context['current_season'])) {
            $contextInfo .= "\n🌤️ **Mùa hiện tại:** " . $context['current_season'];
        }

        // Add entity information
        $entityInfo = "";
        if (!empty($context['entities'])) {
            $entities = $context['entities'];

            if (!empty($entities['locations'])) {
                $locationNames = array_column($entities['locations'], 'name');
                $entityInfo .= "\n📍 **Địa điểm được nhắc đến:** " . implode(', ', $locationNames);
            }

            if (!empty($entities['travel_types'])) {
                $typeNames = array_column($entities['travel_types'], 'name');
                $entityInfo .= "\n🚌 **Loại hình du lịch được nhắc đến:** " . implode(', ', $typeNames);
            }

            if (!empty($entities['features'])) {
                $featureNames = array_column($entities['features'], 'name');
                $entityInfo .= "\n✨ **Tiện ích được nhắc đến:** " . implode(', ', $featureNames);
            }

            if (!empty($entities['dates'])) {
                $formattedDates = array_map(function ($date) {
                    return \Carbon\Carbon::parse($date)->format('d/m/Y');
                }, $entities['dates']);
                $entityInfo .= "\n📅 **Ngày được nhắc đến:** " . implode(', ', $formattedDates);
            }

            if (!empty($entities['price_ranges'])) {
                $formattedPrices = array_map(function ($price) {
                    return number_format($price) . ' VNĐ';
                }, $entities['price_ranges']);
                $entityInfo .= "\n💰 **Giá được nhắc đến:** " . implode(', ', $formattedPrices);
            }

            if (!empty($entities['durations'])) {
                $durationInfo = [];
                if (isset($entities['durations']['days'])) {
                    $durationInfo[] = "{$entities['durations']['days']} ngày";
                }
                if (isset($entities['durations']['nights'])) {
                    $durationInfo[] = "{$entities['durations']['nights']} đêm";
                }
                if (!empty($durationInfo)) {
                    $entityInfo .= "\n⏱️ **Thời gian được nhắc đến:** " . implode(', ', $durationInfo);
                }
            }
        }

        $fullPrompt = $systemPrompt . $tourSystemInfo . $contextInfo . $entityInfo . "\n\n❓ **Câu hỏi:** " . $message . "\n\n💡 **Hãy trả lời một cách chuyên nghiệp, thân thiện và hữu ích:**";

        return $fullPrompt;
    }

    /**
     * Call Gemini 2.5 Pro API with optimized configuration
     */
    private function callGeminiAPI($prompt)
    {
        try {
            Log::info('Calling Gemini 2.5 Pro API', [
                'api_key_exists' => !empty($this->apiKey),
                'api_key_length' => strlen($this->apiKey ?? ''),
                'prompt_length' => strlen($prompt),
                'model' => 'gemini-2.5-pro-preview-05-06'
            ]);

            if (empty($this->apiKey)) {
                throw new \Exception('Gemini API key is not configured');
            }

            $requestData = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.9,        // Higher creativity for better responses
                    'topK' => 64,               // More diverse token selection
                    'topP' => 0.95,             // Higher nucleus sampling
                    'maxOutputTokens' => 8192,   // Gemini 2.5 Pro supports more tokens
                    'candidateCount' => 1,
                    'stopSequences' => [],
                    'responseMimeType' => 'text/plain'
                ],
                'safetySettings' => [
                    [
                        'category' => 'HARM_CATEGORY_HARASSMENT',
                        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                    ],
                    [
                        'category' => 'HARM_CATEGORY_HATE_SPEECH',
                        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                    ],
                    [
                        'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                    ],
                    [
                        'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                    ]
                ]
            ];

            Log::info('Gemini 2.5 Pro Request', [
                'url' => $this->baseUrl,
                'config' => $requestData['generationConfig']
            ]);

            $response = Http::timeout(60)  // Longer timeout for better model
                ->retry(3, 1000)           // Retry 3 times with 1s delay
                ->post($this->baseUrl . '?key=' . $this->apiKey, $requestData);

            Log::info('Gemini 2.5 Pro Response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body_length' => strlen($response->body()),
                'headers' => $response->headers()
            ]);

            if (!$response->successful()) {
                $errorBody = $response->body();
                Log::error('Gemini 2.5 Pro API Error', [
                    'status' => $response->status(),
                    'body' => $errorBody,
                    'headers' => $response->headers()
                ]);

                // Parse error for better debugging
                $errorData = json_decode($errorBody, true);
                $errorMessage = $errorData['error']['message'] ?? 'Unknown error';

                throw new \Exception("Gemini 2.5 Pro API error ({$response->status()}): {$errorMessage}");
            }

            $data = $response->json();

            Log::info('Gemini 2.5 Pro Parsed Response', [
                'has_candidates' => isset($data['candidates']),
                'candidates_count' => count($data['candidates'] ?? []),
                'response_structure' => array_keys($data),
                'usage_metadata' => $data['usageMetadata'] ?? null
            ]);

            // Check for content filtering
            if (empty($data['candidates'])) {
                Log::warning('No candidates in Gemini response', ['full_response' => $data]);
                throw new \Exception('Response was filtered by safety settings');
            }

            $candidate = $data['candidates'][0];

            // Check finish reason
            if (isset($candidate['finishReason']) && $candidate['finishReason'] !== 'STOP') {
                Log::warning('Unusual finish reason', [
                    'finish_reason' => $candidate['finishReason'],
                    'safety_ratings' => $candidate['safetyRatings'] ?? null
                ]);
            }

            if (empty($candidate['content']['parts'][0]['text'])) {
                Log::error('Empty text in Gemini response', [
                    'candidate' => $candidate,
                    'full_response' => $data
                ]);
                throw new \Exception('Empty response text from Gemini 2.5 Pro API');
            }

            $aiMessage = $candidate['content']['parts'][0]['text'];

            Log::info('Gemini 2.5 Pro Success', [
                'response_length' => strlen($aiMessage),
                'finish_reason' => $candidate['finishReason'] ?? 'unknown',
                'usage' => $data['usageMetadata'] ?? null
            ]);

            return [
                'message' => trim($aiMessage),
                'raw_response' => $data,
                'usage_metadata' => $data['usageMetadata'] ?? null,
                'finish_reason' => $candidate['finishReason'] ?? null
            ];
        } catch (\Exception $e) {
            Log::error('Gemini 2.5 Pro API Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Generate contextual suggestions based on tour system data
     */
    private function generateContextualSuggestions($message, $context)
    {
        $message = strtolower($message);

        // If we have tour data, generate tour-specific suggestions
        if (!empty($context['tour_system_data']['tours'])) {
            $tours = $context['tour_system_data']['tours'];
            $suggestions = [];

            // Suggest viewing specific tours
            if (count($tours) > 0) {
                $suggestions[] = "Xem chi tiết tour " . $tours[0]['name'];
            }

            // Suggest comparing tours if we have multiple
            if (count($tours) > 1) {
                $suggestions[] = "So sánh các tour " . $tours[0]['location']['name'];
            }

            // Suggest checking availability
            if (!empty($tours[0]['next_availabilities'])) {
                $suggestions[] = "Kiểm tra lịch khởi hành";
            }

            // Suggest filtering by price
            $suggestions[] = "Tìm tour giá tốt hơn";

            // Add location-specific suggestion
            if (!empty($context['entities']['locations'])) {
                $location = $context['entities']['locations'][0]['name'];
                $suggestions[] = "Điểm tham quan nổi tiếng ở " . $location;
            }

            return array_slice($suggestions, 0, 4);
        }

        // Math/calculation suggestions
        if (preg_match('/[\d\+\-\*\/=]/', $message) || strpos($message, 'toán') !== false) {
            return [
                'Giải bài toán phức tạp hơn',
                'Giải thích công thức toán học',
                'Ứng dụng toán trong thực tế',
                'Chuyển sang tư vấn du lịch'
            ];
        }

        // Travel suggestions with more sophistication
        if (strpos($message, 'tour') !== false || strpos($message, 'du lịch') !== false) {
            return [
                'Tìm tour theo ngân sách',
                'Tour du lịch mùa hè',
                'Địa điểm du lịch nổi tiếng',
                'Tour phù hợp cho gia đình'
            ];
        }

        // Location-specific suggestions
        $popularLocations = ['hà nội', 'đà nẵng', 'hồ chí minh', 'nha trang', 'phú quốc', 'sapa', 'hạ long'];
        foreach ($popularLocations as $location) {
            if (strpos($message, $location) !== false) {
                return [
                    "Tour {$location} giá tốt",
                    "Điểm tham quan ở {$location}",
                    "Ẩm thực {$location}",
                    "Thời điểm đẹp nhất để đến {$location}"
                ];
            }
        }

        // Science/knowledge suggestions
        if (strpos($message, 'tại sao') !== false || strpos($message, 'như thế nào') !== false) {
            return [
                'Giải thích sâu hơn về cơ chế',
                'Ví dụ thực tế minh họa',
                'Ứng dụng trong đời sống',
                'Chủ đề khoa học liên quan'
            ];
        }

        // Cooking suggestions
        if (strpos($message, 'nấu') !== false || strpos($message, 'món') !== false) {
            return [
                'Biến tấu món ăn sáng tạo',
                'Mẹo nấu ăn chuyên nghiệp',
                'Món ăn đặc sản vùng miền',
                'Cách bảo quản nguyên liệu'
            ];
        }

        // Greeting/general suggestions
        if (strpos($message, 'chào') !== false || strpos($message, 'hello') !== false) {
            return [
                'Khám phá tour du lịch hot',
                'Tư vấn địa điểm du lịch',
                'Tìm tour theo ngân sách',
                'Tour phù hợp cho gia đình'
            ];
        }

        // Default sophisticated suggestions
        return [
            'Tư vấn tour du lịch',
            'Địa điểm du lịch nổi tiếng',
            'Tour du lịch giá tốt',
            'Kinh nghiệm du lịch'
        ];
    }

    /**
     * Detect the type of response for better handling
     */
    private function detectResponseType($message)
    {
        $message = strtolower($message);

        if (preg_match('/[\d\+\-\*\/=]/', $message)) {
            return 'calculation';
        }

        if (strpos($message, 'tour') !== false || strpos($message, 'du lịch') !== false) {
            return 'travel';
        }

        if (strpos($message, 'nấu') !== false || strpos($message, 'món') !== false) {
            return 'cooking';
        }

        if (strpos($message, 'tại sao') !== false || strpos($message, 'như thế nào') !== false) {
            return 'explanation';
        }

        if (strpos($message, 'chào') !== false || strpos($message, 'hello') !== false) {
            return 'greeting';
        }

        return 'general';
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
     * Enhanced method to determine if we should use AI
     */
    public function shouldUseAI($message)
    {
        // Always use Gemini 2.5 Pro for best responses
        return true;
    }

    /**
     * Generate comprehensive fallback response when API fails
     */
    private function generateFallbackResponse($message, $context = [])
    {
        $message = strtolower(trim($message));

        // Handle math calculations locally with more operations
        if (preg_match('/(\d+)\s*([\+\-\*\/\^%])\s*(\d+)/', $message, $matches)) {
            return $this->handleAdvancedMathCalculation($message, $matches);
        }

        // Handle greetings with personality
        if (preg_match('/\b(chào|hello|hi|xin chào)\b/', $message)) {
            return [
                'message' => '👋 Xin chào! Tôi là TravelBot với AI Gemini 2.5 Pro! 

🤖 **Hiện tại:** AI đang tạm thời bảo trì
✅ **Vẫn có thể:** 
• Giải toán cơ bản (cộng, trừ, nhân, chia)
• Tìm tour du lịch từ database
• Tư vấn địa điểm du lịch cơ bản
• Trả lời câu hỏi đơn giản

Hãy thử hỏi tôi nhé! 😊',
                'suggestions' => [
                    'Tính 15 × 8',
                    'Tìm tour Sapa',
                    'Tour biển đẹp',
                    'Địa điểm hot nhất'
                ]
            ];
        }

        // Handle travel queries with database lookup
        if ($this->isTravelRelated($message)) {
            // Try to get some tour data
            $entities = $this->extractEntities($message);
            $tours = $this->getDetailedTourData($message, $entities);

            if (!empty($tours)) {
                $tourInfo = "";
                foreach (array_slice($tours, 0, 2) as $tour) {
                    $tourInfo .= "\n• **{$tour['name']}** - {$tour['location']['name']} - {$tour['duration']} - {$tour['price_formatted']}";
                }

                return [
                    'message' => "🏖️ **Tôi tìm thấy một số tour phù hợp với yêu cầu của bạn!**

Mặc dù AI đang bảo trì, tôi vẫn có thể giúp bạn với các tour sau:{$tourInfo}

Bạn có thể xem chi tiết hoặc tìm thêm tour khác. Tôi sẽ cố gắng hỗ trợ bạn tốt nhất! 😊",
                    'suggestions' => [
                        'Xem chi tiết tour',
                        'Tìm tour khác',
                        'So sánh các tour',
                        'Kiểm tra lịch khởi hành'
                    ]
                ];
            }

            return [
                'message' => '🏖️ **Du lịch là đam mê của tôi!** 

Mặc dù Gemini 2.5 Pro đang bảo trì, tôi vẫn có thể:
• Tìm tour từ database theo địa điểm
• Lọc tour theo ngân sách
• Gợi ý điểm đến phù hợp
• So sánh cơ bản các tour

Bạn muốn đi đâu hoặc ngân sách bao nhiêu? 🎯',
                'suggestions' => [
                    'Tour Hà Nội - Sapa',
                    'Tour biển Nha Trang',
                    'Tour dưới 3 triệu',
                    'Điểm đến gần Hà Nội'
                ]
            ];
        }

        // Handle specific location queries
        $locations = [
            'sapa' => '🏔️ Sapa - Thiên đường mây trắng',
            'hạ long' => '🌊 Hạ Long - Kỳ quan thế giới',
            'đà nẵng' => '🏖️ Đà Nẵng - Thành phố đáng sống',
            'nha trang' => '🏝️ Nha Trang - Biển xanh cát trắng',
            'phú quốc' => '🌴 Phú Quốc - Đảo ngọc',
            'hội an' => '🏮 Hội An - Phố cổ thơ mộng'
        ];

        foreach ($locations as $location => $description) {
            if (strpos($message, $location) !== false) {
                return [
                    'message' => "✨ **{$description}**

Tuyệt vời! Bạn đã chọn một điểm đến tuyệt vời. Tôi có thể tìm các tour đến " . ucfirst($location) . " từ database với nhiều lựa chọn về:
• Thời gian (1-7 ngày)
• Ngân sách (từ bình dân đến cao cấp)  
• Phong cách (gia đình, cặp đôi, nhóm bạn)

Bạn muốn xem loại tour nào? 🎯",
                    'suggestions' => [
                        "Tour {$location} 2N1Đ",
                        "Tour {$location} giá tốt",
                        "Lịch trình {$location} chi tiết",
                        "So sánh tour {$location}"
                    ]
                ];
            }
        }

        // Default comprehensive response
        return [
            'message' => '🤖 **TravelBot với Gemini 2.5 Pro đang tạm thời bảo trì**

✅ **Tôi vẫn có thể giúp bạn:**
• 🧮 Giải toán: cộng, trừ, nhân, chia, lũy thừa
• 🏖️ Tìm tour du lịch theo địa điểm & ngân sách
• 🎯 Tư vấn cơ bản về điểm đến
• ❓ Trả lời câu hỏi đơn giản

**Ví dụ:** "Tính 25 × 4" hoặc "Tìm tour Sapa"

Hãy thử hỏi tôi nhé! 😊',
            'suggestions' => [
                'Tính 12 + 8',
                'Tour du lịch hot',
                'Địa điểm gần Hà Nội',
                'Thử lại Gemini 2.5 Pro'
            ]
        ];
    }

    /**
     * Handle advanced math calculations with more operations
     */
    private function handleAdvancedMathCalculation($message, $matches)
    {
        try {
            $num1 = floatval($matches[1]);
            $operator = $matches[2];
            $num2 = floatval($matches[3]);

            $result = null;
            $operatorText = '';
            $explanation = '';

            switch ($operator) {
                case '+':
                    $result = $num1 + $num2;
                    $operatorText = 'cộng';
                    $explanation = "Phép cộng: {$num1} + {$num2}";
                    break;
                case '-':
                    $result = $num1 - $num2;
                    $operatorText = 'trừ';
                    $explanation = "Phép trừ: {$num1} - {$num2}";
                    break;
                case '*':
                case '×':
                    $result = $num1 * $num2;
                    $operatorText = 'nhân';
                    $explanation = "Phép nhân: {$num1} × {$num2}";
                    break;
                case '/':
                case '÷':
                    if ($num2 != 0) {
                        $result = $num1 / $num2;
                        $operatorText = 'chia';
                        $explanation = "Phép chia: {$num1} ÷ {$num2}";
                    } else {
                        return [
                            'message' => '❌ **Lỗi toán học: Chia cho 0!**

🚫 Không thể chia một số cho 0 vì:
• Kết quả sẽ là vô cực (∞)
• Điều này không xác định trong toán học
• Vi phạm quy tắc cơ bản của phép chia

💡 **Thử lại với:** số chia khác 0
**Ví dụ:** 10 ÷ 2 = 5 ✅',
                            'suggestions' => [
                                'Thử 15 ÷ 3',
                                'Học về phép chia',
                                'Phép tính khác',
                                'Hỏi về du lịch'
                            ]
                        ];
                    }
                    break;
                case '^':
                    $result = pow($num1, $num2);
                    $operatorText = 'lũy thừa';
                    $explanation = "Lũy thừa: {$num1} mũ {$num2}";
                    break;
                case '%':
                    if ($num2 != 0) {
                        $result = $num1 % $num2;
                        $operatorText = 'chia lấy dư';
                        $explanation = "Phép chia lấy dư: {$num1} mod {$num2}";
                    } else {
                        return [
                            'message' => '❌ Không thể chia lấy dư cho 0!',
                            'suggestions' => ['Thử số khác', 'Phép tính khác']
                        ];
                    }
                    break;
            }

            if ($result !== null) {
                $resultFormatted = is_float($result) && $result != intval($result) ?
                    number_format($result, 4) : number_format($result);

                return [
                    'message' => "🧮 **Kết quả tính toán:**

**{$explanation} = {$resultFormatted}**

📝 **Giải thích:** Đây là phép {$operatorText} cơ bản
🤖 **Tính bởi:** Hệ thống cục bộ (Gemini 2.5 Pro đang bảo trì)

Bạn có muốn thử phép tính nào khác không? 😊",
                    'suggestions' => [
                        'Phép tính phức tạp hơn',
                        'Giải thích cách tính',
                        'Toán học nâng cao',
                        'Chuyển sang du lịch'
                    ]
                ];
            }
        } catch (\Exception $e) {
            Log::error('Advanced math calculation error: ' . $e->getMessage());
            return [
                'message' => '❌ **Lỗi tính toán!**

Có vấn đề khi xử lý phép tính. Hãy thử:
• Viết rõ ràng: "5 + 3" hoặc "10 × 2"
• Sử dụng số nguyên hoặc thập phân đơn giản
• Kiểm tra ký hiệu toán học

🧮 **Ví dụ đúng:** 15 + 25, 8 × 7, 100 ÷ 4',
                'suggestions' => [
                    'Thử 2 + 2',
                    'Viết phép tính đơn giản',
                    'Hướng dẫn cách tính',
                    'Chuyển chủ đề khác'
                ]
            ];
        }
    }

    /**
     * Legacy method for backward compatibility
     */
    public function generateChatResponse($message, $tourContext = [])
    {
        return $this->generateIntelligentResponse($message, $tourContext);
    }

    /**
     * Legacy method for backward compatibility  
     */
    public function generateGeneralResponse($message, $context = [])
    {
        return $this->generateIntelligentResponse($message, $context);
    }
}
