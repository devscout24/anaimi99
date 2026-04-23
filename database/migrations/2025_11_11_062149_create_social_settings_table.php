<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('social_settings', function (Blueprint $table) {
            $table->id();
            // Facebook
            $table->text('facebook_link')->nullable();
            $table->string('facebook_icon')->nullable();
            // Instagram
            $table->text('instagram_link')->nullable();
            $table->string('instagram_icon')->nullable();
            // Twitter (X)
            $table->text('twitter_link')->nullable();
            $table->string('twitter_icon')->nullable();
            // TikTok
            $table->text('tiktok_link')->nullable();
            $table->string('tiktok_icon')->nullable();
            // WhatsApp
            $table->text('whatsapp_link')->nullable();
            $table->string('whatsapp_icon')->nullable();
            // LinkedIn
            $table->text('linkedin_link')->nullable();
            $table->string('linkedin_icon')->nullable();
            // Telegram
            $table->text('telegram_link')->nullable();
            $table->string('telegram_icon')->nullable();
            // YouTube
            $table->text('youtube_link')->nullable();
            $table->string('youtube_icon')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_settings');
    }
};
