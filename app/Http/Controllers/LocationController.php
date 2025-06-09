<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Tour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Location::query();

        // Apply filters
        if ($request->has('country')) {
            $query->where('country', $request->country);
        }
        if ($request->has('city')) {
            $query->where('city', $request->city);
        }
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Paginate results
        $locations = $query->orderBy('id', 'desc')->paginate(20); // Adjust per_page as needed

        return response()->json([
            'data' => $locations->items(),
            'meta' => [
                'current_page' => $locations->currentPage(),
                'last_page' => $locations->lastPage(),
                'total' => $locations->total(),
                'per_page' => $locations->perPage(),
            ],
            'links' => [
                'next' => $locations->nextPageUrl(),
                'prev' => $locations->previousPageUrl(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'country' => 'required|string|max:255',
                'city' => 'required|string|max:255',
                'description' => 'nullable|string',
                'image' => 'nullable|url',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
            ]);

            $location = Location::create($validated);

            return response()->json([
                'message' => 'Location created successfully',
                'data' => $location
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error: ', $e->errors());
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error creating location: ' . $e->getMessage());
            return response()->json(['message' => 'Error creating location'], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $location = Location::findOrFail($id);

            return response()->json([
                'message' => 'Location retrieved successfully',
                'data' => $location
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Location not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error retrieving location: ' . $e->getMessage());
            return response()->json(['message' => 'Error retrieving location'], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $location = Location::findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'country' => 'required|string|max:255',
                'city' => 'required|string|max:255',
                'description' => 'nullable|string',
                'image' => 'nullable|url',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
            ]);

            $location->update($validated);

            return response()->json([
                'message' => 'Location updated successfully',
                'data' => $location->fresh()
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Location not found'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error: ', $e->errors());
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error updating location: ' . $e->getMessage());
            return response()->json(['message' => 'Error updating location'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $location = Location::findOrFail($id);

            // Check if location has associated tours
            $toursCount = $location->tours()->count();
            if ($toursCount > 0) {
                return response()->json([
                    'message' => 'Cannot delete location with associated tours',
                    'tours_count' => $toursCount
                ], 400);
            }

            $location->delete();

            return response()->json([
                'message' => 'Location deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Location not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting location: ' . $e->getMessage());
            return response()->json(['message' => 'Error deleting location'], 500);
        }
    }

    /**
     * Get tour counts for multiple locations.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function tourCounts(Request $request)
    {
        $locationIds = $request->input('location_ids', []);

        // Fetch tour counts for the given location IDs
        $counts = Tour::whereIn('location_id', $locationIds)
            ->groupBy('location_id')
            ->selectRaw('location_id, COUNT(*) as total')
            ->pluck('total', 'location_id')
            ->toArray();

        // Ensure all requested location IDs are included, even if they have zero tours
        $result = array_fill_keys($locationIds, 0);
        foreach ($counts as $locationId => $total) {
            $result[$locationId] = $total;
        }

        return response()->json(['counts' => $result]);
    }

    /**
     * Display tours for a specific location.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function tours($id)
    {
        // Eager load relationships, including the latest price
        $tours = Tour::with([
            'vendor' => fn($query) => $query->select('id', 'company_name'),
            'location' => fn($query) => $query->select('id', 'name', 'city', 'country'),
            'travelType' => fn($query) => $query->select('id', 'name'),
            'images' => fn($query) => $query->select('id', 'tour_id', 'image_url')->where('is_primary', true),
            'prices' => fn($query) => $query->select('id', 'tour_id', 'price', 'date')
                ->orderBy('date', 'desc')
                ->take(1),
        ])
            ->where('location_id', $id)
            ->select('id', 'location_id', 'vendor_id', 'travel_type_id', 'name', 'description', 'days', 'nights')
            ->paginate(10);

        // Check if location exists
        $location = Location::find($id);
        if (!$location) {
            return response()->json(['message' => 'Location not found'], 404);
        }

        // Transform the collection to include only necessary data
        $tours->getCollection()->transform(function ($tour) {
            return [
                'id' => $tour->id,
                'name' => $tour->name,
                'description' => $tour->description,
                'days' => $tour->days,
                'nights' => $tour->nights,
                'price' => $tour->prices->first()->price ?? null,
                'category' => $tour->travelType->name ?? null,
                'vendor' => $tour->vendor ? ['company_name' => $tour->vendor->company_name] : null,
                'images' => $tour->images->pluck('image_url')->toArray(),
                'features' => $tour->features->pluck('name')->toArray(), // Include features from Tour model
            ];
        });

        return response()->json([
            'location' => $location,
            'tours' => $tours,
        ]);
    }
}
