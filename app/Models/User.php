<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    private const ROLE_ALIASES = [
        'user' => 'customer',
        'service_provider' => 'home_barbar',
    ];

    private const SERVICE_PROVIDER_ROLES = [
        'home_barbar',
        'salon',
        'salon_barbar',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        // Basic
        'name',
        'email',
        'username',
        'phone',
        'password',

        // Profile
        'phone_number',
        'profile_image',
        'cover_image',

        // Location
        'latitude',
        'longitude',

        // Social
        'google_id',
        'facebook_id',
        'apple_id',

        // Device
        'fcm_token',

        // OTP
        'otp',
        'otp_expires_at',
        'otp_verified_at',

        // Salon / Barber
        'salon_id',
        'salon_barbar_status',

        // Password reset
        'reset_password_token',
        'reset_password_token_expires_at',

        // Role / Status
        'role',
        'block_status',
        'is_verified',
        'is_agree',
        'status',

        // Account delete
        'account_delete_reason',
        'account_delete_comment',

        // Availability
        'availability',
    ];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function provider_profiles()
    {
        return $this->hasOne(ProviderProfile::class, 'user_id');
    }

    public function imageGallery()
    {
        return $this->hasMany(ImageGallary::class, 'provider_profile_id');
    }

    // Active schedule (open time / close time)
    public function scheduleDay()
    {
        return $this->hasOne(ScheduleDay::class, 'provider_id')
            ->where('status', 'active');
    }

    public function assignRole(string|null $role): self
    {
        $this->role = $this->normalizeRole($role);
        $this->save();

        return $this;
    }

    public function hasRole(string|array|null $roles): bool
    {
        $roles = is_array($roles) ? $roles : [$roles];
        $currentRole = $this->normalizeRole($this->role);

        foreach ($roles as $role) {
            $normalizedRole = $this->normalizeRole($role);

            if ($normalizedRole === 'service_provider') {
                if (in_array($currentRole, self::SERVICE_PROVIDER_ROLES, true)) {
                    return true;
                }

                continue;
            }

            if ($normalizedRole !== null && $currentRole === $normalizedRole) {
                return true;
            }
        }

        return false;
    }

    private function normalizeRole(string|int|null $role): ?string
    {
        if ($role === null) {
            return null;
        }

        $role = (string) $role;

        return self::ROLE_ALIASES[$role] ?? $role;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // JWT Methods
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }


    public function customer_loyality()
    {
        return $this->hasMany(LoyalityAdd::class, 'customer_id');
    }


    public function barberbooking()
    {
        return $this->hasMany(Booking::class, 'barbar_id');
    }

    public function salonbooking()
    {
        return $this->hasMany(Booking::class, 'salon_id');
    }

    public function reviewRating()
    {
        return $this->hasMany(ReviewRating::class, 'customer_id');
    }

    public function salonLoyality()
    {
        return $this->hasMany(SalonLoyality::class, 'salon_id');
    }

    public function salon()
    {
        return $this->belongsTo(User::class, 'salon_id');
    }
}
