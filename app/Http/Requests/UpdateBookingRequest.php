<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookingRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'start_date' => ['sometimes', 'date', 'after_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'number_of_guests_adults' => ['sometimes', 'integer', 'min:1'],
            'number_of_children' => ['nullable', 'integer', 'min:0'],
            'total_price' => ['sometimes', 'numeric', 'min:0'],
            'status' => ['sometimes', Rule::in(['pending', 'confirmed', 'cancelled'])]
        ];
    }
    
    public function messages()
    {
        return [
            'start_date.after_or_equal' => 'The start date must be today or a future date.',
            'end_date.after' => 'The end date must be after the start date.',
            'number_of_guests_adults.min' => 'The number of adult guests must be at least 1.',
            'number_of_children.min' => 'The number of children must be at least 0.',
            'total_price.min' => 'The total price must be at least 0.'
        ];
    }
    
}
