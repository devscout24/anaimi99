<?php

namespace App\Http\Controllers\Backend\abdullah;

use App\Http\Controllers\Controller;

use App\Models\Service;
use App\Models\ServicePrice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class ServicePriceController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $servicePrices = ServicePrice::query()
                ->with(['service', 'creator'])
                ->latest();

            return DataTables::of($servicePrices)
                ->addIndexColumn()
                ->addColumn('service_name', function (ServicePrice $servicePrice) {
                    return $servicePrice->service?->service_name ?? '';
                })
                ->addColumn('created_by', function (ServicePrice $servicePrice) {
                    $user = $servicePrice->creator;
                    if (!$user) {
                        return '';
                    }

                    return $user->username ?: ($user->name ?: $user->email);
                })
                ->editColumn('time', function (ServicePrice $servicePrice) {
                    return $servicePrice->time ? Carbon::parse($servicePrice->time)->format('H:i') : '';
                })
                ->addColumn('action', function (ServicePrice $servicePrice) {
                    return '
                        <button class="btn btn-sm btn-primary js-edit" data-id="' . $servicePrice->id . '">Edit</button>
                        <button class="btn btn-sm btn-danger js-delete" data-id="' . $servicePrice->id . '">Delete</button>
                    ';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $services = Service::query()->orderBy('service_name')->get(['id', 'service_name']);

        return view('admin.servicePrice.index', compact('services'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'time' => ['nullable', 'date_format:H:i'],
            'created_for_type' => ['required', Rule::in(['salon', 'barber'])],
        ]);

        $servicePrice = ServicePrice::create([
            'service_id' => $validated['service_id'],
            'created_by' => $request->user()->id,
            'price' => $validated['price'],
            'discount' => $validated['discount'] ?? null,
            'time' => $validated['time'] ?? null,
            'created_for_type' => $validated['created_for_type'],
        ]);

        return response()->json([
            'message' => 'Service price created successfully',
            'data' => $servicePrice,
        ]);
    }

    public function edit($id)
    {
        $servicePrice = ServicePrice::findOrFail($id);
        return response()->json($servicePrice);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'time' => ['nullable', 'date_format:H:i'],
            'created_for_type' => ['required', Rule::in(['salon', 'barber'])],
        ]);

        $servicePrice = ServicePrice::findOrFail($id);

        $servicePrice->update([
            'service_id' => $validated['service_id'],
            'price' => $validated['price'],
            'discount' => $validated['discount'] ?? null,
            'time' => $validated['time'] ?? null,
            'created_for_type' => $validated['created_for_type'],
        ]);

        return response()->json([
            'message' => 'Updated successfully',
        ]);
    }

    // Delete a service price

    public function destroy($id)
    {
        ServicePrice::findOrFail($id)->delete();

        return response()->json([
            'message' => 'Deleted successfully',
        ]);
    }


}
