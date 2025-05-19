<?php

namespace App\Http\Controllers;

use App\Models\TravelType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TravelTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $query = TravelType::query();

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
        }

        $travelTypes = $query->get(); // Return all records instead of paginating

        return response()->json(['data' => $travelTypes]);
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
                'name' => 'required|string|max:255|unique:travel_types,name',
                'description' => 'nullable|string',
            ]);

            $travelType = TravelType::create($validated);

            return response()->json($travelType, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error: ', $e->errors());
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
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
        $travelType = TravelType::findOrFail($id);

        return response()->json($travelType);
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
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:travel_types,name,' . $id,
                'description' => 'nullable|string',
            ]);

            $travelType = TravelType::findOrFail($id);
            $travelType->update($validated);

            return response()->json($travelType);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error: ', $e->errors());
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
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
        $travelType = TravelType::findOrFail($id);

        // Check if travel type is associated with any tours
        if ($travelType->tours()->exists()) {
            return response()->json(['message' => 'Cannot delete travel type with associated tours'], 422);
        }

        $travelType->delete();

        return response()->json(['message' => 'Travel type deleted']);
    }
}