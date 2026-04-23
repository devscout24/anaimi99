<?php

namespace App\Http\Controllers\Backend\Farhad;

use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Yajra\DataTables\Facades\DataTables;

class CategoryController extends Controller
{

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $categories = Category::latest()->get();

            return DataTables::of($categories)
                ->addIndexColumn()
                ->addColumn('image', function ($row) {
                    if ($row->image) {
                        return '<img src="' . asset($row->image) . '" alt="' . $row->name . '" width="60" height="60">';
                    }
                    return '<span class="text-muted">No Image</span>';
                })
                ->addColumn('status', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="form-check form-switch form-switch-right form-switch-md">
                    <input class="form-check-input status-switch" type="checkbox" data-id="' . $row->id . '"data-type="category"' . $checked . '>
                </div>';
                })
                ->addColumn('action', function ($row) {
                    $action = '';
                    $action .= '<a href="' . route('admin.categories.edit', $row->id) . '" class="btn btn-sm btn-primary me-1">
                           <i class="fa-regular fa-pen-to-square"></i>
                        </a>';
                    $action .= '<form action="' . route('admin.categories.destroy', $row->id) . '" method="POST" style="display:inline-block;">
                            ' . csrf_field() . method_field('DELETE') . '
                            <button type="submit" class="btn btn-sm btn-danger delete-button">
                                <i class="fa-regular fa-trash-can"></i>
                            </button>
                        </form>';
                    return $action;
                })
                ->rawColumns(['image', 'status', 'action'])
                ->make(true);
        }

        return view('backend.layouts.categories.index');
    }


    public function create()
    {
        return view('backend.layouts.categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:categories,name|max:255',
            'image' => 'nullable|mimes:jpg,jpeg,png,webp|max:10240', // less than 10MB
            'description' => 'nullable|string',
            'status' => 'required|in:0,1',
        ]);

        $imageUrl = null;

        if ($request->file('image')) {
            $image     = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $directory = 'uploads/categories-images/';

            if (!file_exists(public_path($directory))) {
                mkdir(public_path($directory), 0755, true);
            }

            $image->move($directory, $imageName);
            $imageUrl = $directory . $imageName;
        }

        $category = new Category();
        $category->user_id     = auth()->id();
        $category->name        = $request->name;
        $category->slug        = Str::slug($request->name);
        $category->image       = $imageUrl;
        $category->description = $request->description;
        $category->status      = $request->status;
        $category->save();

        return back()->with('success', 'New Category information added successfully');
    }

    public function edit($id)
    {
        $category = Category::findOrFail($id);
        return view('backend.layouts.categories.edit', compact('category'));
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'image' => 'nullable|mimes:jpg,jpeg,png,webp|max:10240', // less than 10MB
            'description' => 'nullable|string',
            'status' => 'required|in:0,1',
        ]);

        $imageUrl = $category->image;

        if ($request->file('image')) {
            if ($category->image && file_exists(public_path($category->image))) {
                unlink(public_path($category->image));
            }

            $image     = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $directory = 'uploads/categories-images/';

            $image->move(public_path($directory), $imageName);
            $imageUrl = $directory . $imageName;
        }

        $category->user_id     = auth()->id();
        $category->name        = $request->name;
        $category->slug        = Str::slug($request->name);
        $category->image       = $imageUrl;
        $category->description = $request->description;
        $category->status      = $request->status;
        $category->save();

        return back()->with('success', 'Category information updated successfully');
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        if ($category->image && file_exists(public_path($category->image))) {
            unlink(public_path($category->image));
        }

        $category->delete();

        return redirect()->back()->with('success', 'Category deleted successfully.');
    }
}
