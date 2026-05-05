<?php

namespace App\Http\Controllers\Backend\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ClientManagementController extends Controller
{
    public function manageSalons(Request $request)
    {
        if ($request->ajax()) {
            $data = User::where('role', 'salon')->latest()->get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('status_label', function ($row) {
                    $class = $row->status == 'approved' ? 'success' : ($row->status == 'pending' ? 'warning' : 'danger');
                    return '<span class="badge badge-' . $class . '">' . ucfirst($row->status) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $btn = '<a href="javascript:void(0)" data-id="' . $row->id . '" class="btn btn-info btn-sm viewDetails"><i class="ri-eye-line"></i></a> ';

                    if ($row->status == 'pending') {
                        $btn .= '<a href="javascript:void(0)" data-id="' . $row->id . '" class="btn btn-success btn-sm approveUser">Approve</a> ';
                        $btn .= '<a href="javascript:void(0)" data-id="' . $row->id . '" class="btn btn-danger btn-sm cancelUser">Cancel</a>';
                    } else {
                        if ($row->status == 'approved') {
                            $btn .= '<a href="javascript:void(0)" data-id="' . $row->id . '" class="btn btn-warning btn-sm cancelUser">Block</a>';
                        } else {
                            $btn .= '<a href="javascript:void(0)" data-id="' . $row->id . '" class="btn btn-success btn-sm approveUser">Unblock/Approve</a>';
                        }
                    }
                    return $btn;
                })
                ->rawColumns(['status_label', 'action'])
                ->make(true);
        }
        return view('backend.layouts.admin.manage_clients.salons');
    }

    public function manageBarbers(Request $request)
    {
        if ($request->ajax()) {
            $data = User::where('role', 'home_barbar')->latest()->get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('status_label', function ($row) {
                    $class = $row->status == 'approved' ? 'success' : ($row->status == 'pending' ? 'warning' : 'danger');
                    return '<span class="badge badge-' . $class . '">' . ucfirst($row->status) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $btn = '<a href="javascript:void(0)" data-id="' . $row->id . '" class="btn btn-info btn-sm viewDetails"><i class="ri-eye-line"></i></a> ';

                    if ($row->status == 'pending') {
                        $btn .= '<a href="javascript:void(0)" data-id="' . $row->id . '" class="btn btn-success btn-sm approveUser">Approve</a> ';
                        $btn .= '<a href="javascript:void(0)" data-id="' . $row->id . '" class="btn btn-danger btn-sm cancelUser">Cancel</a>';
                    } else {
                        if ($row->status == 'approved') {
                            $btn .= '<a href="javascript:void(0)" data-id="' . $row->id . '" class="btn btn-warning btn-sm cancelUser">Block</a>';
                        } else {
                            $btn .= '<a href="javascript:void(0)" data-id="' . $row->id . '" class="btn btn-success btn-sm approveUser">Unblock/Approve</a>';
                        }
                    }
                    return $btn;
                })
                ->rawColumns(['status_label', 'action'])
                ->make(true);
        }
        return view('backend.layouts.admin.manage_clients.barbers');
    }

    public function updateStatus(Request $request)
    {
        $user = User::findOrFail($request->id);
        $user->status = $request->status;
        $user->save();

        return response()->json(['success' => 'Status updated successfully!']);
    }

    public function getDetails($id)
    {
        $user = User::findOrFail($id);
        return response()->json($user);
    }
}
