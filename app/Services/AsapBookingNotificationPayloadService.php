<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\ReviewRating;
use App\Models\ServicePrice;
use App\Models\User;
use Carbon\Carbon;

class AsapBookingNotificationPayloadService
{
    public const ACCEPT_TIMEOUT_SECONDS = 180;
    private const AVERAGE_TRAVEL_SPEED_KMH = 25;
    private const DISPATCH_BUFFER_MINUTES = 5;
    private const ETA_RANGE_BUFFER_MINUTES = 5;

    public static function build(
        Booking $booking,
        User $customer,
        User $barber,
        iterable $slots,
        int $totalDurationMinutes,
        ?string $customerAddress = null
    ): array {
        $booking->loadMissing(['items.service']);

        $serviceItems = $booking->items->map(function ($item) {
            $servicePrice = ServicePrice::where('service_id', $item->service_id)->first();
            $duration = (int) ($servicePrice?->time_duration ?? 0);

            return [
                'id' => (string) $item->service_id,
                'name' => (string) ($item->service?->service_name ?? 'Unknown Service'),
                'quantity' => (string) $item->quantity,
                'price' => number_format((float) $item->price, 2, '.', ''),
                'total' => number_format((float) $item->total, 2, '.', ''),
                'duration_minutes' => (string) ($duration * (int) $item->quantity),
            ];
        });

        $slotTimes = collect($slots)->map(function ($slot) {
            return trim(($slot->scheduled_start_time ?? $slot->start_time) . ' - ' . ($slot->scheduled_end_time ?? $slot->end_time));
        })->filter()->values();

        $assignedAt = $booking->last_assigned_at ? Carbon::parse($booking->last_assigned_at) : now();
        $expiresAt = (clone $assignedAt)->addSeconds(self::ACCEPT_TIMEOUT_SECONDS);

        $distance = self::calculateDistanceKm($customer, $barber);
        $arrivalEstimate = self::calculateArrivalEstimate($distance);
        $serviceLabel = $serviceItems->pluck('name')->filter()->implode(' + ');
        $rating = ReviewRating::where('customer_id', $customer->id)->avg('rating');

        $address = $customerAddress ?: self::formatCustomerLocation($customer);

        return [
            'type' => 'asap_booking_request',
            'booking_id' => (string) $booking->id,
            'booking_type' => (string) $booking->booking_type,
            'status' => (string) $booking->status,
            'payment_type' => (string) $booking->payment_type,

            'title' => 'Nouvelle demande',
            'badge' => 'ASAP',
            'expires_in_seconds' => (string) self::ACCEPT_TIMEOUT_SECONDS,
            'expires_in_label' => gmdate('i:s', self::ACCEPT_TIMEOUT_SECONDS),
            'expires_at' => $expiresAt->toIso8601String(),

            'customer_id' => (string) $customer->id,
            'customer_name' => (string) $customer->name,
            'customer_profile_image' => self::assetUrl($customer->profile_image),
            'customer_rating' => number_format((float) ($rating ?: 0), 1, '.', ''),
            'barber_id' => (string) $barber->id,
            'barber_name' => (string) $barber->name,
            'barber_profile_image' => self::assetUrl($barber->profile_image),

            'distance_km' => $distance !== null ? (string) $distance : '',
            'distance_label' => $distance !== null ? $distance . ' km' : '',
            'address' => $address,
            'location_type' => 'A domicile',
            'latitude' => (string) ($customer->latitude ?? ''),
            'longitude' => (string) ($customer->longitude ?? ''),
            'customer_latitude' => (string) ($customer->latitude ?? ''),
            'customer_longitude' => (string) ($customer->longitude ?? ''),
            'barber_latitude' => (string) ($barber->latitude ?? ''),
            'barber_longitude' => (string) ($barber->longitude ?? ''),

            'service_ids' => $serviceItems->pluck('id')->values()->toJson(),
            'service_names' => $serviceLabel,
            'service_items' => $serviceItems->values()->toJson(),
            'service_summary' => trim($serviceLabel . ' - ' . $totalDurationMinutes . ' min - ' . number_format((float) $booking->total_price, 2, '.', '') . ' EUR'),
            'total_duration' => (string) $totalDurationMinutes,
            'total_duration_label' => $totalDurationMinutes . ' min',
            'total_price' => number_format((float) $booking->total_price, 2, '.', ''),
            'total_price_label' => number_format((float) $booking->total_price, 2, '.', '') . ' EUR',
            'currency' => 'EUR',

            'arrival_type' => 'Des que possible',
            'arrival_estimate' => $arrivalEstimate['label'],
            'arrival_estimate_min_minutes' => (string) $arrivalEstimate['min'],
            'arrival_estimate_max_minutes' => (string) $arrivalEstimate['max'],
            'booking_date' => (string) $booking->booking_date,
            'booking_slots' => $slotTimes->toJson(),

            'accept_action' => 'accept_asap_booking',
            'reject_action' => 'reject_asap_booking',
        ];
    }

    private static function calculateDistanceKm(User $customer, User $barber): ?float
    {
        if (isset($barber->distance)) {
            return round((float) $barber->distance, 1);
        }

        if (!$customer->latitude || !$customer->longitude || !$barber->latitude || !$barber->longitude) {
            return null;
        }

        $earthRadiusKm = 6371;
        $customerLat = deg2rad((float) $customer->latitude);
        $customerLng = deg2rad((float) $customer->longitude);
        $barberLat = deg2rad((float) $barber->latitude);
        $barberLng = deg2rad((float) $barber->longitude);

        $latDelta = $customerLat - $barberLat;
        $lngDelta = $customerLng - $barberLng;

        $a = sin($latDelta / 2) ** 2
            + cos($barberLat) * cos($customerLat) * sin($lngDelta / 2) ** 2;

        $distance = 2 * $earthRadiusKm * asin(min(1, sqrt($a)));

        return round($distance, 1);
    }

    private static function calculateArrivalEstimate(?float $distanceKm): array
    {
        if ($distanceKm === null) {
            return [
                'min' => '',
                'max' => '',
                'label' => '',
            ];
        }

        $travelMinutes = (int) ceil(($distanceKm / self::AVERAGE_TRAVEL_SPEED_KMH) * 60);
        $minMinutes = max(1, $travelMinutes + self::DISPATCH_BUFFER_MINUTES);
        $maxMinutes = $minMinutes + self::ETA_RANGE_BUFFER_MINUTES;

        return [
            'min' => $minMinutes,
            'max' => $maxMinutes,
            'label' => $minMinutes . '-' . $maxMinutes . ' min',
        ];
    }

    private static function formatCustomerLocation(User $customer): string
    {
        if ($customer->latitude && $customer->longitude) {
            return $customer->latitude . ', ' . $customer->longitude;
        }

        return '';
    }

    private static function assetUrl(?string $path): string
    {
        if (!$path) {
            return '';
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset($path);
    }
}
