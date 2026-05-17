<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ReviewRating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerReviewRatingController extends Controller
{
   public function customerReviewRating(Request $request)
{
    try {
        $customer = Auth::guard("api")->user();

        // Safety check
        if (!is_array($request->rating)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rating must be an array',
            ], 422);
        }


      $bookingAlreadyRatingExists = ReviewRating::where('customer_id', $customer->id)
            ->where('booking_id', $request->booking_id)
            ->exists();

            if ($bookingAlreadyRatingExists) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You have already submitted a review and rating for this booking',
                ], 422);
            }


        foreach ($request->rating as $key => $rating) {

            $reviewRating = new ReviewRating();

            // type check
            $type = $request->type[$key] ?? null;

            if ($type == 'salon') {
                $reviewRating->salon_id = $request->salon_id;
                $reviewRating->barbar_id = null;
            } elseif ($type == 'barber') {
                $reviewRating->barbar_id = $request->barbar_id;
                $reviewRating->salon_id = null;
            }

            $reviewRating->customer_id = $customer->id;
            $reviewRating->booking_id = $request->booking_id;
            $reviewRating->review = $request->review[$key] ?? null;
            $reviewRating->rating = $rating;

            $reviewRating->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Review and rating added successfully',
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to add review and rating',
            'error' => $e->getMessage(),
        ], 500);
    }
}
}

