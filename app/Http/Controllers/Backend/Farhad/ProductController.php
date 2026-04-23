<?php

namespace App\Http\Controllers\Backend\Farhad;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\ProductMultiImage;
use App\Http\Controllers\Controller;
use Intervention\Image\Facades\Image;
use Yajra\DataTables\Facades\DataTables;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $products = Product::with('category')->latest()->get();

            return DataTables::of($products)
                ->addIndexColumn()
                ->addColumn('name', function ($row) {
                    return '<strong>' . $row->name . '</strong>';
                })
                ->addColumn('category', function ($row) {
                    return $row->category->name ?? 'N/A';
                })
                ->addColumn('code', function ($row) {
                    return $row->code;
                })
                ->addColumn('image', function ($row) {
                    if ($row->image) {
                        return '<img src="' . asset($row->image) . '" alt="' . $row->name . '" width="60" height="60">';
                    }
                    return '<span class="text-muted">No Image</span>';
                })
                ->addColumn('status', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="form-check form-switch form-switch-right form-switch-md">
                            <input class="form-check-input status-switch" type="checkbox" data-id="' . $row->id . '" data-type="product" ' . $checked . '>
                        </div>';
                })
                ->addColumn('action', function ($row) {
                    $action = '';
                    $action .= '<a href="' . route('admin.products.edit', $row->id) . '" class="btn btn-sm btn-primary me-1">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </a>';
                    $action .= '<form action="' . route('admin.products.destroy', $row->id) . '" method="POST" style="display:inline-block;">
                                ' . csrf_field() . method_field('DELETE') . '
                                <button type="submit" class="btn btn-sm btn-danger delete-button">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </form>';
                    return $action;
                })
                ->rawColumns(['name', 'image', 'status', 'action'])
                ->make(true);
        }

        return view('backend.layouts.products.index');
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::all();
        return view('backend.layouts.products.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // return $request->all();
        $request->validate([
            'name' => 'required|string|unique:products,name|max:255',
            'category_id' => 'required|exists:categories,id',
            'code' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'multi_images'       => 'nullable', // Ensure the array itself is not empty
            'multi_images.*'     => 'nullable|image|mimes:jpeg,png,jpg,gif', //for table
            'short_description' => 'nullable|string',
            'highlight_title' => 'nullable|string',
            'long_description' => 'nullable|string',
            'regular_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0|lte:regular_price',
            'stock' => 'required|integer|min:0',
            'status' => 'required|in:0,1',
        ]);

        // Handle image upload
        $imageUrl = null;

        if ($request->file('image')) {
            $image     = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $directory = 'uploads/products-images/';
            // Create directory if it doesn't exist
            if (!file_exists(public_path($directory))) {
                mkdir(public_path($directory), 0755, true);
            }
            // $resizedImage = Image::make($image)->resize(300, 300);
            $resizedImage = Image::make($image);
            $resizedImage->save(public_path($directory . $imageName));
            $imageUrl = $directory . $imageName;
        }



        $product = new Product();
        $product->name = $request->name;
        $product->user_id     = auth()->id();
        $product->category_id = $request->category_id;
        $product->code = $request->code;
        $product->image = $imageUrl;
        $product->short_description = $request->short_description;
        $product->highlight_title = $request->highlight_title;
        $product->long_description = $request->long_description;
        $product->regular_price = $request->regular_price;
        $product->selling_price = $request->selling_price;
        $product->stock = $request->stock;
        $product->status = $request->status;
        $product->save();

        // Multiple images
        if ($request->hasFile('multi_images')) {
            foreach ($request->file('multi_images') as $multiImage) {
                $multiName = time() . '_' . uniqid() . '.' . $multiImage->getClientOriginalExtension();
                $directory = 'uploads/products-images/multi/';
                $multiImage->move($directory, $multiName);

                $productImage = new ProductMultiImage();
                $productImage->product_id = $product->id;
                $productImage->image = $directory . $multiName;
                $productImage->save();
            }
        }

        return back()->with('success', 'New Product added successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $product = Product::with(['category'])->findOrFail($id);
        return view('backend.layouts.products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $product = Product::findOrFail($id);
        $categories = Category::all();
        return view('backend.layouts.products.edit', compact('product', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:products,name,' . $product->id,
            'category_id' => 'required|exists:categories,id',
            'code' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5048',
            'short_description' => 'nullable|string',
            'highlight_title' => 'nullable|string',
            'long_description' => 'nullable|string',
            'regular_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0|lte:regular_price',
            'stock' => 'required|integer|min:0',
            'status' => 'required|in:0,1',
        ]);

        // Image update or keep old or remove
        $imageUrl = $product->image; // default: keep old image
        if ($request->file('image')) {
            if ($product->image && file_exists($product->image)) {
                unlink($product->image);
            }

            // Upload new image
            $image     = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $directory = 'uploads/products-images/';

            // $resizedImage = Image::make($image)->resize(300, 300);
            $resizedImage = Image::make($image);
            $resizedImage->save(public_path($directory . $imageName));
            $imageUrl = $directory . $imageName;
        }

        $product->user_id     = auth()->id();
        $product->name = $request->name;
        $product->category_id = $request->category_id;
        $product->code = $request->code;
        $product->image = $imageUrl;
        $product->short_description = $request->short_description;
        $product->highlight_title = $request->highlight_title;
        $product->long_description = $request->long_description;
        $product->regular_price = $request->regular_price;
        $product->selling_price = $request->selling_price;
        $product->stock = $request->stock;
        $product->status = $request->status;
        $product->save();



        /* ---------- Remove selected multi images ---------- */
        if ($request->filled('removed_images')) {
            // removed_images is coming as an array, not JSON
            $removedIds = is_array($request->removed_images) ? $request->removed_images : [$request->removed_images];
            foreach ($removedIds as $id) {
                $img = ProductMultiImage::find($id);
                if ($img) {
                    if (file_exists(public_path($img->image))) {
                        @unlink(public_path($img->image));
                    }
                    $img->delete();
                }
            }
        }

        /* ---------- Add new multi images (do not remove old ones) ---------- */
        // Check if new multiple images uploaded
        if ($request->hasFile('multi_images')) {
            // Save new images
            foreach ($request->file('multi_images') as $multiImage) {
                $multiName = time() . '_' . uniqid() . '.' . $multiImage->getClientOriginalExtension();
                $directory = 'uploads/products-images/multi/';
                $multiImage->move(public_path($directory), $multiName);

                $productImage = new ProductMultiImage();
                $productImage->product_id = $product->id;
                $productImage->image = $directory . $multiName;
                $productImage->save();
            }
        }

        return back()->with('success', 'Product updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        // Remove image if exists
        if ($product->image && file_exists($product->image)) {
            unlink($product->image);
        }

        // ✅ Delete all multi images (physical + DB)
        $multiImages = ProductMultiImage::where('product_id', $id)->get();
        foreach ($multiImages as $multiImage) {
            if (file_exists($multiImage->image)) {
                unlink($multiImage->image);
            }
            $multiImage->delete();
        }

        $product->delete();
        return redirect()->back()->with('success', 'Product deleted successfully');
    }
}
