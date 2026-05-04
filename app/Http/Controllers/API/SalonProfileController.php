<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReviewRating;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SalonProfileController extends Controller
{
    use ApiResponse;

    /**
     * Get salon or barber profile details.
     * Route: GET /api/salon/profile/{type}?id=1
     * @param string $type ('information' or 'reviews')
     */
    public function getProfile($type, Request $request)
    {
        try {
            $user = Auth::guard("api")->user();

            if (!$user) {
                return $this->error('User not authenticated');
            }

            $user->load(['imageGallery', 'providerprofiles']);

            if ($type === 'information') {
                return $this->getInformation($user);
            } elseif ($type === 'reviews') {
                return $this->getReviews($user);
            } else {
                return $this->validationError('Invalid type. Use "information" or "reviews".');
            }

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Get Information tab data.
     */
    private function getInformation(User $user)
    {
        $gallery = $user->imageGallery
            ? $user->imageGallery->map(function ($img) {
                return [
                    'id' => $img->id,
                    'image' => $img->image ? asset($img->image) : null,
                ];
            })->values()
            : [];

        // Overall stats (for the header)
        $reviews = $user->role === 'salon'
            ? ReviewRating::where('salon_id', $user->id)->get()
            : ReviewRating::where('barbar_id', $user->id)->get();

        $reviewCount = $reviews->count();
        $avgRating = $reviewCount > 0 ? round($reviews->avg('rating'), 1) : 0;

        $data = [
            'profile_header' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_image' => $user->profile_image ? asset($user->profile_image) : null,
                'avg_rating' => (float) $avgRating,
                'review_count' => $reviewCount,
            ],
            'information' => [
                'representative_name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'since' => $user->created_at ? $user->created_at->format('Y') : null,
                'about' => $user->about ?? $user->providerprofiles->about ?? null,
                'business_name' => $user->business_name ?? $user->providerprofiles->business_name ?? null,
                'salon_address' => $user->salon_address ?? $user->providerprofiles->salon_address ?? null,
                'gallery_images' => $gallery,
            ]
        ];

        return $this->success($data, 'Information fetched successfully');
    }

    /**
     * Get Reviews tab data.
     */
    private function getReviews(User $user)
    {
        $query = ReviewRating::with('customer');

        if ($user->role === 'salon') {
            $query->where('salon_id', $user->id);
        } else {
            $query->where('barbar_id', $user->id);
        }

        $reviews = $query->latest()->get();

        $reviewCount = $reviews->count();
        $avgRating = $reviewCount > 0 ? round($reviews->avg('rating'), 1) : 0;

        $reviewData = $reviews->map(function ($review) {
            return [
                'id' => $review->id,
                'customer_name' => $review->customer->name ?? 'Unknown',
                'customer_image' => $review->customer->profile_image ? asset($review->customer->profile_image) : null,
                'customer_email' => $review->customer->email ?? null,
                'rating' => $review->rating,
                'review' => $review->review,
                'date' => $review->created_at->format('M d, Y'),
            ];
        });

        $data = [
            'profile_header' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_image' => $user->profile_image ? asset($user->profile_image) : null,
                'avg_rating' => (float) $avgRating,
                'review_count' => $reviewCount,
            ],
            'reviews' => $reviewData
        ];

        return $this->success($data, 'Reviews fetched successfully');
    }
}
