<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReviewRating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerReviewRatingController extends Controller
{
    public function customerReviewRating(Request $request)
    {
        try {
            $customer=Auth::guard("api")->user();
            $reviewRating = new ReviewRating();
            $reviewRating->barbar_id = $request->barbar_id;
            $reviewRating->salon_id = $request->salon_id;
            $reviewRating->customer_id = $customer->id;
            $reviewRating->booking_id = $request->booking_id;
            $reviewRating->review = $request->review;
            $reviewRating->rating = $request->rating;
            $reviewRating->save();
            return response()->json([
                'status' => 'success',
                'message' => 'Review and rating added successfully',
                'data' => $reviewRating,
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
