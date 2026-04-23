@extends('backend.app')
@section('content')
    @php
        // Prepare existing images from DB
        $existingImages = $product->productMultiImages->map(function ($img) {
            return asset($img->image); // use "image" column
        });
    @endphp
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                <h4 class="mb-sm-0">Products</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                        <li class="breadcrumb-item active">Product Edit</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Product Edit</h4>
                </div>

                <form action="{{ route('admin.products.update', $product->id) }}" method="POST" enctype="multipart/form-data" id="productForm">
                    @csrf
                    @method('PUT')

                    <div class="card-body">
                        <div class="row gy-4">

                            {{-- User ID Field (hidden) --}}
                            <input type="hidden" name="user_id" value="{{ auth()->id() }}">

                            {{-- Name Field --}}
                            <div class="col-xxl-12 col-md-12">
                                <div>
                                    <label for="name" class="form-label">Name</label>
                                    <input type="text" name="name" id="name" class="form-control" placeholder="Enter Product Name" value="{{ old('name', $product->name) }}">
                                    @error('name')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            {{-- Slug Field --}}
                            <div class="col-xxl-12 col-md-12">
                                <div>
                                    <label for="slug" class="form-label">Slug</label>
                                    <input type="text" name="slug" id="slug" class="form-control" placeholder="Auto-generated slug" value="{{ old('slug', $product->slug) }}" readonly>
                                </div>
                            </div>

                            {{-- Category --}}
                            <div class="col-xxl-12 col-md-12">
                                <label for="category_id" class="form-label">Category</label>
                                <select class="form-select @error('category_id') is-invalid @enderror" name="category_id" id="category_id">
                                    <option value="" disabled selected>Choose Category</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Code --}}
                            <div class="col-xxl-12 col-md-12">
                                <div>
                                    <label for="code" class="form-label">Product Code</label>
                                    <input type="text" name="code" id="code" class="form-control" placeholder="Enter Product Code" value="{{ old('code', $product->code) }}">
                                    @error('code')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            {{-- Image --}}
                            <div class="col-xxl-12 col-md-12">
                                <div>
                                    <label for="image" class="form-label">Product Image</label>
                                    <input type="file" name="image" id="image" class="form-control dropify" data-allowed-file-extensions="jpg jpeg png gif"
                                        @if ($product->image) data-default-file="{{ asset($product->image) }}" @endif>
                                    @error('image')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            {{-- Product Multi Images --}}
                            <div class="col-xxl-12 col-md-12 mt-3">
                                <div>
                                    <label for="multi_images" class="form-label">Product Multi Images</label>
                                    <input type="file" name="multi_images[]" id="multi_images" class="form-control" multiple data-allowed-file-extensions="jpg jpeg png gif">
                                    {{-- Preview area --}}
                                    <div id="preview_multi_images" class="mt-3 d-flex flex-wrap gap-2">
                                        {{-- Show DB Images initially --}}
                                        @foreach ($product->productMultiImages as $productMultiImage)
                                            <div class="position-relative d-inline-block db-image">
                                                <img src="{{ asset($productMultiImage->image) }}" alt="Product Image" height="150" width="150" class="rounded border p-1 shadow-sm">

                                                {{-- Remove button for DB image --}}
                                                <span class="remove-img-btn position-absolute top-0 end-0 bg-danger text-white rounded-circle d-flex align-items-center justify-content-center"
                                                    style="width:24px;height:24px;cursor:pointer;" data-id="{{ $productMultiImage->id }}">
                                                    &times;
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                    @error('multi_images')
                                        @if ($errors->has('multi_images'))
                                            {{ $errors->first('multi_images') }}
                                        @endif
                                        @foreach ($errors->get('multi_images.*') as $message)
                                            {{ $message[0] }}<br>
                                        @endforeach
                                    @enderror
                                </div>
                            </div>

                            {{-- Short Description --}}
                            <div class="col-xxl-12 col-md-12">
                                <label for="short_description" class="form-label">Short Description</label>
                                <textarea name="short_description" id="short_description" class="form-control" rows="3">{{ old('short_description', $product->short_description) }}</textarea>
                            </div>

                            {{-- Highlight Title --}}
                            <div class="col-xxl-12 col-md-12">
                                <label for="highlight_title" class="form-label">Highlight Title</label>
                                <textarea name="highlight_title" id="highlight_title" class="form-control" rows="3">{{ old('highlight_title', $product->highlight_title) }}</textarea>
                            </div>

                            {{-- Long Description --}}
                            <div class="col-xxl-12 col-md-12">
                                <label for="long_description" class="form-label">Long Description</label>
                                <textarea name="long_description" id="long_description" class="ckeditor">{{ old('long_description', $product->long_description) }}</textarea>
                            </div>

                            {{-- Regular Price --}}
                            <div class="col-xxl-6 col-md-6">
                                <div>
                                    <label for="regular_price" class="form-label">Regular Price</label>
                                    <input type="number" name="regular_price" id="regular_price" class="form-control" placeholder="Enter Regular Price" step="0.01"
                                        value="{{ old('regular_price', $product->regular_price) }}">
                                    @error('regular_price')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            {{-- Selling Price --}}
                            <div class="col-xxl-6 col-md-6">
                                <div>
                                    <label for="selling_price" class="form-label">Selling Price</label>
                                    <input type="number" name="selling_price" id="selling_price" class="form-control" placeholder="Enter Selling Price" step="0.01"
                                        value="{{ old('selling_price', $product->selling_price) }}">
                                    @error('selling_price')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            {{-- Stock --}}
                            <div class="col-xxl-6 col-md-6">
                                <div>
                                    <label for="stock" class="form-label">Stock</label>
                                    <input type="number" name="stock" id="stock" class="form-control" placeholder="Enter Stock Quantity" value="{{ old('stock', $product->stock) }}">
                                    @error('stock')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            {{-- Status --}}
                            <div class="col-xxl-6 col-md-6">
                                <label class="form-label" for="statusSelect">Status</label>
                                <select class="form-select @error('status') is-invalid @enderror" name="status" id="statusSelect">
                                    <option value="1" {{ old('status', $product->status) == 1 ? 'selected' : '' }}>Published</option>
                                    <option value="0" {{ old('status', $product->status) == 0 ? 'selected' : '' }}>Unpublished</option>
                                </select>
                                @error('status')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Submit --}}
                            <div class="col-xxl-12 col-md-12">
                                <button type="submit" class="btn btn-primary">Update Product</button>
                            </div>

                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('name').addEventListener('input', function() {
            let name = this.value;
            let slug = name.toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-');
            document.getElementById('slug').value = slug;
        });
    </script>
    <script>
        const previewContainer = document.getElementById('preview_multi_images');

        // Remove DB or new image
        previewContainer.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-img-btn')) {
                const wrapper = e.target.closest('div');
                // If it's a DB image, add hidden input to track removal
                if (wrapper.classList.contains('db-image')) {
                    const removedInput = document.createElement('input');
                    removedInput.type = 'hidden';
                    removedInput.name = 'removed_images[]';
                    removedInput.value = e.target.getAttribute('data-id');
                    previewContainer.appendChild(removedInput);
                }
                wrapper.remove();
            }
        });

        // Add newly selected images
        document.getElementById('multi_images').addEventListener('change', function(event) {
            Array.from(event.target.files).forEach(file => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const wrapper = document.createElement('div');
                        wrapper.classList.add('position-relative', 'd-inline-block', 'new-preview');

                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.height = 150;
                        img.width = 150;
                        img.classList.add('rounded', 'border', 'p-1', 'shadow-sm');
                        img.style.border = "2px dashed #28a745";
                        img.style.padding = "4px";

                        const btn = document.createElement('span');
                        btn.innerHTML = "&times;";
                        btn.classList.add('remove-img-btn', 'position-absolute', 'top-0', 'end-0', 'bg-danger', 'text-white', 'rounded-circle', 'd-flex', 'align-items-center',
                            'justify-content-center');
                        btn.style.width = "24px";
                        btn.style.height = "24px";
                        btn.style.cursor = "pointer";

                        wrapper.appendChild(img);
                        wrapper.appendChild(btn);
                        previewContainer.appendChild(wrapper);
                    }
                    reader.readAsDataURL(file);
                }
            });
        });
    </script>
@endpush
