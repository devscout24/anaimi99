<?php

namespace App\Http\Controllers\Backend\abdullah;

use App\Http\Controllers\Controller;
use App\Models\Dyanamic;
use Illuminate\Http\Request;

class DynamicController extends Controller
{
    public function index(Request $request)
    {
        if(request()->ajax()) {
            $data = Dyanamic::latest()->get();
            return datatables()->of($data)
                ->addIndexColumn()
                ->addColumn('title', function($row){
                    return $row->title;
                })
                ->addColumn('description', function($row){
                    return $row->description;
                })
                ->addColumn('action', function($row){
                    $btn = '<a href="'.route('admin.dynamic.edit', $row->id).'" class="edit btn btn-primary btn-sm m-2">Edit</a>';
                    $btn .= '<form action="'.route('admin.dynamic.destroy', $row->id).'" method="POST" style="display:inline-block;">
                                '.csrf_field().'
                                '.method_field('DELETE').'';    
                    $btn .= '<button type="submit" class="delete btn btn-danger btn-sm" onclick="return confirm(\'Are you sure?\')">Delete</button>
                            </form>';
                    return $btn;
                })
                ->rawColumns(['title', 'description', 'action'])
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
