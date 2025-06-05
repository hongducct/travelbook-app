<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\Vendor;
use App\Http\Resources\NewsResource;
use App\Http\Resources\ReviewResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NewsController extends Controller
{
    /**
     * Get list of news with enhanced filtering
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('perPage', 12);
            $search = $request->input('search');
            $status = $request->input('status');
            $featured = $request->input('featured');
            $categoryId = $request->input('category_id');
            $destination = $request->input('destination');
            $season = $request->input('season');
            $tags = $request->input('tags');
            $sort = $request->input('sort', 'latest');

            $query = News::with([
                'vendor.user',
                'admin',
                'category',
                'reviews' => function ($query) {
                    $query->where('status', 'approved')->with('user:id,username,avatar');
                },
            ]);

            // Apply search
            if ($search) {
                $query->search($search);
            }

            // Apply filters
            if ($categoryId) {
                $query->where('category_id', $categoryId);
            }

            if ($destination) {
                $query->byDestination($destination);
            }

            if ($season) {
                $query->bySeason($season);
            }

            if ($tags) {
                $query->byTags($tags);
            }

            // Apply access control
            $isAdmin = Auth::guard('admin-token')->check();
            $isVendor = Auth::guard('sanctum')->check();

            if ($isAdmin) {
                // Admin can see all
            } elseif ($isVendor) {
                $user = Auth::guard('sanctum')->user();
                $vendor = Vendor::where('user_id', $user->id)->first();

                if ($vendor) {
                    $query->where(function ($q) use ($vendor) {
                        $q->where('blog_status', 'published')
                            ->orWhere(function ($subQ) use ($vendor) {
                                $subQ->where('author_type', 'vendor')
                                    ->where('vendor_id', $vendor->id);
                            });
                    });
                } else {
                    $query->published();
                }
            } else {
                $query->published();
            }

            // Apply status filter
            if ($status) {
                $query->status($status);
            }

            // Apply sorting
            switch ($sort) {
                case 'featured':
                    $query->featured()->orderBy('published_at', 'desc');
                    break;
                case 'popular':
                    $query->orderBy('view_count', 'desc');
                    break;
                case 'trending':
                    $query->where('created_at', '>=', now()->subDays(7))
                        ->orderBy('view_count', 'desc');
                    break;
                case 'oldest':
                    $query->orderBy('published_at', 'asc');
                    break;
                default: // latest
                    $query->orderBy('created_at', 'desc');
            }

            $news = $query->paginate($perPage);

            // Transform the response
            // Transform the response
            $news->getCollection()->transform(function ($newsItem) {
                // Get average rating and review count
                $newsItem->average_rating = $newsItem->reviews->avg('rating') ?? 0;
                $newsItem->review_count = $newsItem->reviews->count();

                // Transform reviews using ReviewResource
                $newsItem->reviews = ReviewResource::collection($newsItem->reviews);

                return $newsItem;
            });

            return NewsResource::collection($news);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Lỗi server: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show specific news item
     */
    public function show($id)
    {
        try {
            $news = News::with([
                'vendor.user',
                'admin',
                'category',
                'reviews' => function ($query) {
                    $query->where('status', 'approved')->with('user:id,username,avatar');
                },
            ])->findOrFail($id);

            // Check access permissions
            $isAdmin = Auth::guard('admin-token')->check();
            $isVendor = Auth::guard('sanctum')->check();
            $canView = false;

            if ($news->blog_status === 'published') {
                $canView = true;
            } elseif ($isAdmin) {
                $canView = true;
            } elseif ($isVendor) {
                $user = Auth::guard('sanctum')->user();
                $vendor = Vendor::where('user_id', $user->id)->first();

                if ($vendor && $news->author_type === 'vendor' && $news->vendor_id === $vendor->id) {
                    $canView = true;
                }
            }

            if (!$canView) {
                return response()->json([
                    'message' => 'Không có quyền truy cập blog này'
                ], 403);
            }

            // Track view
            $userId = $isVendor ? Auth::guard('sanctum')->id() : null;
            $adminId = $isAdmin ? Auth::guard('admin-token')->id() : null;

            $news->incrementViewCount(
                request()->ip(),
                request()->userAgent(),
                $userId,
                $adminId
            );

            // Transform the news item
            $news->average_rating = $news->reviews->avg('rating') ?? 0;
            $news->review_count = $news->reviews->count();
            $news->reviews = ReviewResource::collection($news->reviews);

            return new NewsResource($news);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Không tìm thấy blog: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Create new news
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'content' => 'nullable|string',
                'excerpt' => 'nullable|string|max:500',
                'image' => 'nullable|string',
                'category_id' => 'nullable|exists:news_categories,id',
                'tags' => 'nullable|array',
                'published_at' => 'nullable|date',
                'blog_status' => 'nullable|in:draft,pending,rejected,published,archived',
                'is_featured' => 'nullable|boolean',
                'meta_description' => 'nullable|string|max:160',
                'meta_keywords' => 'nullable|string',
                'destination' => 'nullable|string|max:255',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'travel_season' => 'nullable|in:spring,summer,autumn,winter,all_year',
                'travel_tips' => 'nullable|array',
                'estimated_budget' => 'nullable|numeric|min:0',
                'duration_days' => 'nullable|integer|min:1|max:365',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            $isAdmin = Auth::guard('admin-token')->check();
            $isVendor = Auth::guard('sanctum')->check();

            if (!$isAdmin && !$isVendor) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $newsData = $request->only([
                'title',
                'content',
                'excerpt',
                'image',
                'category_id',
                'tags',
                'published_at',
                'is_featured',
                'meta_description',
                'meta_keywords',
                'destination',
                'latitude',
                'longitude',
                'travel_season',
                'travel_tips',
                'estimated_budget',
                'duration_days'
            ]);

            $newsData['blog_status'] = $request->blog_status ?? 'draft';

            if ($isAdmin) {
                $admin = Auth::guard('admin-token')->user();
                $newsData['author_type'] = 'admin';
                $newsData['admin_id'] = $admin->id;
                $newsData['vendor_id'] = null;
            } else {
                $user = Auth::guard('sanctum')->user();
                $vendor = Vendor::where('user_id', $user->id)->first();

                if (!$vendor) {
                    return response()->json(['message' => 'Không tìm thấy thông tin vendor'], 404);
                }

                $newsData['author_type'] = 'vendor';
                $newsData['vendor_id'] = $vendor->id;
                $newsData['admin_id'] = null;

                if ($request->blog_status && !in_array($request->blog_status, ['draft', 'pending'])) {
                    $newsData['blog_status'] = 'draft';
                }
            }

            $news = News::create($newsData);

            return response()->json([
                'message' => 'Tạo blog thành công',
                'data' => new NewsResource($news)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Lỗi server: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update news
     */
    public function update(Request $request, News $news)
    {
        try {
            $isAdmin = Auth::guard('admin-token')->check();
            $isVendor = Auth::guard('sanctum')->check();
            $canEdit = false;

            if ($isAdmin) {
                $canEdit = true;
            } elseif ($isVendor) {
                $user = Auth::guard('sanctum')->user();
                $vendor = Vendor::where('user_id', $user->id)->first();

                if ($vendor && $news->author_type === 'vendor' && $news->vendor_id === $vendor->id) {
                    $canEdit = true;
                }
            }

            if (!$canEdit) {
                return response()->json(['message' => 'Bạn không có quyền sửa blog này'], 403);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|string|max:255',
                'content' => 'nullable|string',
                'excerpt' => 'nullable|string|max:500',
                'image' => 'nullable|string',
                'category_id' => 'nullable|exists:news_categories,id',
                'tags' => 'nullable|array',
                'published_at' => 'nullable|date',
                'blog_status' => 'sometimes|in:draft,pending,rejected,published,archived',
                'is_featured' => 'nullable|boolean',
                'meta_description' => 'nullable|string|max:160',
                'meta_keywords' => 'nullable|string',
                'destination' => 'nullable|string|max:255',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'travel_season' => 'nullable|in:spring,summer,autumn,winter,all_year',
                'travel_tips' => 'nullable|array',
                'estimated_budget' => 'nullable|numeric|min:0',
                'duration_days' => 'nullable|integer|min:1|max:365',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = $request->only([
                'title',
                'content',
                'excerpt',
                'image',
                'category_id',
                'tags',
                'published_at',
                'is_featured',
                'meta_description',
                'meta_keywords',
                'destination',
                'latitude',
                'longitude',
                'travel_season',
                'travel_tips',
                'estimated_budget',
                'duration_days'
            ]);

            // Handle status changes with permissions
            if ($request->has('blog_status')) {
                if ($isAdmin) {
                    $updateData['blog_status'] = $request->blog_status;
                } elseif ($isVendor) {
                    $allowedStatuses = ['draft', 'pending'];
                    if (
                        in_array($request->blog_status, $allowedStatuses) &&
                        in_array($news->blog_status, ['draft', 'pending', 'rejected'])
                    ) {
                        $updateData['blog_status'] = $request->blog_status;
                    }
                }
            }

            $news->update($updateData);

            return response()->json([
                'message' => 'Cập nhật blog thành công',
                'data' => new NewsResource($news->fresh())
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Lỗi server: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete news
     */
    public function destroy(News $news)
    {
        try {
            $isAdmin = Auth::guard('admin-token')->check();
            $isVendor = Auth::guard('sanctum')->check();
            $canDelete = false;

            if ($isAdmin) {
                $canDelete = true;
            } elseif ($isVendor) {
                $user = Auth::guard('sanctum')->user();
                $vendor = Vendor::where('user_id', $user->id)->first();

                if ($vendor && $news->author_type === 'vendor' && $news->vendor_id === $vendor->id) {
                    $canDelete = true;
                }
            }

            if (!$canDelete) {
                return response()->json(['message' => 'Bạn không có quyền xóa blog này'], 403);
            }

            $news->delete();

            return response()->json(['message' => 'Xóa blog thành công'], 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Lỗi server: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available tags
     */
    public function getTags()
    {   
        try {
            $tags = News::published()
                ->whereNotNull('tags')
                ->pluck('tags')
                ->flatten()
                ->unique()
                ->values()
                ->sort();

            return response()->json([
                'data' => $tags
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Lỗi server: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get popular destinations
     */
    public function getDestinations()
    {
        try {
            $destinations = News::published()
                ->whereNotNull('destination')
                ->groupBy('destination')
                ->selectRaw('destination, COUNT(*) as count')
                ->orderBy('count', 'desc')
                ->limit(20)
                ->pluck('destination');

            return response()->json([
                'data' => $destinations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Lỗi server: ' . $e->getMessage()
            ], 500);
        }
    }
}
