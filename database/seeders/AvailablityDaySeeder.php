<?php

namespace Database\Seeders;

use App\Models\AvailablityDay;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class AvailablityDaySeeder extends Seeder
{
    public function run(): void
    {
        $hasLegacyProviderColumns = Schema::hasColumns('availability_days', ['provider_id', 'provider_type']);

        if ($hasLegacyProviderColumns) {
            $userIds = User::query()->pluck('id');

            foreach ($userIds as $userId) {
                foreach (AvailablityDay::DAYS as $dayName) {
                    AvailablityDay::query()->firstOrCreate(
                        [
                            'provider_id' => $userId,
                            'provider_type' => 'salon',
                            'day_name' => $dayName,
                        ],
                        [
                            'is_active' => true,
                        ]
                    );
                }
            }

            return;
        }

        foreach (AvailablityDay::DAYS as $dayName) {
            AvailablityDay::query()->firstOrCreate(
                [
                    'day_name' => $dayName,
                ],
                [
                    'is_active' => true,
                ]
            );
        }
    }
}
