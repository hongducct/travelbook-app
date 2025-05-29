<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Tour;
use App\Models\News;
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
        $type = $request->input('type'); // Thêm tham số type để filter
        $user = $request->user();

        // Debug: Kiểm tra user và wishlist
        Log::info('User ID: ' . $user->id);
        Log::info('Filter type: ' . $type);

        // Bắt đầu với query cơ bản
        $query = $user->wishlist();

        // Debug query trước khi filter
        Log::info('Query before type filter: ' . $query->toSql());
        Log::info('Bindings before type filter: ', $query->getBindings());

        // Filter theo type nếu có - SỬA LẠI PHẦN NÀY
        if ($type === 'blog') {
            $query = $query->where('favoritable_type', 'App\\Models\\News');
        } elseif ($type === 'tour') {
            $query = $query->where('favoritable_type', 'App\\Models\\Tour');
        }

        // Debug query sau khi filter
        Log::info('Query after type filter: ' . $query->toSql());
        Log::info('Bindings after type filter: ', $query->getBindings());

        // Thêm eager loading sau khi đã filter
        $query = $query->with(['favoritable' => function ($q) {
            // Kiểm tra loại favoritable để load các mối quan hệ phù hợp
            $q->when($q->getModel() instanceof Tour, function ($q) {
                $q->with(['travelType', 'location', 'vendor', 'images', 'availabilities', 'features', 'prices', 'reviews']);
            })->when($q->getModel() instanceof News, function ($q) {
                $q->with(['vendor']); // Load các mối quan hệ cần thiết cho Blog
            });
        }]);

        // Kiểm tra dữ liệu thô trước khi paginate
        $rawData = $query->get();
        Log::info('Raw data count: ' . $rawData->count());
        Log::info('Raw data: ', $rawData->toArray());

        // Phân trang
        $wishlist = $query->paginate($perPage);

        // Debug: Kiểm tra dữ liệu trước khi transform
        Log::info('Wishlist data before transform: ', $wishlist->items());

        // Transform dữ liệu
        $wishlist->getCollection()->transform(function ($favorite) {
            $favoritable = $favorite->favoritable;

            if (!$favoritable) {
                Log::warning('No favoritable found for favorite ID: ' . $favorite->id);
                return null;
            }

            if ($favoritable instanceof Tour) {
                // Xử lý dữ liệu cho Tour
                $latestPrice = $favoritable->prices()->orderBy('date', 'desc')->first();
                $favoritable->price = $latestPrice?->price;
                $favoritable->category = $favoritable->travelType?->name;
                $avgRating = $favoritable->reviews()->where('status', 'approved')->avg('rating');
                $favoritable->average_rating = $avgRating;
                $reviewCount = $favoritable->reviews()->where('status', 'approved')->count();
                $favoritable->review_count = $reviewCount;
                $favoritable->type = 'tour'; // Thêm type
                unset($favoritable->prices);
                unset($favoritable->travelType);
            } elseif ($favoritable instanceof News) {
                // Xử lý dữ liệu cho Blog
                $favoritable->type = 'blog'; // Thêm type để frontend phân biệt
                $favoritable->excerpt = $this->getExcerpt($favoritable->content);
            }

            return $favoritable;
        });

        // Loại bỏ các giá trị null (nếu có tour/blog bị xóa)
        $wishlist->setCollection($wishlist->getCollection()->filter());

        // Debug: Kiểm tra dữ liệu sau khi transform
        Log::info('Wishlist data after transform: ', $wishlist->items());

        return response()->json($wishlist);
    }

    // Hàm helper để tạo excerpt cho blog
    private function getExcerpt($content)
    {
        if (!$content) return '';
        $plainText = strip_tags($content);
        return strlen($plainText) > 150 ? substr($plainText, 0, 150) . '...' : $plainText;
    }

    // Thêm vào wishlist
    public function store(Request $request)
    {
        $request->validate([
            'favoritable_id' => 'required|integer',
            'favoritable_type' => 'required|string|in:App\\Models\\Tour,App\\Models\\News',
        ]);

        $user = $request->user();

        // Kiểm tra nếu đã có trong wishlist
        $exists = Favorite::where('user_id', $user->id)
            ->where('favoritable_id', $request->favoritable_id)
            ->where('favoritable_type', $request->favoritable_type)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Đã có trong danh sách yêu thích'], 400);
        }

        $favorite = Favorite::create([
            'user_id' => $user->id,
            'favoritable_id' => $request->favoritable_id,
            'favoritable_type' => $request->favoritable_type,
        ]);

        return response()->json(['message' => 'Đã thêm vào danh sách yêu thích']);
    }

    // Xóa khỏi wishlist
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $favorite = Favorite::where('user_id', $user->id)
            ->where('favoritable_id', $id)
            ->whereIn('favoritable_type', ['App\\Models\\Tour', 'App\\Models\\News'])
            ->first();

        if (!$favorite) {
            return response()->json(['message' => 'Không có trong danh sách yêu thích'], 404);
        }

        $favorite->delete();

        return response()->json(['message' => 'Đã xóa khỏi danh sách yêu thích']);
    }

    // Kiểm tra nếu item có trong wishlist
    public function check(Request $request, $id)
    {
        $user = $request->user();

        $isInWishlist = Favorite::where('user_id', $user->id)
            ->where('favoritable_id', $id)
            ->whereIn('favoritable_type', ['App\\Models\\Tour', 'App\\Models\\News'])
            ->exists();

        return response()->json(['isInWishlist' => $isInWishlist]);
    }
}
