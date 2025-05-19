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

        $query = Tour::with(['travelType', 'location', 'vendor', 'images', 'availabilities', 'features']);

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

        $tours = $query->paginate($perPage);

        // Transform the response
        $tours->getCollection()->transform(function ($tour) {
            $latestPrice = $tour->prices()->orderBy('date', 'desc')->first();
            $tour->price = $latestPrice?->price;
            $tour->category = $tour->travelType?->name;
            unset($tour->prices, $tour->travelType, $tour->features);
            return $tour;
        });

        return response()->json($tours);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $tour = Tour::with(['travelType', 'location', 'vendor', 'images', 'availabilities', 'features'])->findOrFail($id);

        $latestPrice = $tour->prices()->orderBy('date', 'desc')->first();
        $tour->price = $latestPrice ? $latestPrice->price : null;
        $tour->category = $tour->travelType?->name;
        unset($tour->prices, $tour->travelType, $tour->features);

        return response()->json($tour);
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
            ]);

            // Custom validation for available_slots <= max_guests
            foreach ($request->availabilities as $index => $availability) {
                if ($availability['available_slots'] > $availability['max_guests']) {
                    $validator = Validator::make($request->all(), []);
                    $validator->errors()->add("availabilities.$index.available_slots", "The availabilities-acceptance.$index.available_slots must be less than or equal to max guests.");
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

        // Create price in prices table
        $tour->prices()->create([
            'date' => now(),
            'price' => $validated['price'],
        ]);

        // Load relationships and transform response
        $tour->load(['travelType', 'location', 'vendor', 'images', 'availabilities', 'features']);
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
        $tour->load(['travelType', 'location', 'vendor', 'images', 'availabilities', 'features']);
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
