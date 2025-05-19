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
    public function index()
    {
        $locations = Location::all(); // Return all locations as an array
        return response()->json(['data' => $locations]);
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

            return response()->json($location, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error: ', $e->errors());
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        }
    }

    /**
     * Display tours for a specific location.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function tours($id)
    {
        // Kiểm tra địa điểm có tồn tại không
        $location = Location::findOrFail($id);

        // Lấy danh sách tour theo location_id và phân trang
        $tours = Tour::with(['vendor', 'location', 'travelType', 'images'])
            ->where('location_id', $id)
            ->paginate(10);

        // Thêm giá mới nhất, category, và features cho mỗi tour
        $tours->getCollection()->transform(function ($tour) {
            $latestPrice = $tour->prices()->orderBy('date', 'desc')->first();
            $tour->price = $latestPrice?->price;
            $tour->category = $tour->travelType?->name;
            $tour->features = $tour->features;
            unset($tour->prices, $tour->travelType);
            return $tour;
        });

        return response()->json([
            'location' => $location,
            'tours' => $tours,
        ]);
    }
}