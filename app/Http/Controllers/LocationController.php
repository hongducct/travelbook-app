<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Tour;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index()
    {
        $locations = Location::paginate(15); // Phân trang địa điểm
        return response()->json($locations);
    }

    public function tours($id)
    {
        // Kiểm tra địa điểm có tồn tại không
        $location = Location::findOrFail($id);

        // Lấy danh sách tour theo location_id và phân trang
        $tours = Tour::with(['vendor', 'location', 'images'])
            ->where('location_id', $id)
            ->paginate(10);

        // Thêm giá mới nhất và features cho mỗi tour
        $tours->getCollection()->transform(function ($tour) {
            $latestPrice = $tour->prices()->orderBy('date', 'desc')->first();
            $tour->price = $latestPrice?->price;
            $tour->features = $tour->features; // Nếu đã cast sẵn trong model
            unset($tour->prices); // Không trả về toàn bộ danh sách giá
            return $tour;
        });

        return response()->json([
            'location' => $location,
            'tours' => $tours,
        ]);
    }
}
