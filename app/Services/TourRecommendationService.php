<?php

namespace App\Services;

use App\Models\Tour;
use App\Models\UserSearchHistory;
use App\Models\Booking;
use App\Models\Location;
use App\Models\TravelType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TourRecommendationService
{
    /**
     * Get personalized tour recommendations based on user history
     */
    public function getPersonalizedRecommendations($userId, $searchHistory = [])
    {
        try {
            $cacheKey = "tour_recommendations_{$userId}_" . md5(implode('|', $searchHistory));

            return Cache::remember($cacheKey, 1800, function () use ($userId, $searchHistory) {
                // Analyze user preferences
                $preferences = $this->analyzeUserPreferences($userId, $searchHistory);

                // Get base recommendations
                $recommendations = $this->getBaseRecommendations($preferences);

                // Apply collaborative filtering
                $collaborativeRecs = $this->getCollaborativeRecommendations($userId, $preferences);

                // Merge and rank recommendations
                $finalRecommendations = $this->mergeAndRankRecommendations(
                    $recommendations,
                    $collaborativeRecs,
                    $preferences
                );

                Log::info('Recommendations generated', [
                    'user_id' => $userId,
                    'preferences' => $preferences,
                    'recommendation_count' => count($finalRecommendations)
                ]);

                return $finalRecommendations;
            });
        } catch (\Exception $e) {
            Log::error('Recommendation generation failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);

            // Fallback to popular tours
            return $this->getPopularTours();
        }
    }

    /**
     * Analyze user preferences from search history and bookings
     */
    private function analyzeUserPreferences($userId, $searchHistory)
    {
        $preferences = [
            'locations' => [],
            'travel_types' => [],
            'price_ranges' => [],
            'durations' => [],
            'keywords' => [],
            'seasonal_preferences' => [],
            'booking_patterns' => []
        ];

        try {
            // Analyze search history
            $allSearches = array_merge(
                $searchHistory,
                UserSearchHistory::where('user_id', $userId)
                    ->orderBy('created_at', 'desc')
                    ->limit(20)
                    ->pluck('search_query')
                    ->toArray()
            );

            foreach ($allSearches as $search) {
                $this->extractPreferencesFromQuery($search, $preferences);
            }

            // Analyze booking history
            $bookings = Booking::where('user_id', $userId)
                ->with(['bookable.location', 'bookable.travelType'])
                ->get();

            foreach ($bookings as $booking) {
                if ($booking->bookable) {
                    $this->extractPreferencesFromBooking($booking, $preferences);
                }
            }

            // Weight and normalize preferences
            $preferences = $this->normalizePreferences($preferences);

            return $preferences;
        } catch (\Exception $e) {
            Log::error('Preference analysis failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            return $preferences;
        }
    }

    /**
     * Extract preferences from search query
     */
    private function extractPreferencesFromQuery($query, &$preferences)
    {
        $query = strtolower($query);

        // Extract locations
        $locations = Location::all();
        foreach ($locations as $location) {
            if (strpos($query, strtolower($location->name)) !== false) {
                $preferences['locations'][$location->id] =
                    ($preferences['locations'][$location->id] ?? 0) + 1;
            }
        }

        // Extract travel types
        $travelTypes = TravelType::all();
        foreach ($travelTypes as $type) {
            if (strpos($query, strtolower($type->name)) !== false) {
                $preferences['travel_types'][$type->id] =
                    ($preferences['travel_types'][$type->id] ?? 0) + 1;
            }
        }

        // Extract price preferences
        if (preg_match('/(\d+)\s*(triệu|tr|nghìn|k)/i', $query, $matches)) {
            $amount = intval($matches[1]);
            $unit = strtolower($matches[2]);

            if (in_array($unit, ['triệu', 'tr'])) {
                $amount *= 1000000;
            } elseif (in_array($unit, ['nghìn', 'k'])) {
                $amount *= 1000;
            }

            $priceRange = $this->getPriceRange($amount);
            $preferences['price_ranges'][$priceRange] =
                ($preferences['price_ranges'][$priceRange] ?? 0) + 1;
        }

        // Extract duration preferences
        if (preg_match('/(\d+)\s*ngày/i', $query, $matches)) {
            $days = intval($matches[1]);
            $durationRange = $this->getDurationRange($days);
            $preferences['durations'][$durationRange] =
                ($preferences['durations'][$durationRange] ?? 0) + 1;
        }

        // Extract keywords
        $keywords = ['biển', 'núi', 'thành phố', 'văn hóa', 'lịch sử', 'ẩm thực', 'nghỉ dưỡng', 'phiêu lưu'];
        foreach ($keywords as $keyword) {
            if (strpos($query, $keyword) !== false) {
                $preferences['keywords'][$keyword] =
                    ($preferences['keywords'][$keyword] ?? 0) + 1;
            }
        }
    }

    /**
     * Extract preferences from booking
     */
    private function extractPreferencesFromBooking($booking, &$preferences)
    {
        $tour = $booking->bookable;

        // Location preference
        if ($tour->location) {
            $preferences['locations'][$tour->location->id] =
                ($preferences['locations'][$tour->location->id] ?? 0) + 3; // Higher weight for bookings
        }

        // Travel type preference
        if ($tour->travelType) {
            $preferences['travel_types'][$tour->travelType->id] =
                ($preferences['travel_types'][$tour->travelType->id] ?? 0) + 3;
        }

        // Price preference
        $priceRange = $this->getPriceRange($booking->total_price);
        $preferences['price_ranges'][$priceRange] =
            ($preferences['price_ranges'][$priceRange] ?? 0) + 2;

        // Duration preference
        $durationRange = $this->getDurationRange($tour->days);
        $preferences['durations'][$durationRange] =
            ($preferences['durations'][$durationRange] ?? 0) + 2;

        // Seasonal preference
        $month = date('n', strtotime($booking->start_date));
        $season = $this->getSeason($month);
        $preferences['seasonal_preferences'][$season] =
            ($preferences['seasonal_preferences'][$season] ?? 0) + 1;
    }

    /**
     * Get base recommendations based on preferences
     */
    private function getBaseRecommendations($preferences)
    {
        $query = Tour::with([
            'location',
            'travelType',
            'images' => function ($q) {
                $q->where('is_primary', true);
            },
            'prices' => function ($q) {
                $q->orderBy('date', 'desc')->limit(1);
            },
            'reviews'
        ]);

        // Apply location preferences
        if (!empty($preferences['locations'])) {
            $topLocations = array_keys(array_slice($preferences['locations'], 0, 3, true));
            $query->whereIn('location_id', $topLocations);
        }

        // Apply travel type preferences
        if (!empty($preferences['travel_types'])) {
            $topTypes = array_keys(array_slice($preferences['travel_types'], 0, 2, true));
            $query->whereIn('travel_type_id', $topTypes);
        }

        // Apply duration preferences
        if (!empty($preferences['durations'])) {
            $preferredDurations = array_keys($preferences['durations']);
            $query->where(function ($q) use ($preferredDurations) {
                foreach ($preferredDurations as $range) {
                    [$min, $max] = $this->parseDurationRange($range);
                    $q->orWhereBetween('days', [$min, $max]);
                }
            });
        }

        $tours = $query->limit(10)->get();

        return $tours->map(function ($tour) {
            return $this->formatTourForRecommendation($tour);
        })->toArray();
    }

    /**
     * Get collaborative filtering recommendations
     */
    private function getCollaborativeRecommendations($userId, $preferences)
    {
        try {
            // Find similar users based on booking patterns
            $similarUsers = $this->findSimilarUsers($userId, $preferences);

            if (empty($similarUsers)) {
                return [];
            }

            // Get tours booked by similar users
            $recommendedTourIds = Booking::whereIn('user_id', $similarUsers)
                ->where('user_id', '!=', $userId)
                ->where('status', 'confirmed')
                ->groupBy('bookable_id')
                ->selectRaw('bookable_id, COUNT(*) as booking_count')
                ->orderBy('booking_count', 'desc')
                ->limit(5)
                ->pluck('bookable_id')
                ->toArray();

            if (empty($recommendedTourIds)) {
                return [];
            }

            $tours = Tour::whereIn('id', $recommendedTourIds)
                ->with([
                    'location',
                    'travelType',
                    'images' => function ($q) {
                        $q->where('is_primary', true);
                    },
                    'prices' => function ($q) {
                        $q->orderBy('date', 'desc')->limit(1);
                    },
                    'reviews'
                ])
                ->get();

            return $tours->map(function ($tour) {
                return $this->formatTourForRecommendation($tour);
            })->toArray();
        } catch (\Exception $e) {
            Log::error('Collaborative filtering failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            return [];
        }
    }

    /**
     * Find similar users based on preferences
     */
    private function findSimilarUsers($userId, $preferences)
    {
        // Simple similarity based on location and travel type preferences
        $userBookings = Booking::where('user_id', $userId)
            ->with('bookable')
            ->get();

        if ($userBookings->isEmpty()) {
            return [];
        }

        $userLocationIds = $userBookings->pluck('bookable.location_id')->filter()->unique()->toArray();
        $userTravelTypeIds = $userBookings->pluck('bookable.travel_type_id')->filter()->unique()->toArray();

        // Find users with similar booking patterns
        $similarUsers = Booking::whereHas('bookable', function ($q) use ($userLocationIds, $userTravelTypeIds) {
            $q->whereIn('location_id', $userLocationIds)
                ->orWhereIn('travel_type_id', $userTravelTypeIds);
        })
            ->where('user_id', '!=', $userId)
            ->where('status', 'confirmed')
            ->groupBy('user_id')
            ->selectRaw('user_id, COUNT(*) as similarity_score')
            ->orderBy('similarity_score', 'desc')
            ->limit(10)
            ->pluck('user_id')
            ->toArray();

        return $similarUsers;
    }

    /**
     * Merge and rank recommendations
     */
    private function mergeAndRankRecommendations($baseRecs, $collaborativeRecs, $preferences)
    {
        $allRecs = array_merge($baseRecs, $collaborativeRecs);

        // Remove duplicates
        $uniqueRecs = [];
        $seenIds = [];

        foreach ($allRecs as $rec) {
            if (!in_array($rec['id'], $seenIds)) {
                $uniqueRecs[] = $rec;
                $seenIds[] = $rec['id'];
            }
        }

        // Score and sort recommendations
        foreach ($uniqueRecs as &$rec) {
            $rec['recommendation_score'] = $this->calculateRecommendationScore($rec, $preferences);
        }

        usort($uniqueRecs, function ($a, $b) {
            return $b['recommendation_score'] <=> $a['recommendation_score'];
        });

        return array_slice($uniqueRecs, 0, 6);
    }

    /**
     * Calculate recommendation score
     */
    private function calculateRecommendationScore($tour, $preferences)
    {
        $score = 0;

        // Location preference score
        if (isset($preferences['locations'][$tour['location_id']])) {
            $score += $preferences['locations'][$tour['location_id']] * 3;
        }

        // Travel type preference score
        if (isset($preferences['travel_types'][$tour['travel_type_id']])) {
            $score += $preferences['travel_types'][$tour['travel_type_id']] * 2;
        }

        // Rating score
        $score += $tour['rating'] * 2;

        // Review count score (popularity)
        $score += min($tour['review_count'] / 10, 5);

        // Price preference score
        $tourPriceRange = $this->getPriceRange($tour['price']);
        if (isset($preferences['price_ranges'][$tourPriceRange])) {
            $score += $preferences['price_ranges'][$tourPriceRange];
        }

        return $score;
    }

    /**
     * Format tour for recommendation
     */
    private function formatTourForRecommendation($tour)
    {
        $latestPrice = $tour->prices->first();
        $avgRating = $tour->reviews->avg('rating') ?? 0;

        return [
            'id' => $tour->id,
            'name' => $tour->name,
            'location' => $tour->location ? $tour->location->name : '',
            'location_id' => $tour->location_id,
            'travel_type' => $tour->travelType ? $tour->travelType->name : '',
            'travel_type_id' => $tour->travel_type_id,
            'duration' => "{$tour->days} ngày {$tour->nights} đêm",
            'price' => $latestPrice ? $latestPrice->price : 0,
            'price_formatted' => $latestPrice ? number_format($latestPrice->price) . ' VNĐ' : 'Liên hệ',
            'image' => $tour->images->first() ? $tour->images->first()->image_url : null,
            'rating' => round($avgRating, 1),
            'review_count' => $tour->reviews->count(),
            'recommendation_reason' => $this->getRecommendationReason($tour)
        ];
    }

    /**
     * Get recommendation reason
     */
    private function getRecommendationReason($tour)
    {
        $reasons = [];

        $avgRating = $tour->reviews->avg('rating') ?? 0;
        if ($avgRating >= 4.5) {
            $reasons[] = 'Đánh giá cao';
        }

        if ($tour->reviews->count() > 20) {
            $reasons[] = 'Phổ biến';
        }

        $latestPrice = $tour->prices->first();
        if ($latestPrice && $latestPrice->price < 2000000) {
            $reasons[] = 'Giá tốt';
        }

        return implode(', ', $reasons) ?: 'Phù hợp với sở thích';
    }

    /**
     * Get popular tours as fallback
     */
    private function getPopularTours()
    {
        return Tour::with([
            'location',
            'travelType',
            'images' => function ($q) {
                $q->where('is_primary', true);
            },
            'prices' => function ($q) {
                $q->orderBy('date', 'desc')->limit(1);
            },
            'reviews'
        ])
            ->withCount('reviews')
            ->orderBy('reviews_count', 'desc')
            ->limit(6)
            ->get()
            ->map(function ($tour) {
                return $this->formatTourForRecommendation($tour);
            })
            ->toArray();
    }

    /**
     * Helper methods
     */
    private function getPriceRange($price)
    {
        if ($price < 1000000) return 'under_1m';
        if ($price < 3000000) return '1m_3m';
        if ($price < 5000000) return '3m_5m';
        if ($price < 10000000) return '5m_10m';
        return 'over_10m';
    }

    private function getDurationRange($days)
    {
        if ($days <= 2) return 'short';
        if ($days <= 5) return 'medium';
        return 'long';
    }

    private function parseDurationRange($range)
    {
        switch ($range) {
            case 'short':
                return [1, 2];
            case 'medium':
                return [3, 5];
            case 'long':
                return [6, 14];
            default:
                return [1, 14];
        }
    }

    private function getSeason($month)
    {
        if (in_array($month, [12, 1, 2])) return 'winter';
        if (in_array($month, [3, 4, 5])) return 'spring';
        if (in_array($month, [6, 7, 8])) return 'summer';
        return 'autumn';
    }

    private function normalizePreferences($preferences)
    {
        foreach ($preferences as $key => &$values) {
            if (is_array($values) && !empty($values)) {
                arsort($values);
                $values = array_slice($values, 0, 5, true);
            }
        }
        return $preferences;
    }
}
