<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\Vendor;
use App\Http\Resources\NewsResource;
use App\Http\Resources\ReviewResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class NewsController extends Controller
{
    /**
     * Tạo blog mới
     */
    public function store(Request $request)
    {
        try {
            // Validate dữ liệu
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'content' => 'nullable|string',
                'image' => 'nullable|string',
                'published_at' => 'nullable|date',
                'blog_status' => 'nullable|in:draft,pending,rejected,published,archived',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Xác định người dùng và loại tác giả
            $isAdmin = Auth::guard('admin-token')->check();
            $isVendor = Auth::guard('sanctum')->check();

            if (!$isAdmin && !$isVendor) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 401);
            }

            // Chuẩn bị dữ liệu để tạo news
            $newsData = [
                'title' => $request->title,
                'content' => $request->content,
                'image' => $request->image,
                'published_at' => $request->published_at,
                'blog_status' => $request->blog_status ?? 'draft',
            ];

            if ($isAdmin) {
                $admin = Auth::guard('admin-token')->user();
                $newsData['author_type'] = 'admin';
                $newsData['admin_id'] = $admin->id;
                $newsData['vendor_id'] = null;
                // Admin có thể tạo với bất kỳ status nào
            } else {
                $user = Auth::guard('sanctum')->user();
                $vendor = Vendor::where('user_id', $user->id)->first();

                if (!$vendor) {
                    return response()->json([
                        'message' => 'Không tìm thấy thông tin vendor'
                    ], 404);
                }

                $newsData['author_type'] = 'vendor';
                $newsData['vendor_id'] = $vendor->id;
                $newsData['admin_id'] = null;

                // Vendor chỉ được tạo với status draft hoặc pending
                if ($request->blog_status && !in_array($request->blog_status, ['draft', 'pending'])) {
                    $newsData['blog_status'] = 'draft';
                }
            }

            // Tạo news
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
     * Lấy danh sách blog với phân quyền
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('perPage', 10); // Align with TourController
            $search = $request->input('search');
            $status = $request->input('status');
            $featured = $request->input('featured');

            $query = News::with([
                'vendor.user',
                'admin',
                'reviews' => function ($query) {
                    $query->where('status', 'approved')->with('user:id,username,avatar');
                },
            ]);

            // Add search functionality
            if ($search) {
                $query->where('title', 'like', '%' . $search . '%');
            }

            // Add option to get featured news (most reviewed)
            if ($featured) {
                $query->withCount(['reviews' => function ($query) {
                    $query->where('status', 'approved');
                }])->orderBy('reviews_count', 'desc');
            }

            // Kiểm tra quyền truy cập
            $isAdmin = Auth::guard('admin-token')->check();
            $isVendor = Auth::guard('sanctum')->check();

            if ($isAdmin) {
                // Admin có thể xem tất cả blog
            } elseif ($isVendor) {
                // Vendor chỉ được xem:
                // 1. Blog published của tất cả mọi người
                // 2. Blog của chính họ (tất cả status)
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
                    $query->where('blog_status', 'published');
                }
            } else {
                // Guest chỉ được xem blog published
                $query->where('blog_status', 'published');
            }

            // Apply status filter nếu có
            if ($status) {
                $query->where('blog_status', $status);
            }

            $news = $query->orderBy('created_at', 'desc')->paginate($perPage);

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
     * Lấy chi tiết blog
     */
    public function show(News $news)
    {
        try {
            $isAdmin = Auth::guard('admin-token')->check();
            $isVendor = Auth::guard('sanctum')->check();

            // Kiểm tra quyền xem
            $canView = false;

            if ($news->blog_status === 'published') {
                // Blog published thì ai cũng xem được
                $canView = true;
            } elseif ($isAdmin) {
                // Admin xem được tất cả
                $canView = true;
            } elseif ($isVendor) {
                // Vendor chỉ xem được blog của chính mình
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

            // Load relationships including approved reviews
            $news->load([
                'vendor.user',
                'admin',
                'reviews' => function ($query) {
                    $query->where('status', 'approved')->with('user:id,username,avatar');
                },
            ]);

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
     * Cập nhật blog
     */
    public function update(Request $request, News $news)
    {
        try {
            $isAdmin = Auth::guard('admin-token')->check();
            $isVendor = Auth::guard('sanctum')->check();

            // Kiểm tra quyền sửa
            $canEdit = false;

            if ($isAdmin) {
                // Admin có thể sửa tất cả blog
                $canEdit = true;
            } elseif ($isVendor) {
                // Vendor chỉ sửa được blog của chính mình
                $user = Auth::guard('sanctum')->user();
                $vendor = Vendor::where('user_id', $user->id)->first();

                if ($vendor && $news->author_type === 'vendor' && $news->vendor_id === $vendor->id) {
                    $canEdit = true;
                }
            }

            if (!$canEdit) {
                return response()->json([
                    'message' => 'Bạn không có quyền sửa blog này'
                ], 403);
            }

            // Validate dữ liệu
            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|string|max:255',
                'content' => 'nullable|string',
                'image' => 'nullable|string',
                'published_at' => 'nullable|date',
                'blog_status' => 'sometimes|in:draft,pending,rejected,published,archived',
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
                'image',
                'published_at'
            ]);

            // Log ra dữ liệu cập nhật
            Log::info('Cập nhật blog với dữ liệu: ', $updateData);

            // Xử lý blog_status với phân quyền
            if ($request->has('blog_status')) {
                if ($isAdmin) {
                    // Admin có thể thay đổi sang bất kỳ status nào
                    $updateData['blog_status'] = $request->blog_status;
                } elseif ($isVendor) {
                    // Vendor chỉ được thay đổi từ draft sang pending
                    $allowedStatuses = ['draft', 'pending'];

                    if (in_array($request->blog_status, $allowedStatuses)) {
                        // Chỉ cho phép thay đổi nếu hiện tại là draft hoặc pending
                        if (in_array($news->blog_status, ['draft', 'pending', 'rejected'])) {
                            $updateData['blog_status'] = $request->blog_status;
                        }
                    }
                }
            }

            // Cập nhật
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
     * Xóa blog
     */
    public function destroy(News $news)
    {
        try {
            $isAdmin = Auth::guard('admin-token')->check();
            $isVendor = Auth::guard('sanctum')->check();

            // Kiểm tra quyền xóa
            $canDelete = false;

            if ($isAdmin) {
                // Admin có thể xóa tất cả blog
                $canDelete = true;
            } elseif ($isVendor) {
                // Vendor chỉ xóa được blog của chính mình
                $user = Auth::guard('sanctum')->user();
                $vendor = Vendor::where('user_id', $user->id)->first();

                if ($vendor && $news->author_type === 'vendor' && $news->vendor_id === $vendor->id) {
                    $canDelete = true;
                }
            }

            if (!$canDelete) {
                return response()->json([
                    'message' => 'Bạn không có quyền xóa blog này'
                ], 403);
            }

            $news->delete();

            return response()->json([
                'message' => 'Xóa blog thành công'
            ], 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Lỗi server: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Thay đổi trạng thái blog (dành riêng cho admin duyệt bài)
     */
    public function changeStatus(Request $request, News $news)
    {
        try {
            $isAdmin = Auth::guard('admin-token')->check();
            $isVendor = Auth::guard('sanctum')->check();

            // Validate dữ liệu
            $validator = Validator::make($request->all(), [
                'blog_status' => 'required|in:draft,pending,rejected,published,archived',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            $newStatus = $request->blog_status;

            if ($isAdmin) {
                // Admin có thể thay đổi sang bất kỳ status nào
                $news->update(['blog_status' => $newStatus]);

                return response()->json([
                    'message' => 'Thay đổi trạng thái thành công',
                    'data' => new NewsResource($news->fresh())
                ]);
            } elseif ($isVendor) {
                // Vendor chỉ được thay đổi blog của mình
                $user = Auth::guard('sanctum')->user();
                $vendor = Vendor::where('user_id', $user->id)->first();

                if (!$vendor || $news->author_type !== 'vendor' || $news->vendor_id !== $vendor->id) {
                    return response()->json([
                        'message' => 'Bạn không có quyền thay đổi trạng thái blog này'
                    ], 403);
                }

                // Vendor chỉ được thay đổi từ draft sang pending hoặc từ rejected về draft/pending
                $allowedTransitions = [
                    'draft' => ['pending'],
                    'rejected' => ['draft', 'pending'],
                    'pending' => ['draft'] // Có thể rút lại về draft
                ];

                $currentStatus = $news->blog_status;

                if (
                    !isset($allowedTransitions[$currentStatus]) ||
                    !in_array($newStatus, $allowedTransitions[$currentStatus])
                ) {
                    return response()->json([
                        'message' => 'Bạn không được phép thay đổi từ trạng thái này'
                    ], 403);
                }

                $news->update(['blog_status' => $newStatus]);

                return response()->json([
                    'message' => 'Thay đổi trạng thái thành công',
                    'data' => new NewsResource($news->fresh())
                ]);
            } else {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 401);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Lỗi server: ' . $e->getMessage()
            ], 500);
        }
    }
}
