<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionSetting extends Model
{
    protected $guarded = [];

    /**
     * Calculate commission for a given total price and booking type.
     *
     * @param float $totalPrice
     * @param string $bookingType  e.g. salon_auto, salon_barber, home_barber, custom, as_soon_possible
     * @return array [commission_rate, admin_commission, provider_earnings]
     */
    public static function calculateCommission(float $totalPrice, string $bookingType): array
    {
        // Determine which commission type applies
        $commissionType = in_array($bookingType, ['salon_auto', 'salon_barber', 'custom'])
            ? 'salon'
            : 'home_barber';

        $setting = self::where('type', $commissionType)->first();

        $rate = $setting ? (float) $setting->commission_rate : 0;
        $adminCommission = round($totalPrice * $rate / 100, 2);
        $providerEarnings = round($totalPrice - $adminCommission, 2);

        return [
            'commission_rate' => $rate,
            'admin_commission' => $adminCommission,
            'provider_earnings' => $providerEarnings,
        ];
    }
}
