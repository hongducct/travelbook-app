<?php

namespace App\Http\Controllers;

use App\Models\NewsCategory;
use App\Http\Resources\NewsCategoryResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class NewsCategoryController extends Controller
{
    /**
     * Display a listing of the categories.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        try {
            $query = NewsCategory::query();

            // Filter by active status if requested
            if ($request->has('active')) {
                $active = filter_var($request->active, FILTER_VALIDATE_BOOLEAN);
                $query->where('is_active', $active);
            }

            // Apply sorting
            $sortField = $request->input('sort_by', 'sort_order');
            $sortDirection = $request->input('sort_direction', 'asc');

            if (in_array($sortField, ['name', 'sort_order', 'created_at'])) {
                $query->orderBy($sortField, $sortDirection === 'desc' ? 'desc' : 'asc');
            }

            // Secondary sort by name for consistent ordering
            if ($sortField !== 'name') {
                $query->orderBy('name', 'asc');
            }

            // Get categories with post count
            $categories = $query->withCount('news')->get();

            return NewsCategoryResource::collection($categories);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Lỗi khi lấy danh sách danh mục: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created category in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Check if user is admin
            if (!Auth::guard('admin-token')->check()) {
                return response()->json([
                    'message' => 'Bạn không có quyền tạo danh mục'
                ], 403);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:news_categories,name',
                'description' => 'nullable|string',
                'color' => 'nullable|string|max:7|regex:/^#[a-fA-F0-9]{6}$/',
                'icon' => 'nullable|string|max:50',
                'is_active' => 'nullable|boolean',
                'sort_order' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Create slug from name
            $slug = Str::slug($request->name);
            $originalSlug = $slug;
            $counter = 1;

            // Ensure slug is unique
            while (NewsCategory::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            // Create category
            $category = NewsCategory::create([
                'name' => $request->name,
                'slug' => $slug,
                'description' => $request->description,
                'color' => $request->color ?? '#3B82F6', // Default blue color
                'icon' => $request->icon,
                'is_active' => $request->has('is_active') ? $request->is_active : true,
                'sort_order' => $request->sort_order ?? 0,
            ]);

            return response()->json([
                'message' => 'Tạo danh mục thành công',
                'data' => new NewsCategoryResource($category)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Lỗi khi tạo danh mục: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified category.
     *
     * @param  int  $id
     * @return \App\Http\Resources\NewsCategoryResource|\Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $category = NewsCategory::withCount('news')->findOrFail($id);
            return new NewsCategoryResource($category);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Không tìm thấy danh mục: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified category in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            // Check if user is admin
            if (!Auth::guard('admin-token')->check()) {
                return response()->json([
                    'message' => 'Bạn không có quyền cập nhật danh mục'
                ], 403);
            }

            $category = NewsCategory::findOrFail($id);

            // Validate request
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255|unique:news_categories,name,' . $id,
                'description' => 'nullable|string',
                'color' => 'nullable|string|max:7|regex:/^#[a-fA-F0-9]{6}$/',
                'icon' => 'nullable|string|max:50',
                'is_active' => 'nullable|boolean',
                'sort_order' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update slug if name changes
            if ($request->has('name') && $request->name !== $category->name) {
                $slug = Str::slug($request->name);
                $originalSlug = $slug;
                $counter = 1;

                // Ensure slug is unique
                while (NewsCategory::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }

                $category->slug = $slug;
            }

            // Update category fields
            if ($request->has('name')) $category->name = $request->name;
            if ($request->has('description')) $category->description = $request->description;
            if ($request->has('color')) $category->color = $request->color;
            if ($request->has('icon')) $category->icon = $request->icon;
            if ($request->has('is_active')) $category->is_active = $request->is_active;
            if ($request->has('sort_order')) $category->sort_order = $request->sort_order;

            $category->save();

            return response()->json([
                'message' => 'Cập nhật danh mục thành công',
                'data' => new NewsCategoryResource($category)
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Không tìm thấy danh mục'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Lỗi khi cập nhật danh mục: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified category from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            // Check if user is admin
            if (!Auth::guard('admin-token')->check()) {
                return response()->json([
                    'message' => 'Bạn không có quyền xóa danh mục'
                ], 403);
            }

            $category = NewsCategory::findOrFail($id);

            // Check if category has associated news
            $newsCount = $category->news()->count();
            if ($newsCount > 0) {
                return response()->json([
                    'message' => 'Không thể xóa danh mục này vì có ' . $newsCount . ' bài viết liên quan',
                    'news_count' => $newsCount
                ], 422);
            }

            $category->delete();

            return response()->json([
                'message' => 'Xóa danh mục thành công'
            ], 204);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Không tìm thấy danh mục'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Lỗi khi xóa danh mục: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get news posts for a specific category.
     *
     * @param  int  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
     */
    public function getCategoryNews($id, Request $request)
    {
        try {
            $category = NewsCategory::findOrFail($id);

            $perPage = $request->input('perPage', 12);
            $sort = $request->input('sort', 'latest');

            $query = $category->news()->with([
                'vendor.user',
                'admin',
                'category',
                'reviews' => function ($query) {
                    $query->where('status', 'approved')->with('user:id,username,avatar');
                },
            ]);

            // Apply access control
            $isAdmin = Auth::guard('admin-token')->check();
            $isVendor = Auth::guard('sanctum')->check();

            if (!$isAdmin) {
                if ($isVendor) {
                    $user = Auth::guard('sanctum')->user();
                    $vendorId = $user->vendor->id ?? null;

                    if ($vendorId) {
                        $query->where(function ($q) use ($vendorId) {
                            $q->where('blog_status', 'published')
                                ->orWhere(function ($subQ) use ($vendorId) {
                                    $subQ->where('author_type', 'vendor')
                                        ->where('vendor_id', $vendorId);
                                });
                        });
                    } else {
                        $query->where('blog_status', 'published');
                    }
                } else {
                    $query->where('blog_status', 'published');
                }
            }

            // Apply sorting
            switch ($sort) {
                case 'featured':
                    $query->where('is_featured', true)->orderBy('published_at', 'desc');
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
                    $query->orderBy('published_at', 'desc');
            }

            $news = $query->paginate($perPage);

            // Transform the response
            $news->getCollection()->transform(function ($newsItem) {
                $newsItem->average_rating = $newsItem->reviews->avg('rating') ?? 0;
                $newsItem->review_count = $newsItem->reviews->count();
                return $newsItem;
            });

            return \App\Http\Resources\NewsResource::collection($news);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Không tìm thấy danh mục'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Lỗi khi lấy bài viết: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the order of multiple categories at once.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateOrder(Request $request)
    {
        try {
            // Check if user is admin
            if (!Auth::guard('admin-token')->check()) {
                return response()->json([
                    'message' => 'Bạn không có quyền cập nhật thứ tự danh mục'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'categories' => 'required|array',
                'categories.*.id' => 'required|exists:news_categories,id',
                'categories.*.sort_order' => 'required|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            foreach ($request->categories as $categoryData) {
                NewsCategory::where('id', $categoryData['id'])
                    ->update(['sort_order' => $categoryData['sort_order']]);
            }

            return response()->json([
                'message' => 'Cập nhật thứ tự danh mục thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Lỗi khi cập nhật thứ tự danh mục: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle the active status of a category.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleActive($id)
    {
        try {
            // Check if user is admin
            if (!Auth::guard('admin-token')->check()) {
                return response()->json([
                    'message' => 'Bạn không có quyền thay đổi trạng thái danh mục'
                ], 403);
            }

            $category = NewsCategory::findOrFail($id);
            $category->is_active = !$category->is_active;
            $category->save();

            return response()->json([
                'message' => 'Thay đổi trạng thái danh mục thành công',
                'data' => new NewsCategoryResource($category)
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Không tìm thấy danh mục'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Lỗi khi thay đổi trạng thái danh mục: ' . $e->getMessage()
            ], 500);
        }
    }
}
