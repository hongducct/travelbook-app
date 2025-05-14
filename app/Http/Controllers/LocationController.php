<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index()
    {
        $locations = Location::paginate(15); // Thêm phân trang với 15 mục mỗi trang
        return response()->json($locations);
    }

    public function tours($id)
    {
        $location = Location::with(['tours' => function ($query) {
            $query->paginate(10); // Thêm phân trang với 10 mục mỗi trang
        }])->findOrFail($id);

        return response()->json([
            'location' => $location,
            'tours' => $location->tours,
        ]);
    }
}
