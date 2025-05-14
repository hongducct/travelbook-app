<?php

namespace App\Http\Controllers;

use App\Models\TourAvailability;
use Illuminate\Http\Request;

class TourAvailabilityController extends Controller
{
    public function index(Request $request)
    {
        $tourId = $request->query('tour_id');
        $availabilities = TourAvailability::where('tour_id', $tourId)
            ->where('is_active', true)
            ->where('available_slots', '>', 0)
            ->get();

        return response()->json($availabilities);
    }
}