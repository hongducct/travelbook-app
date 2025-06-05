<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Tour;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\ReviewResource;

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

        // Bắt đầu với query cơ bản
        $query = $user->wishlist();

        // Debug query trước khi filter
        Log::info('Query before type filter: ' . $query->toSql());
        Log::info('Bindings before type filter: ', $query->getBindings());

        // Filter theo type nếu có
        if ($type === 'blog') {
            $query = $query->where('favoritable_type', 'App\\Models\\News');
        } elseif ($type === 'tour') {
            $query = $query->where('favoritable_type', 'App\\Models\\Tour');
        }

        // Debug query sau khi filter
        Log::info('Query after type filter: ' . $query->toSql());
        Log::info('Bindings after type filter: ', $query->getBindings());

        // Thêm eager loading sau khi đã filter - SỬA LẠI PHẦN NÀY
        $query = $query->with(['favoritable' => function ($q) {
            // Load Tour với đầy đủ relations như TourController
            $q->when($q->getModel() instanceof Tour, function ($q) {
                $q->with([
                    'travelType',
                    'location',
                    'vendor',
                    'images', // Đảm bảo load images
                    'availabilities' => function ($query) {
                        $query->where('date', '>=', now()->toDateString())
                            ->where('is_active', true);
                    },
                    'features',
                    'reviews' => function ($query) {
                        $query->where('status', 'approved')->with('user:id,username,avatar');
                    },
                ]);
            })->when($q->getModel() instanceof News, function ($q) {
                $q->with(['vendor', 'images']); // Thêm images cho News nếu cần
            });
        }]);

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

            // Debug: Kiểm tra xem images có được load không
            Log::info('Favoritable data: ', $favoritable->toArray());
            if ($favoritable instanceof Tour) {
                Log::info('Tour images count: ' . $favoritable->images->count());
            }

            if ($favoritable instanceof Tour) {
                // Xử lý dữ liệu cho Tour giống như TourController

                // Get latest price - sử dụng relationship đã load
                $favoritable->price = $favoritable->prices()->orderBy('date', 'desc')->first()?->price;

                // Get travel type as category
                $favoritable->category = $favoritable->travelType?->name;

                // Get average rating and review count
                $favoritable->average_rating = $favoritable->reviews->avg('rating') ?? 0;
                $favoritable->review_count = $favoritable->reviews->count();

                // Transform reviews using ReviewResource (nếu có)
                if (class_exists('App\Http\Resources\ReviewResource')) {
                    $favoritable->reviews = ReviewResource::collection($favoritable->reviews);
                }

                // Thêm type
                $favoritable->type = 'tour';

                // QUAN TRỌNG: Giữ lại images và chỉ unset những gì không cần
                // Images đã được load qua eager loading nên sẽ có sẵn
                unset($favoritable->travelType);
                // Không unset prices vì cần để query, chỉ unset sau khi đã lấy price

            } elseif ($favoritable instanceof News) {
                // Xử lý dữ liệu cho Blog
                $favoritable->type = 'blog';
                $favoritable->excerpt = $this->getExcerpt($favoritable->content);
            }

            return $favoritable;
        });

        // Loại bỏ các giá trị null (nếu có tour/blog bị xóa)
        $wishlist->setCollection($wishlist->getCollection()->filter());

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
