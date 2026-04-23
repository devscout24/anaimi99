<?php

namespace Database\Seeders;

use App\Models\SocialSetting;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SocialSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SocialSetting::updateOrCreate(
            ['id' => 1], // ensure single row
            [
                // Facebook
                'facebook_link'   => 'https://www.facebook.com/yourpage',
                'facebook_icon'   => null,

                // Instagram
                'instagram_link'  => 'https://www.instagram.com/yourpage',
                'instagram_icon'  => null,

                // Twitter / X
                'twitter_link'    => 'https://twitter.com/yourpage',
                'twitter_icon'    => null,

                // TikTok
                'tiktok_link'     => 'https://www.tiktok.com/@yourpage',
                'tiktok_icon'     => null,

                // WhatsApp
                'whatsapp_link'   => 'https://wa.me/1234567890',
                'whatsapp_icon'   => null,

                // LinkedIn
                'linkedin_link'   => 'https://www.linkedin.com/company/yourcompany',
                'linkedin_icon'   => null,

                // Telegram
                'telegram_link'   => 'https://t.me/yourchannel',
                'telegram_icon'   => null,

                // YouTube
                'youtube_link'    => 'https://www.youtube.com/@yourchannel',
                'youtube_icon'    => null,
            ]
        );
    }
}
