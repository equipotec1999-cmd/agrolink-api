<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'profile' => [
                'avatar_path' => $this->profile?->avatar_path,
                'bio' => $this->profile?->bio,
                'state' => $this->profile?->state,
                'municipality' => $this->profile?->municipality,
            ],
            'seller_profile' => $this->when($this->sellerProfile, fn () => [
                'business_name' => $this->sellerProfile->business_name,
                'is_verified' => $this->sellerProfile->is_verified,
                'completed_operations' => $this->sellerProfile->completed_operations,
                'rating_accuracy' => $this->sellerProfile->rating_accuracy,
                'rating_fulfillment' => $this->sellerProfile->rating_fulfillment,
                'rating_communication' => $this->sellerProfile->rating_communication,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
