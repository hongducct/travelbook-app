<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Tour;
use App\Models\News;

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
        $reviewableData = $this->reviewable ? $this->formatReviewable($this->reviewable) : null;

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'booking_id' => $this->booking_id,
            'reviewable_type' => $this->reviewable_type,
            'reviewable_id' => $this->reviewable_id,
            'title' => $this->title,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'status' => $this->status,
            'replied_at' => $this->replied_at ? $this->replied_at->toDateTimeString() : null,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
            'user' => $this->user ? [
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
                // 'created_at' => $this->user->created_at->toDateTimeString(),
                // 'updated_at' => $this->user->updated_at->toDateTimeString(),
            ] : null,
            'reviewable' => $reviewableData,
        ];
    }

    /**
     * Format reviewable data based on its type.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $reviewable
     * @return array|null
     */
    private function formatReviewable($reviewable)
    {
        if ($reviewable instanceof Tour) {
            return [
                'id' => $reviewable->id,
                'vendor_id' => $reviewable->vendor_id,
                'location_id' => $reviewable->location_id,
                'name' => $reviewable->name,
                'description' => $reviewable->description,
                'days' => $reviewable->days,
                'nights' => $reviewable->nights,
                'category' => $reviewable->travelType?->name,
                'features' => $reviewable->features,
                'type' => 'tour',
                'created_at' => $reviewable->created_at->toDateTimeString(),
                'updated_at' => $reviewable->updated_at->toDateTimeString(),
            ];
        } elseif ($reviewable instanceof News) {
            return [
                'id' => $reviewable->id,
                'vendor_id' => $reviewable->vendor_id,
                'name' => $reviewable->name,
                'description' => $this->getExcerpt($reviewable->content),
                'type' => 'blog',
                'created_at' => $reviewable->created_at->toDateTimeString(),
                'updated_at' => $reviewable->updated_at->toDateTimeString(),
            ];
        }

        return null;
    }

    /**
     * Create excerpt for blog content.
     *
     * @param  string|null  $content
     * @return string
     */
    private function getExcerpt($content)
    {
        if (!$content) return '';
        $plainText = strip_tags($content);
        return strlen($plainText) > 150 ? substr($plainText, 0, 150) . '...' : $plainText;
    }
}
