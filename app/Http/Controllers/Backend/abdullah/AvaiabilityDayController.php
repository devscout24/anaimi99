<?php

namespace App\Http\Controllers\Backend\Abdullah;

use App\Http\Controllers\Controller;
use App\Models\AvailablityDay;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class AvaiabilityDayController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $availabilityDays = AvailablityDay::query()->latest();

            return DataTables::of($availabilityDays)
                ->addIndexColumn()
                ->addColumn('status', function (AvailablityDay $availabilityDay) {
                    $isActive = (bool) $availabilityDay->is_active;
                    $label = $isActive ? 'Active' : 'Inactive';
                    $btnClass = $isActive ? 'btn-success' : 'btn-secondary';

                    return '<button class="btn btn-sm ' . $btnClass . ' js-toggle-status" data-id="' . $availabilityDay->id . '">' . $label . '</button>';
                })
                ->addColumn('action', function (AvailablityDay $availabilityDay) {
                    return '<button class="btn btn-sm btn-danger js-delete" data-id="' . $availabilityDay->id . '">
                                <i class="fa-regular fa-trash-can"></i>
                            </button>';
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('admin.availabilityDays.index');
    }

    public function store(Request $request)
    {
        $hasLegacyProviderColumns = Schema::hasColumns('availability_days', ['provider_id', 'provider_type']);

        $validated = $request->validate([
            'day_name' => ['required', Rule::in(AvailablityDay::DAYS)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data = [
            'day_name' => $validated['day_name'],
            'is_active' => $validated['is_active'] ?? true,
        ];

        if ($hasLegacyProviderColumns) {
            $providerId = User::query()->value('id');

            if (!$providerId) {
                return response()->json([
                    'message' => 'No user found for provider_id.',
                ], 422);
            }

            $data['provider_id'] = $providerId;
            $data['provider_type'] = 'salon';
        }

        $search = ['day_name' => $data['day_name']];
        if ($hasLegacyProviderColumns) {
            $search['provider_id'] = $data['provider_id'];
            $search['provider_type'] = $data['provider_type'];
        }

        $availabilityDay = AvailablityDay::query()->firstOrCreate($search, ['is_active' => $data['is_active']]);

        if (!$availabilityDay->wasRecentlyCreated) {
            return response()->json([
                'message' => 'This day already exists.',
                'errors' => [
                    'day_name' => ['This day already exists.'],
                ],
            ], 422);
        }

        return response()->json([
            'message' => 'Availability day created successfully',
            'data' => $availabilityDay,
        ]);
    }

    public function destroy($id)
    {
        $availabilityDay = AvailablityDay::findOrFail($id);
        $availabilityDay->delete();

        return response()->json([
            'message' => 'Availability day deleted successfully',
        ]);
    }

    public function toggleStatus($id)
    {
        $availabilityDay = AvailablityDay::findOrFail($id);

        $availabilityDay->update([
            'is_active' => !$availabilityDay->is_active,
        ]);

        return response()->json([
            'message' => 'Status updated successfully',
            'data' => $availabilityDay,
        ]);
    }
}

