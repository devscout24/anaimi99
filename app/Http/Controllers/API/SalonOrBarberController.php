<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SalonOrBarberController extends Controller
{
    use ApiResponse;

    /**
     * Get list of salons & home barbers sorted by distance.
     *
     * Query Params:
     *  - latitude    (required) : user's current latitude
     *  - longitude   (required) : user's current longitude
     *  - radius      (optional) : search radius in KM for home_barbar, default 50
     *  - search      (optional) : search by name or salon_address (from provider_profiles)
     *  - type        (optional) : 'salon' | 'home_barbar' | null = both
     *                             → salon      : radius restriction yok, name/address search
     *                             → home_barbar: only within radius, sorted by distance
     *  - available   (optional) : 1 = only available, 0 = all
     *  - per_page    (optional) : pagination, default 15
     */
    public function index(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius'    => 'nullable|numeric|min:1|max:500',
            'search'    => 'nullable|string|max:100',
            'type'      => 'nullable|in:salon,home_barbar',
            'available' => 'nullable|boolean',
            'per_page'  => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->first());
        }

        try {
            $userLat  = (float) $request->latitude;
            $userLng  = (float) $request->longitude;
            $radius   = $request->radius   ?? 50;
            $perPage  = $request->per_page ?? null;
            $type     = $request->type;


            $distanceSql = '( 6371 * ACOS( COS( RADIANS(?) ) * COS( RADIANS(latitude) ) *
                              COS( RADIANS(longitude) - RADIANS(?) ) +
                              SIN( RADIANS(?) ) * SIN( RADIANS(latitude) ) ) )';

            // Base query with distance column
            $query = User::selectRaw("
                        users.id, users.name, users.phone, users.email,
                        users.profile_image, users.cover_image,
                        users.latitude, users.longitude,
                        users.role, users.availability, users.status,
                        users.salon_barbar_status,
                        {$distanceSql} AS distance",
                        [$userLat, $userLng, $userLat]
                    )
                    ->where('users.status', 'approved')
                    ->where('users.block_status', 'unblock')
                    ->whereNotNull('users.latitude')
                    ->whereNotNull('users.longitude');

            // ── TYPE LOGIC ───────────────────────────────────────────────────
            if ($type === 'salon') {
                /*
                 * SALON MODE
                 * - No radius restriction (show all salons)
                 * - Search: users.name  OR  provider_profiles.salon_address
                 * - Join provider_profiles so we can search salon_address & return business info
                 */
                $query->where('users.role', 'salon')
                      ->leftJoin('provider_profiles', 'provider_profiles.user_id', '=', 'users.id')
                      ->addSelect([
                          'provider_profiles.business_name',
                          'provider_profiles.salon_address',
                          'provider_profiles.about',
                      ]);

                if ($request->filled('search')) {
                    $search = '%' . $request->search . '%';
                    $query->where(function ($q) use ($search) {
                        $q->where('users.name', 'like', $search)
                          ->orWhere('provider_profiles.business_name', 'like', $search)
                          ->orWhere('provider_profiles.salon_address', 'like', $search);
                    });
                }

            } elseif ($type === 'home_barbar') {
                /*
                 * HOME BARBAR MODE
                 * - Radius restriction ON (nearest barbers only)
                 * - Search: users.name only
                 */
                $query->where('users.role', 'home_barbar')
                      ->havingRaw("{$distanceSql} <= ?", [$userLat, $userLng, $userLat, $radius]);

                if ($request->filled('search')) {
                    $search = '%' . $request->search . '%';
                    $query->where(function ($q) use ($search) {
                        $q->where('users.name', 'like', $search)
                          ->orWhere('users.phone', 'like', $search);
                    });
                }

            } else {
                /*
                 * BOTH (no type sent)
                 * - Show salon + home_barbar within radius
                 * - Search: name only
                 */
                $query->whereIn('users.role', ['salon', 'home_barbar'])
                      ->havingRaw("{$distanceSql} <= ?", [$userLat, $userLng, $userLat, $radius]);

                if ($request->filled('search')) {
                    $search = '%' . $request->search . '%';
                    $query->where(function ($q) use ($search) {
                        $q->where('users.name', 'like', $search)
                          ->orWhere('users.phone', 'like', $search);
                    });
                }
            }

            // ── AVAILABILITY FILTER ───────────────────────────────────────────
            if ($request->filled('available') && (int) $request->available === 1) {
                $query->where('users.availability', true);
            }

            // ── ORDER: available first → nearest ─────────────────────────────
            $query->orderByDesc('users.availability')
                  ->orderBy('distance', 'asc');

            // ── EXECUTE QUERY ─────────────────────────────────────────────────
            // per_page না দিলে সব data এক সাথে দেখায়, দিলে paginate করে
            if ($perPage === null) {
                $collection = $query->with(['imageGallery', 'scheduleDay'])->get();

                $data = $collection->map(function ($user) use ($type) {
                    return $this->formatUser($user, $type === 'salon');
                });

                return $this->success([
                    'list'  => $data,
                    'total' => $collection->count(),
                ], 'Salon / Barber list fetched successfully');

            } else {
                $results = $query->with(['imageGallery', 'scheduleDay'])->paginate($perPage);

                $data = $results->getCollection()->map(function ($user) use ($type) {
                    return $this->formatUser($user, $type === 'salon');
                });

                return $this->success([
                    'list'         => $data,
                    'current_page' => $results->currentPage(),
                    'last_page'    => $results->lastPage(),
                    'total'        => $results->total(),
                    'per_page'     => $results->perPage(),
                ], 'Salon / Barber list fetched successfully');
            }

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }



    /**
     * Get a single salon or home barber details by ID.
     */
    public function show($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude'  => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->first());
        }

        try {
            $user = User::with(['imageGallery', 'scheduleDay'])
                ->whereIn('role', ['salon', 'home_barbar'])
                ->where('block_status', 'unblock')
                ->find($id);

            if (!$user) {
                return $this->notFound([], 'Salon or barber not found.');
            }

            // Calculate distance if caller sends their location
            $distance = null;
            if ($request->filled('latitude') && $request->filled('longitude') &&
                $user->latitude && $user->longitude) {
                $distance = $this->haversine(
                    (float) $request->latitude,
                    (float) $request->longitude,
                    (float) $user->latitude,
                    (float) $user->longitude
                );
            }

            $formatted            = $this->formatUser($user);
            $formatted['distance_km'] = $distance ? round($distance, 2) : null;

            return $this->success($formatted, 'Details fetched successfully');

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }


    // ──────────────────────────────────────────────
    //  PRIVATE HELPERS
    // ──────────────────────────────────────────────

    /**
     * Format a user object into a clean response array.
     * $isSalon = true when the query already joined provider_profiles (type=salon).
     */
    private function formatUser(User $user, bool $isSalon = false): array
    {
        // Gallery images with full URL
      $gallery = $user->imageGallery
    ? $user->imageGallery->map(function ($img) {
        return [
            'id' => $img->id,
            'image' => $img->image ? asset($img->image) : null,
        ];
    })->values()
    : [];

        // Schedule / open-close time
        $schedule  = $user->scheduleDay;
        $openTime  = $schedule?->start_time ?? null;
        $closeTime = $schedule?->end_time   ?? null;
        $isOpenNow = false;

        if ($openTime && $closeTime) {
            $now       = now()->format('H:i:s');
            $isOpenNow = ($now >= $openTime && $now <= $closeTime);
        }

        $response = [
            'id'                  => $user->id,
            'name'                => $user->name,
            'phone'               => $user->phone,
            'email'               => $user->email,
            'role'                => $user->role,
            'profile_image'       => $user->profile_image
                                        ? asset($user->profile_image)
                                        : null,
            'cover_image'         => $user->cover_image
                                        ? asset($user->cover_image)
                                        : null,
            'gallery_images'      => $gallery,
            'latitude'            => $user->latitude,
            'longitude'           => $user->longitude,
            'availability'        => (bool) $user->availability,
            'status'              => $user->status,
            'salon_barbar_status' => (bool) $user->salon_barbar_status,
            'distance_km'         => isset($user->distance)
                                        ? round((float) $user->distance, 2)
                                        : null,
            // Schedule
            'open_time'           => $openTime,
            'close_time'          => $closeTime,
            'is_open_now'         => $isOpenNow,
            'schedule_duration'   => $schedule?->schedule_duration ?? null,
            'buffer_time'         => $schedule?->buffer_time        ?? null,
            'break_time'          => $schedule?->break_time         ?? null,
        ];

        // Extra fields only for salon (joined from provider_profiles)
        if ($isSalon) {
            $response['business_name']  = $user->business_name  ?? null;
            $response['salon_address']  = $user->salon_address  ?? null;
            $response['about']          = $user->about          ?? null;
        }

        return $response;
    }



    /**
     * PHP-side Haversine distance calculation (KM).
     */
    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * asin(sqrt($a));
    }
}

