<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\ReviewResource;

class ReviewsController extends Controller
{
    /**
     * Display a listing of the reviews.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10); // Mặc định 10 nếu không gửi lên
        $status = $request->query('status'); // Nếu có lọc theo status

        $query = Review::with(['user', 'reviewable']);

        if ($status) {
            $query->where('status', $status);
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
            'user_id' => 'required|exists:users,id',
            'reviewable_id' => 'required|integer',
            'reviewable_type' => 'required|string|in:App\Models\Tour', // Chỉ chấp nhận Tour hiện tại
            'rating' => 'required|integer|between:1,5',
            'comment' => 'nullable|string|max:1000',
            'status' => 'required|in:approved,pending,rejected',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $review = Review::create($request->all());

        return response()->json($review->load(['user', 'reviewable']), 201);
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
        $validator = Validator::make($request->all(), [
            'rating' => 'sometimes|integer|between:1,5',
            'comment' => 'sometimes|nullable|string|max:1000',
            'status' => 'sometimes|in:approved,pending,rejected',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $review->update($request->only(['rating', 'comment', 'status']));

        return response()->json($review->load(['user', 'reviewable']));
    }

    /**
     * Remove the specified review from storage.
     *
     * @param  \App\Models\Review  $review
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Review $review)
    {
        $review->delete();

        return response()->json(['message' => 'Review deleted successfully']);
    }
}