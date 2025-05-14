<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'reviewable_type' => $this->reviewable_type,
            'reviewable_id' => $this->reviewable_id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'status' => $this->status,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
            'user' => [
                'id' => $this->user->id,
                'username' => $this->user->username,
                'email' => $this->user->email,
                'first_name' => $this->user->first_name,
                'last_name' => $this->user->last_name,
                'phone_number' => $this->user->phone_number,
                'date_of_birth' => $this->user->date_of_birth,
                'description' => $this->user->description,
                'avatar' => $this->user->avatar,
                'address' => $this->user->address,
                'gender' => $this->user->gender,
                'is_vendor' => $this->user->is_vendor,
                'user_status' => $this->user->user_status,
                'created_at' => $this->user->created_at->toDateTimeString(),
                'updated_at' => $this->user->updated_at->toDateTimeString(),
            ],
            'reviewable' => [
                'id' => $this->reviewable->id,
                'vendor_id' => $this->reviewable->vendor_id,
                'location_id' => $this->reviewable->location_id,
                'name' => $this->reviewable->name,
                'description' => $this->reviewable->description,
                'days' => $this->reviewable->days,
                'nights' => $this->reviewable->nights,
                'category' => $this->reviewable->category,
                'features' => $this->reviewable->features,
                'created_at' => $this->reviewable->created_at->toDateTimeString(),
                'updated_at' => $this->reviewable->updated_at->toDateTimeString(),
            ],
        ];
    }
}