<?php

namespace App\Http\Controllers;

use App\Models\TourAvailability;
use Illuminate\Http\Request;

class TourAvailabilityController extends Controller
{
    public function index(Request $request)
    {
        $availabilities = TourAvailability::where('is_active', true)
            ->where('date', '>=', now()->toDateString())
            ->select('date')
            ->distinct()
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                $tours = TourAvailability::where('date', $item->date)
                    ->where('is_active', true)
                    ->pluck('tour_id');
                return [
                    'date' => $item->date,
                    'tour_ids' => $tours,
                ];
            });

        return response()->json($availabilities);
    }
}
