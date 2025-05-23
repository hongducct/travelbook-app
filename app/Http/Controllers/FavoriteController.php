<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Tour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FavoriteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    // Lấy danh sách wishlist
    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 10);
        $user = $request->user();

        // Debug: Kiểm tra user và wishlist
        Log::info('User ID: ' . $user->id);

        // Lấy danh sách wishlist của user với mối quan hệ favoritable (Tour)
        $query = $user->wishlist()
            ->with(['favoritable' => function ($q) {
                $q->with(['travelType', 'location', 'vendor', 'images', 'availabilities', 'features', 'prices', 'reviews']);
            }]);

        // Phân trang
        $wishlist = $query->paginate($perPage);

        // Debug: Kiểm tra dữ liệu trước khi transform
        Log::info('Wishlist data before transform: ', $wishlist->items());

        // Debug: Kiểm tra truy vấn SQL
        Log::info('Raw wishlist query: ' . $query->toSql());
        Log::info('Raw wishlist bindings: ', $query->getBindings());

        // Transform dữ liệu
        $wishlist->getCollection()->transform(function ($favorite) {
            $tour = $favorite->favoritable;

            if (!$tour) {
                Log::warning('No favoritable found for favorite ID: ' . $favorite->id);
                return null;
            }

            // Get latest price
            $latestPrice = $tour->prices()->orderBy('date', 'desc')->first();
            $tour->price = $latestPrice?->price;

            // Get travel type as category
            $tour->category = $tour->travelType?->name;

            // Get average rating
            $avgRating = $tour->reviews()->where('status', 'approved')->avg('rating');
            $tour->average_rating = $avgRating;

            // Get review count
            $reviewCount = $tour->reviews()->where('status', 'approved')->count();
            $tour->review_count = $reviewCount;

            // Unset các mối quan hệ không cần thiết
            unset($tour->prices);
            unset($tour->travelType);

            return $tour; // Trả về tour đã được transform
        });

        // Loại bỏ các giá trị null (nếu có tour bị xóa)
        $wishlist->setCollection($wishlist->getCollection()->filter());

        // Debug: Kiểm tra dữ liệu sau khi transform
        Log::info('Wishlist data after transform: ', $wishlist->items());

        return response()->json($wishlist);
    }

    // Thêm tour vào wishlist
    public function store(Request $request)
    {
        $request->validate([
            'favoritable_id' => 'required|integer|exists:tours,id',
            'favoritable_type' => 'required|string|in:App\\Models\\Tour',
        ]);

        $user = $request->user();

        // Kiểm tra nếu đã có trong wishlist
        $exists = Favorite::where('user_id', $user->id)
            ->where('favoritable_id', $request->favoritable_id)
            ->where('favoritable_type', $request->favoritable_type)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Tour đã có trong danh sách yêu thích'], 400);
        }

        $favorite = Favorite::create([
            'user_id' => $user->id,
            'favoritable_id' => $request->favoritable_id,
            'favoritable_type' => $request->favoritable_type,
        ]);

        return response()->json(['message' => 'Đã thêm vào danh sách yêu thích']);
    }

    // Xóa tour khỏi wishlist
    public function destroy(Request $request, $tourId)
    {
        $user = $request->user();

        $favorite = Favorite::where('user_id', $user->id)
            ->where('favoritable_id', $tourId)
            ->where('favoritable_type', 'App\\Models\\Tour')
            ->first();

        if (!$favorite) {
            return response()->json(['message' => 'Tour không có trong danh sách yêu thích'], 404);
        }

        $favorite->delete();

        return response()->json(['message' => 'Đã xóa khỏi danh sách yêu thích']);
    }

    // Kiểm tra nếu tour có trong wishlist
    public function check(Request $request, $tourId)
    {
        $user = $request->user();

        $isInWishlist = Favorite::where('user_id', $user->id)
            ->where('favoritable_id', $tourId)
            ->where('favoritable_type', 'App\\Models\\Tour')
            ->exists();

        return response()->json(['isInWishlist' => $isInWishlist]);
    }
}
