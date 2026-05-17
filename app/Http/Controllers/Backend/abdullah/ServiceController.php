<?php

namespace App\Http\Controllers\Backend\Abdullah;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ServiceController extends Controller
{
    // INDEX
    public function index()
    {
        if (request()->ajax()) {
            $services = Service::latest();

            return DataTables::of($services)
                ->addIndexColumn()
                ->addColumn('action', function ($service) {
                    return '
                        <button class="btn btn-sm btn-primary js-edit" data-id="' . $service->id . '">
                            <i class="fa-regular fa-pen-to-square"></i>
                        </button>
                        <button class="btn btn-sm btn-danger js-delete" data-id="' . $service->id . '">
                            <i class="fa-regular fa-trash-can"></i>
                        </button>
                    ';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('admin.services.index');
    }

    // STORE (AJAX)
    public function store(Request $request)
    {
        $request->validate([
            'service_name' => 'required|unique:services,service_name',
        ]);

        $service = Service::create([
            'service_name' => $request->service_name,
        ]);

        return response()->json([
            'message' => 'Service created successfully',
            'data' => $service
        ]);
    }

    // EDIT
    public function edit($id)
    {
        $service = Service::findOrFail($id);

        return response()->json($service);
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $request->validate([
            'service_name' => 'required|unique:services,service_name,' . $id,
        ]);

        $service = Service::findOrFail($id);

        $service->update([
            'service_name' => $request->service_name,
        ]);

        return response()->json([
            'message' => 'Updated successfully'
        ]);
    }

    // DELETE
    public function destroy($id)
    {
        Service::findOrFail($id)->delete();

        return response()->json([
            'message' => 'Deleted successfully'
        ]);
    }
}

