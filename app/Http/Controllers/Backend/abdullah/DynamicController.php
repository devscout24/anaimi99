<?php

namespace App\Http\Controllers\Backend\Abdullah;

use App\Http\Controllers\Controller;
use App\Models\Dyanamic;
use Illuminate\Http\Request;

class DynamicController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = \App\Models\Dyanamic::latest()->get();
            return \Yajra\DataTables\Facades\DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('description', function ($row) {
                    return \Illuminate\Support\Str::limit(strip_tags($row->description), 100);
                })
                ->addColumn('action', function ($row) {
                    $btn = '<a href="' . route('admin.dynamic.edit', ['dynamic' => $row->id]) . '" class="edit btn btn-primary btn-sm m-1">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </a>';
                    $btn .= '<form action="' . route('admin.dynamic.destroy', ['dynamic' => $row->id]) . '" method="POST" style="display:inline-block;">
                                ' . csrf_field() . '
                                ' . method_field('DELETE') . '';
                    $btn .= '<button type="submit" class="delete btn btn-danger btn-sm m-1" onclick="return confirm(\'Are you sure?\')">
                                <i class="fa-regular fa-trash-can"></i>
                            </button>
                            </form>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('backend.dynamic.index');
    }

    public function create()
    {
        return view('backend.dynamic.create');
    }

    public function store(Request $request)
    {
        // Validate the request data
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $dynamic = new Dyanamic();
        $dynamic->title = $request->input('title');
        $dynamic->description = $request->input('description');
        $dynamic->save();

        // Redirect back with a success message
        return redirect()->route('admin.dynamic.index')->with('success', 'Dyanamic created successfully.');
    }
    public function edit($id)
    {
        $dynamic = Dyanamic::findOrFail($id);
        return view('backend.dynamic.edit', compact('dynamic'));
    }

    public function update(Request $request, $id)
    {
        // Validate the request data
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $dynamic = Dyanamic::findOrFail($id);
        $dynamic->title = $request->input('title');
        $dynamic->description = $request->input('description');
        $dynamic->save();

        // Redirect back with a success message
        return redirect()->route('admin.dynamic.index')->with('success', 'Dyanamic updated successfully.');
    }

    public function destroy($id)
    {
        $dynamic = Dyanamic::findOrFail($id);
        $dynamic->delete();

        // Redirect back with a success message
        return redirect()->route('admin.dynamic.index')->with('success', 'Dyanamic deleted successfully.');
    }
}

