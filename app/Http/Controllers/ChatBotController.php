<?php

namespace App\Http\Controllers;

use App\Models\Tour;
use App\Models\Location;
use App\Models\TravelType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatBotController extends Controller
{
    /**
     * Process chatbot queries about tours
     */
    public function processQuery(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $message = strtolower(trim($request->message));
        Log::info('ChatBot Query: ' . $message);

        try {
            $response = $this->analyzeMessage($message);
            return response()->json([
                'message' => $response['message'],
                'data' => $response['data'] ?? null,
                'suggestions' => $response['suggestions'] ?? []
            ]);
        } catch (\Exception $e) {
            Log::error('ChatBot Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Xin chào! Tôi có thể giúp bạn tìm hiểu về các tour du lịch. Bạn có thể hỏi về giá tour, địa điểm, lịch trình hoặc các thông tin khác.',
                'suggestions' => [
                    'Tìm tour Hà Nội',
                    'Tour giá rẻ dưới 2 triệu',
                    'Tour 3 ngày 2 đêm',
                    'Xem tour nổi bật'
                ]
            ]);
        }
    }

    /**
     * Analyze user message and provide appropriate response
     */
    private function analyzeMessage($message)
    {
        // Greeting patterns
        if ($this->matchesPattern($message, ['xin chào', 'hello', 'hi', 'chào'])) {
            return [
                'message' => 'Xin chào! Tôi là trợ lý du lịch ảo. Tôi có thể giúp bạn tìm kiếm tour, kiểm tra giá cả, xem lịch trình và nhiều thông tin khác. Bạn muốn tìm hiểu về điều gì?',
                'suggestions' => [
                    'Tìm tour theo địa điểm',
                    'Xem tour theo giá',
                    'Tour theo số ngày',
                    'Tour nổi bật nhất'
                ]
            ];
        }

        // Price inquiry patterns
        if ($this->matchesPattern($message, ['giá', 'chi phí', 'bao nhiêu tiền', 'price'])) {
            return $this->handlePriceInquiry($message);
        }

        // Location search patterns
        if ($this->matchesPattern($message, ['tour', 'đi', 'du lịch'])) {
            return $this->handleLocationSearch($message);
        }

        // Duration search patterns
        if ($this->matchesPattern($message, ['ngày', 'đêm', 'days', 'nights'])) {
            return $this->handleDurationSearch($message);
        }

        // Featured tours patterns
        if ($this->matchesPattern($message, ['nổi bật', 'hot', 'phổ biến', 'tốt nhất', 'featured'])) {
            return $this->handleFeaturedTours();
        }

        // Available dates patterns
        if ($this->matchesPattern($message, ['lịch trình', 'ngày khởi hành', 'available', 'schedule'])) {
            return $this->handleAvailabilityInquiry($message);
        }

        // General tour search
        return $this->handleGeneralSearch($message);
    }

    /**
     * Handle price-related inquiries
     */
    private function handlePriceInquiry($message)
    {
        // Extract price range if mentioned
        preg_match_all('/\d+/', $message, $numbers);
        $numbers = array_map('intval', $numbers[0]);

        if (!empty($numbers)) {
            $maxPrice = max($numbers) * (Str::contains($message, ['triệu', 'tr']) ? 1000000 : 1000);

            $tours = Tour::with(['location', 'images', 'travelType'])
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
                    'data' => $tours,
                    'suggestions' => [
                        'Xem chi tiết tour',
                        'Tìm tour khác',
                        'Lịch khởi hành'
                    ]
                ];
            }
        }

        // General price information
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
    }

    /**
     * Handle location-based search
     */
    private function handleLocationSearch($message)
    {
        // Extract location names from message
        $locations = Location::all();
        $foundLocation = null;

        foreach ($locations as $location) {
            if (Str::contains($message, strtolower($location->name))) {
                $foundLocation = $location;
                break;
            }
        }

        if ($foundLocation) {
            $tours = Tour::with(['location', 'images', 'travelType'])
                ->where('location_id', $foundLocation->id)
                ->limit(5)
                ->get()
                ->map(function ($tour) {
                    return $this->formatTourData($tour);
                });

            return [
                'message' => "Tôi tìm thấy {$tours->count()} tour tại {$foundLocation->name}:",
                'data' => $tours,
                'suggestions' => [
                    'Xem thêm tour ' . $foundLocation->name,
                    'So sánh giá tour',
                    'Lịch khởi hành'
                ]
            ];
        }

        // Show available locations
        $locationList = $locations->pluck('name')->take(10)->toArray();
        return [
            'message' => 'Chúng tôi có tour tại các địa điểm sau:',
            'data' => $locationList,
            'suggestions' => array_slice($locationList, 0, 4)
        ];
    }

    /**
     * Handle duration-based search
     */
    private function handleDurationSearch($message)
    {
        // Extract duration numbers
        preg_match_all('/(\d+)\s*(ngày|days?)/i', $message, $dayMatches);
        preg_match_all('/(\d+)\s*(đêm|nights?)/i', $message, $nightMatches);

        $days = !empty($dayMatches[1]) ? intval($dayMatches[1][0]) : null;
        $nights = !empty($nightMatches[1]) ? intval($nightMatches[1][0]) : null;

        $query = Tour::with(['location', 'images', 'travelType']);

        if ($days) {
            $query->where('days', $days);
        }
        if ($nights) {
            $query->where('nights', $nights);
        }

        $tours = $query->limit(5)->get()->map(function ($tour) {
            return $this->formatTourData($tour);
        });

        if ($tours->count() > 0) {
            $duration = $days ? "{$days} ngày" : "";
            $duration .= $nights ? " {$nights} đêm" : "";

            return [
                'message' => "Tôi tìm thấy {$tours->count()} tour {$duration}:",
                'data' => $tours,
                'suggestions' => [
                    'Xem chi tiết',
                    'Tìm tour khác',
                    'So sánh giá'
                ]
            ];
        }

        return [
            'message' => 'Không tìm thấy tour phù hợp. Dưới đây là các gói tour phổ biến:',
            'data' => $this->getPopularDurations(),
            'suggestions' => [
                '3 ngày 2 đêm',
                '4 ngày 3 đêm',
                '5 ngày 4 đêm'
            ]
        ];
    }

    /**
     * Handle featured tours request
     */
    private function handleFeaturedTours()
    {
        $tours = Tour::with(['location', 'images', 'travelType', 'reviews'])
            ->withCount('reviews')
            ->orderBy('reviews_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($tour) {
                return $this->formatTourData($tour);
            });

        return [
            'message' => 'Đây là những tour nổi bật và được yêu thích nhất:',
            'data' => $tours,
            'suggestions' => [
                'Xem đánh giá',
                'Kiểm tra lịch trình',
                'So sánh tour'
            ]
        ];
    }

    /**
     * Handle availability inquiry
     */
    private function handleAvailabilityInquiry($message)
    {
        $upcomingTours = Tour::with(['location', 'availabilities'])
            ->whereHas('availabilities', function ($query) {
                $query->where('date', '>=', now()->toDateString())
                    ->where('is_active', true)
                    ->where('available_slots', '>', 0);
            })
            ->limit(5)
            ->get()
            ->map(function ($tour) {
                $nextAvailable = $tour->availabilities
                    ->where('date', '>=', now()->toDateString())
                    ->where('is_active', true)
                    ->where('available_slots', '>', 0)
                    ->first();

                return [
                    'id' => $tour->id,
                    'name' => $tour->name,
                    'location' => $tour->location->name ?? '',
                    'next_departure' => $nextAvailable ? $nextAvailable->date : null,
                    'available_slots' => $nextAvailable ? $nextAvailable->available_slots : 0,
                    'price' => $tour->prices()->orderBy('date', 'desc')->first()?->price ?? 0
                ];
            });

        return [
            'message' => 'Dưới đây là các tour có lịch khởi hành sớm nhất:',
            'data' => $upcomingTours,
            'suggestions' => [
                'Đặt tour ngay',
                'Xem chi tiết',
                'Tìm ngày khác'
            ]
        ];
    }

    /**
     * Handle general search
     */
    private function handleGeneralSearch($message)
    {
        $tours = Tour::with(['location', 'images', 'travelType'])
            ->where('name', 'like', "%{$message}%")
            ->orWhere('description', 'like', "%{$message}%")
            ->orWhereHas('location', function ($query) use ($message) {
                $query->where('name', 'like', "%{$message}%");
            })
            ->limit(5)
            ->get()
            ->map(function ($tour) {
                return $this->formatTourData($tour);
            });

        if ($tours->count() > 0) {
            return [
                'message' => "Tôi tìm thấy {$tours->count()} kết quả phù hợp:",
                'data' => $tours,
                'suggestions' => [
                    'Xem chi tiết',
                    'So sánh tour',
                    'Kiểm tra giá'
                ]
            ];
        }

        return [
            'message' => 'Tôi không tìm thấy thông tin phù hợp. Dưới đây là những gợi ý có thể giúp bạn:',
            'suggestions' => [
                'Tìm tour theo địa điểm',
                'Xem tour nổi bật',
                'Tour theo ngân sách',
                'Liên hệ tư vấn'
            ]
        ];
    }

    /**
     * Format tour data for response
     */
    private function formatTourData($tour)
    {
        return [
            'id' => $tour->id,
            'name' => $tour->name,
            'location' => $tour->location->name ?? '',
            'category' => $tour->travelType->name ?? '',
            'duration' => "{$tour->days} ngày {$tour->nights} đêm",
            'price' => $tour->prices()->orderBy('date', 'desc')->first()?->price ?? 0,
            'price_formatted' => number_format($tour->prices()->orderBy('date', 'desc')->first()?->price ?? 0) . ' VNĐ',
            'image' => $tour->images()->where('is_primary', true)->first()?->image_url ?? '',
            'rating' => round($tour->reviews->avg('rating') ?? 0, 1),
            'review_count' => $tour->reviews->count()
        ];
    }

    /**
     * Get price ranges for tours
     */
    private function getPriceRanges()
    {
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
    }

    /**
     * Get popular tour durations
     */
    private function getPopularDurations()
    {
        return Tour::selectRaw('days, nights, COUNT(*) as count')
            ->groupBy('days', 'nights')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'duration' => "{$item->days} ngày {$item->nights} đêm",
                    'count' => $item->count
                ];
            });
    }

    /**
     * Check if message matches any of the given patterns
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
     * Get tour details by ID for chatbot
     */
    public function getTourDetails($id)
    {
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
    }
}
