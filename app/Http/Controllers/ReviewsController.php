<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\ReviewResource;
use Illuminate\Support\Facades\Log;

class ReviewsController extends Controller
{   
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show']);
    }
    /**
     * Display a listing of the reviews.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10);
        $status = $request->query('status');
        $type = $request->query('type'); // Filter by reviewable_type (tour or blog)

        $query = Review::with(['user', 'reviewable']);

        if ($status) {
            $query->where('status', $status);
        }

        if ($type === 'blog') {
            $query->where('reviewable_type', 'App\\Models\\News');
        } elseif ($type === 'tour') {
            $query->where('reviewable_type', 'App\\Models\\Tour');
        }

        $reviews = $query->paginate($perPage);

        return ReviewResource::collection($reviews);
    }

    /**
     * Store a newly created review in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reviewable_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) use ($request) {
                    $type = $request->input('reviewable_type');
                    if ($type === 'App\\Models\\Tour') {
                        if (!\App\Models\Tour::where('id', $value)->exists()) {
                            $fail('The selected tour does not exist.');
                        }
                    } elseif ($type === 'App\\Models\\News') {
                        if (!\App\Models\News::where('id', $value)->exists()) {
                            $fail('The selected blog does not exist.');
                        }
                    } else {
                        $fail('Invalid reviewable type.');
                    }
                },
            ],
            'reviewable_type' => 'required|string|in:App\\Models\\Tour,App\\Models\\News',
            'booking_id' => [
                'nullable',
                'integer',
                'exists:bookings,id',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->input('reviewable_type') === 'App\\Models\\News' && $value) {
                        $fail('Booking ID is not applicable for blog reviews.');
                    }
                },
            ],
            'title' => 'nullable|string|max:255',
            'rating' => 'required|integer|between:1,5',
            'comment' => 'nullable|string|max:1000',
            'status' => 'required|in:approved,pending,rejected',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $review = Review::create([
            'user_id' => $request->user()->id,
            'reviewable_id' => $request->reviewable_id,
            'reviewable_type' => $request->reviewable_type,
            'booking_id' => $request->booking_id,
            'title' => $request->title,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'status' => $request->status,
        ]);

        return response()->json(new ReviewResource($review->load(['user', 'reviewable'])), 201);
    }

    /**
     * Update the specified review in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Review  $review
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Review $review)
    {
        Log::info(auth()->user()); // trong ReviewsController@update

        $isAdmin = Auth::guard('admin-token')->check();
        Log::info('isAdmin: ' . ($isAdmin ? 'true' : 'false'));
        $user = $isAdmin
            ? Auth::guard('admin-token')->user()
            : $request->user();

        if (!$isAdmin && (!$user || $user->id !== $review->user_id)) {
            return response()->json(['message' => 'Bạn không có quyền sửa đánh giá này'], 403);
        }

        $validator = Validator::make($request->all(), [
            'booking_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:bookings,id',
                function ($attribute, $value, $fail) use ($review) {
                    if ($review->reviewable_type === 'App\\Models\\News' && $value) {
                        $fail('Booking ID không áp dụng cho đánh giá blog.');
                    }
                },
            ],
            'title' => 'sometimes|nullable|string|max:255',
            'rating' => 'sometimes|integer|between:1,5',
            'comment' => 'sometimes|nullable|string|max:1000',
        ] + ($isAdmin ? [
            'status' => 'sometimes|in:approved,pending,rejected',
        ] : []));

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only(['booking_id', 'title', 'rating', 'comment']);
        if ($isAdmin && $request->has('status')) {
            $data['status'] = $request->input('status');
        }

        $review->update($data);

        return response()->json(new ReviewResource($review->load(['user', 'reviewable'])));
    }

    /**
     * Remove the specified review from storage.
     *
     * @param  \App\Models\Review  $review
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, Review $review)
    {
        if ($request->user()->id !== $review->user_id) {
            return response()->json(['message' => 'Unauthorized to delete this review'], 403);
        }

        $review->delete();

        return response()->json(['message' => 'Review deleted successfully']);
    }
}
