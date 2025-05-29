<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use App\Models\Tour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class VoucherController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of vouchers
     */
    public function index(Request $request)
    {
        $query = Voucher::with('usages');

        // Filter by status
        if ($request->has('status')) {
            switch ($request->status) {
                case 'active':
                    $query->active();
                    break;
                case 'expired':
                    $query->where('end_date', '<', now());
                    break;
                case 'upcoming':
                    $query->where('start_date', '>', now());
                    break;
            }
        }

        // Search by code
        if ($request->has('search')) {
            $query->where('code', 'like', '%' . $request->search . '%');
        }

        $vouchers = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'message' => 'Lấy danh sách voucher thành công',
            'data' => $vouchers->items(),
            'pagination' => [
                'current_page' => $vouchers->currentPage(),
                'last_page' => $vouchers->lastPage(),
                'per_page' => $vouchers->perPage(),
                'total' => $vouchers->total(),
            ]
        ]);
    }

    /**
     * Store a newly created voucher
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|unique:vouchers,code|max:50',
            'discount' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|integer|min:1|max:100',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'usage_limit' => 'nullable|integer|min:1',
            'applicable_tour_ids' => 'nullable|array',
            'applicable_tour_ids.*' => 'exists:tours,id',
        ]);

        // Custom validation: either discount or discount_percentage must be provided
        $validator->after(function ($validator) use ($request) {
            if (!$request->discount && !$request->discount_percentage) {
                $validator->errors()->add('discount', 'Phải có giảm giá cố định hoặc phần trăm giảm giá.');
            }
            if ($request->discount && $request->discount_percentage) {
                $validator->errors()->add('discount', 'Chỉ được chọn một loại giảm giá.');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        $voucher = Voucher::create($request->all());

        return response()->json([
            'message' => 'Tạo voucher thành công',
            'data' => $voucher
        ], 201);
    }

    /**
     * Display the specified voucher
     */
    public function show($id)
    {
        $voucher = Voucher::with(['usages.user', 'usages.booking'])->find($id);

        if (!$voucher) {
            return response()->json(['message' => 'Voucher không tồn tại'], 404);
        }

        return response()->json([
            'message' => 'Lấy thông tin voucher thành công',
            'data' => $voucher
        ]);
    }

    /**
     * Update the specified voucher
     */
    public function update(Request $request, $id)
    {
        $voucher = Voucher::find($id);

        if (!$voucher) {
            return response()->json(['message' => 'Voucher không tồn tại'], 404);
        }

        $validator = Validator::make($request->all(), [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('vouchers')->ignore($id)
            ],
            'discount' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|integer|min:1|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'usage_limit' => 'nullable|integer|min:1',
            'applicable_tour_ids' => 'nullable|array',
            'applicable_tour_ids.*' => 'exists:tours,id',
        ]);

        // Custom validation: either discount or discount_percentage must be provided
        $validator->after(function ($validator) use ($request) {
            if (!$request->discount && !$request->discount_percentage) {
                $validator->errors()->add('discount', 'Phải có giảm giá cố định hoặc phần trăm giảm giá.');
            }
            if ($request->discount && $request->discount_percentage) {
                $validator->errors()->add('discount', 'Chỉ được chọn một loại giảm giá.');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        $voucher->update($request->all());

        return response()->json([
            'message' => 'Cập nhật voucher thành công',
            'data' => $voucher
        ]);
    }

    /**
     * Remove the specified voucher
     */
    public function destroy($id)
    {
        $voucher = Voucher::find($id);

        if (!$voucher) {
            return response()->json(['message' => 'Voucher không tồn tại'], 404);
        }

        // Check if voucher has been used
        if ($voucher->usage_count > 0) {
            return response()->json([
                'message' => 'Không thể xóa voucher đã được sử dụng'
            ], 400);
        }

        $voucher->delete();

        return response()->json(['message' => 'Xóa voucher thành công']);
    }

    /**
     * Apply voucher to a tour
     */
    public function apply(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
            'tour_id' => 'required|exists:tours,id',
            'total_price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        $voucher = Voucher::where('code', $request->code)->first();

        if (!$voucher) {
            return response()->json(['message' => 'Mã voucher không tồn tại'], 404);
        }

        if (!$voucher->canBeUsed()) {
            return response()->json(['message' => 'Voucher không khả dụng hoặc đã hết hạn'], 400);
        }

        if (!$voucher->isApplicableToTour($request->tour_id)) {
            return response()->json(['message' => 'Voucher không áp dụng cho tour này'], 400);
        }

        $discountAmount = $voucher->calculateDiscount($request->total_price);

        return response()->json([
            'message' => 'Áp dụng voucher thành công',
            'data' => [
                'voucher' => $voucher,
                'discount_amount' => $discountAmount,
                'final_price' => max(0, $request->total_price - $discountAmount)
            ]
        ]);
    }

    /**
     * Get voucher statistics
     */
    public function statistics()
    {
        $stats = [
            'total_vouchers' => Voucher::count(),
            'active_vouchers' => Voucher::active()->count(),
            'expired_vouchers' => Voucher::where('end_date', '<', now())->count(),
            'upcoming_vouchers' => Voucher::where('start_date', '>', now())->count(),
            'total_usage' => \App\Models\VoucherUsage::count(),
            'total_discount_given' => \App\Models\VoucherUsage::sum('discount_applied'),
        ];

        return response()->json([
            'message' => 'Lấy thống kê thành công',
            'data' => $stats
        ]);
    }

    /**
     * Toggle voucher status (activate/deactivate)
     */
    public function toggle($id)
    {
        $voucher = Voucher::find($id);

        if (!$voucher) {
            return response()->json(['message' => 'Voucher không tồn tại'], 404);
        }

        // Toggle by adjusting dates
        if ($voucher->is_active) {
            $voucher->end_date = now()->subDay();
        } else {
            $voucher->start_date = now();
            $voucher->end_date = now()->addMonth();
        }

        $voucher->save();

        return response()->json([
            'message' => 'Thay đổi trạng thái voucher thành công',
            'data' => $voucher
        ]);
    }
}
