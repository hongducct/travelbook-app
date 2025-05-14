<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;
use App\Http\Resources\NewsResource;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10); // mặc định 10 nếu không gửi lên
        $status = $request->query('status'); // nếu có lọc theo status

        $query = News::query();

        if ($status) {
            $query->where('blog_status', $status);
        }

        $news = $query->with('vendor')->paginate($perPage);
        // Chỉ lấy các trường cần thiết từ bảng news

        return NewsResource::collection($news);
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'image' => 'nullable|string',
            'published_at' => 'nullable|date',
            'blog_status' => 'nullable|in:draft,pending,rejected,published,archived',
        ]);

        $news = News::create($data);
        return response()->json($news, 201);
    }

    public function show(News $news)
    {
        return new NewsResource($news);
    }

    public function update(Request $request, News $news)
    {
        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'nullable|string',
            'image' => 'nullable|string',
            'published_at' => 'nullable|date',
            'blog_status' => 'nullable|in:draft,pending,rejected,published,archived',
        ]);

        $news->update($data);
        return response()->json($news);
    }

    public function destroy(News $news)
    {
        $news->delete();
        return response()->json(null, 204);
    }

    public function changeStatus(Request $request, News $news)
    {
        $data = $request->validate([
            'blog_status' => 'required|in:draft,pending,rejected,published,archived',
        ]);

        $news->update(['blog_status' => $data['blog_status']]);
        return response()->json(['message' => 'Status updated', 'news' => $news]);
    }
}
