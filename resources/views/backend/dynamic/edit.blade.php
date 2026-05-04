@extends('backend.app')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs5.min.css" rel="stylesheet">
    <style>
        .dynamic-create-page {
            max-width: 980px;
            margin: 0 auto;
        }

        .dynamic-create-card {
            border: 0;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .dynamic-create-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #fff;
            padding: 1.25rem 1.5rem;
        }

        .dynamic-create-header h4 {
            margin: 0;
            font-weight: 700;
            letter-spacing: 0.2px;
        }

        .dynamic-create-body {
            padding: 1.5rem;
            background: #fff;
        }

        .dynamic-create-body .form-label {
            font-weight: 600;
            color: #334155;
        }

        .dynamic-create-body .form-control {
            border-radius: 12px;
            min-height: 46px;
            border-color: #dbe3ef;
        }

        .dynamic-create-body .note-editor.note-frame {
            border-radius: 12px;
            border-color: #dbe3ef;
            overflow: hidden;
        }

        .dynamic-create-actions {
            display: flex;
            gap: .75rem;
            justify-content: flex-end;
            margin-top: 1.25rem;
        }
    </style>
@endpush

@section('content')
    <div class="container dynamic-create-page py-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card dynamic-create-card">
                    <div class="dynamic-create-header d-flex justify-content-between align-items-center gap-3">
                        <div>
                            <h4>Edit Dynamic Content</h4>
                        </div>
                        <a href="{{ route('admin.dynamic.index') }}" class="btn btn-light btn-sm">Back to list</a>
                    </div>
                    <div class="dynamic-create-body">
                        <form action="{{ route('admin.dynamic.update', $dynamic->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="mb-3">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" name="title" class="form-control" id="title" value="{{ old('title', $dynamic->title) }}" placeholder="Enter title">
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea name="description" class="form-control summernote" id="description" placeholder="Enter description">{{ old('description', $dynamic->description) }}</textarea>
                            </div>

                            <div class="dynamic-create-actions">
                                <a href="{{ route('admin.dynamic.index') }}" class="btn btn-light">Cancel</a>
                                <button type="submit" class="btn btn-primary px-4">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.summernote').summernote({
                height: 220,
                placeholder: 'Enter description',
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'underline', 'italic', 'clear']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link', 'picture', 'video']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ],
                callbacks: {
                    onPaste: function(e) {
                        const clipboardData = (e.originalEvent || e).clipboardData || window.clipboardData;

                        if (!clipboardData) {
                            return;
                        }

                        e.preventDefault();
                        const text = clipboardData.getData('text/plain');
                        document.execCommand('insertText', false, text);
                    }
                }
            });
        });
    </script>
@endpush
