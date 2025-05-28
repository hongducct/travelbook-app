<?php

namespace App\Http\Controllers;

use App\Models\Tour;
use App\Models\TourImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TourController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 10);
        $search = $request->input('search');
        $featured = $request->input('featured');

        $query = Tour::with(['travelType', 'location', 'vendor', 'images', 'availabilities', 'features']);

        // Add search functionality
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhereHas('location', function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('travelType', function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        // Add option to get featured tours (most booked/reviewed)
        if ($featured) {
            // Example: Get tours with most reviews
            $query->withCount('reviews')
                ->orderBy('reviews_count', 'desc');
        }

        $tours = $query->paginate($perPage);

        // Transform the response
        $tours->getCollection()->transform(function ($tour) {
            // Get latest price
            $latestPrice = $tour->prices()->orderBy('date', 'desc')->first();
            $tour->price = $latestPrice?->price;

            // Get travel type as category
            $tour->category = $tour->travelType?->name;

            // Get average rating
            $avgRating = $tour->reviews()->where('status', 'approved')->avg('rating');
            $tour->average_rating = $avgRating;

            // Get review count
            $reviewCount = $tour->reviews()->where('status', 'approved')->count();
            $tour->review_count = $reviewCount;

            unset($tour->prices);
            unset($tour->travelType);

            return $tour;
        });

        return response()->json($tours);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $tour = Tour::with([
            'travelType',
            'location',
            'vendor',
            'images',
            'availabilities',
            'features',
            'itineraries' => function ($query) {
                $query->with('images')->orderBy('day');
            },
            'reviews' => function ($query) {
                $query->where('status', 'approved')
                    ->with('user:id,username,avatar');
            }
        ])->findOrFail($id);

        // Get latest price
        $latestPrice = $tour->prices()->orderBy('date', 'desc')->first();
        $tour->price = $latestPrice ? $latestPrice->price : null;

        // Get travel type as category
        $tour->category = $tour->travelType?->name;

        // Calculate average rating
        $tour->average_rating = $tour->reviews->avg('rating');

        unset($tour->prices);
        unset($tour->travelType);

        return response()->json($tour);
    }

    /**
     * Get reviews for a tour.
     */
    public function getReviews($tourId)
    {
        $tour = Tour::findOrFail($tourId);
        $reviews = $tour->reviews()
            ->where('status', 'approved')
            ->with('user:id,name,avatar')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($reviews);
    }

    /**
     * Get prices for a tour.
     */
    public function getPrices($tourId)
    {
        $tour = Tour::findOrFail($tourId);
        $prices = $tour->prices()->orderBy('date', 'asc')->get();

        return response()->json($prices);
    }

    /**
     * Get itineraries for a tour.
     */
    public function getItineraries($tourId)
    {
        $tour = Tour::findOrFail($tourId);
        $itineraries = $tour->itineraries()
            ->with('images')
            ->orderBy('day')
            ->get();

        return response()->json($itineraries);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::info('Request data: ', $request->all());
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'required|numeric|min:0',
                'days' => 'required|integer|min:1',
                'nights' => 'required|integer|min:0',
                'travel_type_id' => 'required|exists:travel_types,id',
                'location_id' => 'required|exists:locations,id',
                'vendor_id' => 'required|exists:vendors,id',
                'features' => 'nullable|array',
                'features.*' => 'exists:features,id',
                'images' => [
                    'required',
                    'array',
                    function ($attribute, $value, $fail) {
                        $primaryCount = array_sum(array_column($value, 'is_primary'));
                        if ($primaryCount !== 1) {
                            $fail('Exactly one image must be set as primary.');
                        }
                    },
                ],
                'images.*.image_url' => 'required|url',
                'images.*.caption' => 'nullable|string',
                'images.*.is_primary' => 'required|boolean',
                'availabilities' => 'required|array|min:1',
                'availabilities.*.date' => 'required|date|after_or_equal:today',
                'availabilities.*.max_guests' => 'required|integer|min:1',
                'availabilities.*.available_slots' => 'required|integer|min:0',
                'availabilities.*.is_active' => 'required|boolean',
                'itineraries' => 'nullable|array',
                'itineraries.*.day' => 'required|integer|min:1',
                'itineraries.*.title' => 'required|string|max:255',
                'itineraries.*.description' => 'nullable|string',
                'itineraries.*.activities' => 'nullable|array',
                'itineraries.*.accommodation' => 'nullable|string',
                'itineraries.*.meals' => 'nullable|string',
                'itineraries.*.start_time' => 'nullable|date_format:H:i',
                'itineraries.*.end_time' => 'nullable|date_format:H:i',
                'itineraries.*.notes' => 'nullable|string',
            ]);

            // Custom validation for available_slots <= max_guests
            foreach ($request->availabilities as $index => $availability) {
                if ($availability['available_slots'] > $availability['max_guests']) {
                    $validator = Validator::make($request->all(), []);
                    $validator->errors()->add("availabilities.$index.available_slots", "The availabilities.$index.available_slots must be less than or equal to max guests.");
                    throw new \Illuminate\Validation\ValidationException($validator);
                }
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error: ', $e->errors());
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        }

        $tour = Tour::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'days' => $validated['days'],
            'nights' => $validated['nights'],
            'travel_type_id' => $validated['travel_type_id'],
            'location_id' => $validated['location_id'],
            'vendor_id' => $validated['vendor_id'],
        ]);

        // Sync features to feature_tour pivot table
        if (!empty($validated['features'])) {
            $tour->features()->sync($validated['features']);
        }

        // Create images
        foreach ($validated['images'] as $image) {
            $tour->images()->create([
                'image_url' => $image['image_url'],
                'caption' => $image['caption'],
                'is_primary' => $image['is_primary'],
            ]);
        }

        // Create availabilities
        foreach ($validated['availabilities'] as $avail) {
            $tour->availabilities()->create([
                'date' => $avail['date'],
                'max_guests' => $avail['max_guests'],
                'available_slots' => $avail['available_slots'],
                'is_active' => $avail['is_active'],
            ]);
        }

        // Create itineraries
        if (!empty($validated['itineraries'])) {
            foreach ($validated['itineraries'] as $itinerary) {
                $tour->itineraries()->create([
                    'day' => $itinerary['day'],
                    'title' => $itinerary['title'],
                    'description' => $itinerary['description'] ?? null,
                    'activities' => $itinerary['activities'] ?? null,
                    'accommodation' => $itinerary['accommodation'] ?? null,
                    'meals' => $itinerary['meals'] ?? null,
                    'start_time' => $itinerary['start_time'] ?? null,
                    'end_time' => $itinerary['end_time'] ?? null,
                    'notes' => $itinerary['notes'] ?? null,
                ]);
            }
        }

        // Create price in prices table
        $tour->prices()->create([
            'date' => now(),
            'price' => $validated['price'],
        ]);

        // Load relationships and transform response
        $tour->load(['travelType', 'location', 'vendor', 'images', 'availabilities', 'features', 'itineraries']);
        $tour->category = $tour->travelType?->name;
        unset($tour->travelType, $tour->features);

        return response()->json($tour, 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'required|numeric|min:0',
                'days' => 'required|integer|min:1',
                'nights' => 'required|integer|min:0',
                'travel_type_id' => 'required|exists:travel_types,id',
                'location_id' => 'required|exists:locations,id',
                'vendor_id' => 'required|exists:vendors,id',
                'features' => 'nullable|array',
                'features.*' => 'exists:features,id',
                'images' => [
                    'required',
                    'array',
                    function ($attribute, $value, $fail) {
                        $primaryCount = array_sum(array_column($value, 'is_primary'));
                        if ($primaryCount !== 1) {
                            $fail('Exactly one image must be set as primary.');
                        }
                    },
                ],
                'images.*.id' => 'nullable|exists:tour_images,id',
                'images.*.image_url' => 'required|url',
                'images.*.caption' => 'nullable|string',
                'images.*.is_primary' => 'required|boolean',
                'availabilities' => 'required|array|min:1',
                'availabilities.*.id' => 'nullable|exists:tour_availabilities,id',
                'availabilities.*.date' => 'required|date|after_or_equal:today',
                'availabilities.*.max_guests' => 'required|integer|min:1',
                'availabilities.*.available_slots' => 'required|integer|min:0',
                'availabilities.*.is_active' => 'required|boolean',
                'itineraries' => 'nullable|array',
                'itineraries.*.id' => 'nullable|exists:itineraries,id',
                'itineraries.*.day' => 'required|integer|min:1',
                'itineraries.*.title' => 'required|string|max:255',
                'itineraries.*.description' => 'nullable|string',
                'itineraries.*.activities' => 'nullable|array',
                'itineraries.*.accommodation' => 'nullable|string',
                'itineraries.*.meals' => 'nullable|string',
                'itineraries.*.start_time' => 'nullable|date_format:H:i',
                'itineraries.*.end_time' => 'nullable|date_format:H:i',
                'itineraries.*.notes' => 'nullable|string',
            ]);

            // Custom validation for available_slots <= max_guests
            foreach ($request->availabilities as $index => $availability) {
                if ($availability['available_slots'] > $availability['max_guests']) {
                    $validator = Validator::make($request->all(), []);
                    $validator->errors()->add("availabilities.$index.available_slots", "The availabilities.$index.available_slots must be less than or equal to max guests.");
                    throw new \Illuminate\Validation\ValidationException($validator);
                }
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error: ', $e->errors());
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        }

        $tour = Tour::findOrFail($id);
        $tour->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'days' => $validated['days'],
            'nights' => $validated['nights'],
            'travel_type_id' => $validated['travel_type_id'],
            'location_id' => $validated['location_id'],
            'vendor_id' => $validated['vendor_id'],
        ]);

        // Sync features to feature_tour pivot table
        if (isset($validated['features'])) {
            $tour->features()->sync($validated['features']);
        } else {
            $tour->features()->detach();
        }

        // Handle images
        $existingImageIds = $tour->images->pluck('id')->toArray();
        $newImageIds = array_filter(array_column($validated['images'], 'id'));

        // Delete removed images
        TourImage::where('tour_id', $tour->id)
            ->whereNotIn('id', $newImageIds)
            ->delete();

        // Update or create images
        foreach ($validated['images'] as $image) {
            TourImage::updateOrCreate(
                ['id' => $image['id'], 'tour_id' => $tour->id],
                [
                    'image_url' => $image['image_url'],
                    'caption' => $image['caption'],
                    'is_primary' => $image['is_primary'],
                ]
            );
        }

        // Handle itineraries
        if (isset($validated['itineraries'])) {
            $existingItineraryIds = $tour->itineraries->pluck('id')->toArray();
            $newItineraryIds = array_filter(array_column($validated['itineraries'], 'id'));

            // Delete removed itineraries
            $tour->itineraries()->whereNotIn('id', $newItineraryIds)->delete();

            // Update or create itineraries
            foreach ($validated['itineraries'] as $itinerary) {
                $tour->itineraries()->updateOrCreate(
                    ['id' => $itinerary['id'] ?? null, 'tour_id' => $tour->id],
                    [
                        'day' => $itinerary['day'],
                        'title' => $itinerary['title'],
                        'description' => $itinerary['description'] ?? null,
                        'activities' => $itinerary['activities'] ?? null,
                        'accommodation' => $itinerary['accommodation'] ?? null,
                        'meals' => $itinerary['meals'] ?? null,
                        'start_time' => $itinerary['start_time'] ?? null,
                        'end_time' => $itinerary['end_time'] ?? null,
                        'notes' => $itinerary['notes'] ?? null,
                    ]
                );
            }
        }

        // Handle price update
        $latestPrice = $tour->prices()->orderBy('date', 'desc')->first();
        if ($latestPrice && $latestPrice->price == $validated['price']) {
            $latestPrice->touch();
        } else {
            $tour->prices()->create([
                'date' => now(),
                'price' => $validated['price'],
            ]);
        }

        // Handle availabilities
        $existingAvailabilityIds = $tour->availabilities->pluck('id')->toArray();
        $newAvailabilityIds = array_filter(array_column($validated['availabilities'], 'id'));

        // Delete removed availabilities
        $tour->availabilities()->whereNotIn('id', $newAvailabilityIds)->delete();

        // Update or create availabilities
        foreach ($validated['availabilities'] as $availability) {
            $tour->availabilities()->updateOrCreate(
                ['id' => $availability['id'], 'tour_id' => $tour->id],
                [
                    'date' => $availability['date'],
                    'max_guests' => $availability['max_guests'],
                    'available_slots' => $availability['available_slots'],
                    'is_active' => $availability['is_active'],
                ]
            );
        }

        // Load relationships and transform response
        $tour->load(['travelType', 'location', 'vendor', 'images', 'availabilities', 'features', 'itineraries']);
        $tour->category = $tour->travelType?->name;
        unset($tour->travelType, $tour->features);

        return response()->json($tour);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $tour = Tour::find($id);

        if (!$tour) {
            return response()->json(['message' => 'Tour not found'], 404);
        }

        $tour->delete();

        return response()->json(['message' => 'Tour deleted']);
    }
}
