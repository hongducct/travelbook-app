<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VoucherController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function apply(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
            'tour_id' => 'required|exists:tours,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Dữ liệu không hợp lệ', 'errors' => $validator->errors()], 400);
        }

        $voucher = Voucher::where('code', $request->code)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->where('usage_limit', '>', 0)
            ->first();

        if (!$voucher) {
            return response()->json(['message' => 'Mã voucher không hợp lệ hoặc đã hết hạn.'], 400);
        }

        $applicableTours = json_decode($voucher->applicable_tour_ids, true);
        if (!in_array($request->tour_id, $applicableTours)) {
            return response()->json(['message' => 'Voucher không áp dụng cho tour này.'], 400);
        }

        return response()->json(['voucher' => $voucher], 200);
    }
}