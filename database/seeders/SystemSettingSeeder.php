<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SystemSetting::firstOrCreate(
            ['id' => 1], // ensures only one default record
            [
                'logo'            => null,
                'mini_logo'       => null,
                'favicon'         => null,
                'system_title'    => 'Admin Panel',
                'company_name'    => 'Company Name',
                'tag_line'        => 'Tag Line Here',
                'phone_number'    => '+39 366 2270888',
                'whatsapp_number' => '+39 366 2270888',
                'email'           => 'info@email.net',
                'copyright_text'  => '© ' . date('Y') . ' Admin Panel . All rights reserved.',
            ]
        );
    }
}
