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
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Lấy giá trị perPage từ request, mặc định là 10 nếu không có
        $perPage = $request->input('perPage', 10);
        // Lấy giá trị search từ request
        $search = $request->input('search');

        // Khởi tạo query với các quan hệ
        $query = Tour::with(['vendor', 'location', 'images']);

        // Áp dụng tìm kiếm nếu có search query
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhereHas('location', function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        // Sử dụng paginate với perPage
        $tours = $query->paginate($perPage);

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
        Log::info('Request data: ', $request->all());
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'required|numeric',
                'days' => 'required|integer|min:1',
                'nights' => 'required|integer|min:0',
                'category' => 'required|string',
                'location_id' => 'required|exists:locations,id',
                'vendor_id' => 'required|exists:vendors,id',
                'features' => 'nullable|json',
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
            'price' => $validated['price'],
            'days' => $validated['days'],
            'nights' => $validated['nights'],
            'category' => $validated['category'],
            'location_id' => $validated['location_id'],
            'vendor_id' => $validated['vendor_id'],
            'features' => $validated['features'],
        ]);

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

        // Update tour price in prices table
        $tour->prices()->create([
            'date' => now(),
            'price' => $validated['price'],
        ]);

        return response()->json($tour->load('images', 'availabilities'), 201);
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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'days' => 'required|integer|min:1',
            'nights' => 'required|integer|min:0',
            'category' => 'required|string',
            'location_id' => 'required|exists:locations,id',
            'vendor_id' => 'required|exists:vendors,id',
            'features' => 'nullable|json',
            'images' => 'required|array',
            'images.*.id' => 'nullable|exists:tour_images,id',
            'images.*.image_url' => 'required|url',
            'images.*.caption' => 'nullable|string',
            'images.*.is_primary' => 'required|boolean',
            'availabilities' => 'required|array|min:1',
            'availabilities.*.id' => 'nullable|exists:tour_availabilities,id',
            'availabilities.*.date' => 'required|date|after_or_equal:today',
            'availabilities.*.max_guests' => 'required|integer|min:1',
            'availabilities.*.available_slots' => 'required|integer|min:0|lte:max_guests',
            'availabilities.*.is_active' => 'required|boolean',
        ]);

        $tour = Tour::findOrFail($id);
        $tour->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'price' => $validated['price'],
            'days' => $validated['days'],
            'nights' => $validated['nights'],
            'category' => $validated['category'],
            'location_id' => $validated['location_id'],
            'vendor_id' => $validated['vendor_id'],
            'features' => $validated['features'],
        ]);

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
        // so sánh price mới và cũ, nếu giống nhau thì cập nhật updated_at, nếu khác nhau thì tạo mới
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

        return response()->json($tour->load('images'), 200);
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
