@extends('backend.app')
@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                <h4 class="mb-sm-0">Categories</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                        <li class="breadcrumb-item active">Category Edit</li>
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
                    <h4 class="card-title mb-0 flex-grow-1">Category Edit</h4>
                </div>

                <form action="{{ route('admin.categories.update', $category->id) }}" method="POST" enctype="multipart/form-data">
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
                                    <input type="text" name="name" id="name" class="form-control" placeholder="Enter Name" value="{{ old('name', $category->name) }}">
                                    @error('name')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            {{-- Slug Field (readonly) --}}
                            <div class="col-xxl-12 col-md-12">
                                <div>
                                    <label for="slug" class="form-label">Slug</label>
                                    <input type="text" name="slug" id="slug" class="form-control" placeholder="Auto-generated slug" value="{{ old('slug', $category->slug) }}" readonly>
                                </div>
                            </div>

                            {{-- image Field --}}
                            <div class="col-xxl-12 col-md-12">
                                <div>
                                    <label for="imageUpload" class="form-label">Image</label>
                                    <input type="file" name="image" id="imageUpload" class="form-control" accept="image/*">
                                    @error('image')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            {{-- Description Field --}}
                            <div class="col-xxl-12 col-md-12">
                                <div>
                                    <label for="description" class="form-label">Description</label>
                                    <textarea name="description" id="description" class="form-control" rows="4" placeholder="Enter description">{{ old('description', $category->description) }}</textarea>
                                    @error('description')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            {{-- Status Field --}}
                            <div class="mb-3">
                                <label class="form-label" for="statusSelect">Status</label>
                                <select class="form-select @error('status') is-invalid @enderror" name="status" id="statusSelect">
                                    <option value="" disabled>Choose...</option>
                                    <option value="1" {{ old('status', $category->status) == 1 ? 'selected' : '' }}>Published</option>
                                    <option value="0" {{ old('status', $category->status) == 0 ? 'selected' : '' }}>Unpublished</option>
                                </select>
                                @error('status')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Submit Button --}}
                            <div class="col-xxl-12 col-md-12">
                                <button type="submit" class="btn btn-primary">Update</button>
                            </div>

                        </div>
                    </div>
                </form>

            </div>
        </div>
        <!--end col-->
    </div>
    <!--end row-->
@endsection

{{-- Push the script --}}
@push('scripts')
    <script>
        // Slug auto-generation
        document.getElementById('name').addEventListener('input', function() {
            let name = this.value;
            let slug = name.toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-');
            document.getElementById('slug').value = slug;
        });

        // image Preview
    </script>
@endpush
