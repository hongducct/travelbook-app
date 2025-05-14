<?php

namespace App\Http\Controllers;

use App\Models\Tour;
use Illuminate\Http\Request;

class TourController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Sử dụng paginate thay vì get, mặc định 15 items mỗi trang
        $tours = Tour::with(['vendor', 'location', 'images'])->paginate(15);

        // Thêm giá mới nhất vào mỗi tour
        $tours->getCollection()->map(function ($tour) {
            $latestPrice = $tour->prices()->orderBy('date', 'desc')->first();
            $tour->price = $latestPrice?->price;
            $tour->features = $tour->features; // Already cast to array in the model
            unset($tour->prices);
            return $tour;
        });

        return response()->json($tours);
    }
    public function show($id)
    {
        $tour = Tour::with(['vendor', 'location', 'availabilities', 'images'])->findOrFail($id);

        // Thêm giá mới nhất
        $latestPrice = $tour->prices()->orderBy('date', 'desc')->first();
        $tour->price = $latestPrice ? $latestPrice->price : null;
        // Không cần json_decode vì đã được cast sang array
        unset($tour->prices); // Xóa mảng prices

        return response()->json($tour);
    }

    public function getPrices($tourId)
    {
        $tour = Tour::findOrFail($tourId);
        $prices = $tour->prices()->orderBy('date', 'asc')->get(); // Sắp xếp theo ngày tăng dần

        return response()->json($prices);
    }
    
    // public function show($id)
    // {
    //     $tour = Tour::with(['vendor', 'location'])->find($id);

    //     if (!$tour) {
    //         return response()->json(['message' => 'Tour not found'], 404);
    //     }

    //     return response()->json($tour);
    // }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'location_id' => 'required|exists:locations,id',
            'name' => 'required|string',
            'description' => 'nullable|string',
            'days' => 'required|integer|min:1',
            'nights' => 'required|integer|min:0',
            'category' => 'required|string',
            'features' => 'nullable|string',
        ]);

        $tour = Tour::create($request->all());

        return response()->json($tour, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'vendor_id' => 'exists:vendors,id',
            'location_id' => 'exists:locations,id',
            'name' => 'string',
            'description' => 'nullable|string',
            'days' => 'integer|min:1',
            'nights' => 'integer|min:0',
            'category' => 'string',
            'features' => 'nullable|string',
        ]);

        $tour = Tour::find($id);

        if (!$tour) {
            return response()->json(['message' => 'Tour not found'], 404);
        }

        $tour->update($request->all());

        return response()->json($tour);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
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