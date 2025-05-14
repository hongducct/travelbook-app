<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreBookingRequest extends FormRequest
{
    public function authorize()
    {
        $isAuthenticated = auth()->check();
        // Log::info('StoreBookingRequest authorize', [
        //     'isAuthenticated' => $isAuthenticated,
        //     'user' => auth()->user() ? auth()->user()->toArray() : null,
        // ]);
        return $isAuthenticated;
    }

    public function rules()
    {
        return [
            'tour_id' => 'required|exists:tours,id',
            'start_date' => 'required|date|after_or_equal:today',
            'number_of_guests_adults' => 'required|integer|min:1',
            'number_of_children' => 'nullable|integer|min:0',
            'voucher_code' => 'nullable|string',
            'special_requests' => 'nullable|string|max:1000',
            'contact_phone' => 'required|string|max:20',
            'payment_method' => 'required|in:cash,bank_transfer,credit_card,paypal',
        ];
    }

    public function messages()
    {
        return [
            'tour_id.required' => 'Vui lòng chọn tour.',
            'tour_id.exists' => 'Tour không tồn tại.',
            'start_date.required' => 'Vui lòng chọn ngày bắt đầu.',
            'start_date.date' => 'Ngày bắt đầu không đúng định dạng.',
            'start_date.after_or_equal' => 'Ngày bắt đầu phải từ hôm nay trở đi.',
            'number_of_guests_adults.required' => 'Vui lòng nhập số người lớn.',
            'number_of_guests_adults.integer' => 'Số người lớn phải là số nguyên.',
            'number_of_guests_adults.min' => 'Phải có ít nhất 1 người lớn.',
            'number_of_children.integer' => 'Số trẻ em phải là số nguyên.',
            'number_of_children.min' => 'Số trẻ em không được âm.',
            'voucher_code.exists' => 'Mã voucher không hợp lệ.',
            'contact_phone.required' => 'Vui lòng nhập số điện thoại.',
            'contact_phone.min' => 'Số điện thoại phải có ít nhất 10 ký tự.',
            'contact_phone.max' => 'Số điện thoại không được vượt quá 15 ký tự.',
            'payment_method.required' => 'Vui lòng chọn phương thức thanh toán.',
            'payment_method.in' => 'Phương thức thanh toán không hợp lệ.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        Log::warning('StoreBookingRequest validation failed', [
            'errors' => $validator->errors()->toArray(),
            'request' => $this->all(),
        ]);

        throw new HttpResponseException(
            response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}