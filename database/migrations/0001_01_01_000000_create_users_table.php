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
        Schema::create('users', function (Blueprint $table) {

            $table->id();

            $table->string('name', 100);
            $table->string('email', 255)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('username', 255)->nullable()->unique();
            $table->string('phone', 20)->nullable()->unique();
            // AUTH
            $table->string('password');

            // PROFILE
            $table->string('phone_number', 20)->nullable();
            $table->string('profile_image', 255)->nullable();
            $table->string('cover_image', 255)->nullable();

            // LOCATION
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            // SOCIAL LOGIN
            $table->string('google_id')->nullable();
            $table->string('facebook_id')->nullable();
            $table->string('apple_id')->nullable();

            // DEVICE
            $table->string('fcm_token')->nullable();

            // OTP
            $table->string('otp', 50)->nullable();
            $table->dateTime('otp_expires_at')->nullable();
            $table->dateTime('otp_verified_at')->nullable();

            // salon barbar
           $table->foreignId('salon_id')
                    ->nullable()
                    ->constrained('users')
                    ->onDelete('set null')
                    ->comment('For salon barbers');
            $table->boolean('salon_barbar_status')->default(false);

            // PASSWORD RESET
            $table->string('reset_password_token')->nullable();
            $table->dateTime('reset_password_token_expires_at')->nullable();

            // ROLE SYSTEM (UNIFIED)
            $table->enum('role', [
                'superadmin',
                'admin',
                'home_barbar',
                'salon',
                'salon_barbar',
                'customer',

            ])->default('customer');

            // STATUS
            $table->enum('block_status', ['blocked', 'unblock'])->default('unblock');
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_agree')->default(true);
            $table->enum('status', [
                    'pending',
                    'approved',
                    'cancel'
                ])->default('pending');
            // ACCOUNT DELETE
            $table->text('account_delete_reason')->nullable();
            $table->text('account_delete_comment')->nullable();

            $table->boolean('availability')->default(true);
            $table->softDeletes();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};