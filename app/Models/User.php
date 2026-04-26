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
        'name',
        'email',
        'password',
        'role',
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

    public function providerprofiles()
    {
        return $this->hasOne(ProviderProfile::class, 'user_id');
    }

    public function imageGallery()
    {
        return $this->hasMany(ImageGallary::class, 'provider_profile_id');
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
}
