@extends(backpack_view('blank'))

@section('title', 'Edit Post - ' . $post->title)

@push('after_styles')
<style>
    .card {
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    }

    .form-control:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, .25);
    }

    .readonly-value {
        background-color: #f8f9fa;
        border: 1px solid #ced4da;
        border-radius: 6px;
        padding: 10px 15px;
        min-height: 42px;
        display: flex;
        align-items: center;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header text-black">
                    <h2 class="mb-0">Edit Post Information</h2>
                </div>
                <div class="card-body">

                    <form method="POST" action="{{ backpack_url('post/' . $post->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <!-- Read Only -->
                            <div class="col-md-12 mb-4">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Post ID</label>
                                        <div class="readonly-value">{{ $post->id }}</div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Created At</label>
                                        <div class="readonly-value">
                                            {{ $post->created_at?->format('d-m-Y H:i') ?? '—' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Post Code <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control"
                                    value="{{ old('code', $post->code) }}" required>
                            </div>

                            <div class="col-md-8 mb-3">
                                <label>Post Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control"
                                    value="{{ old('title', $post->title) }}" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Branch <span class="text-danger">*</span></label>
                                <select name="branch_id" class="form-control form-select" required>
                                    <option value="">Select Branch</option>
                                    @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id', $post->branch_id) ==
                                        $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Department <span class="text-danger">*</span></label>
                                <select name="department_id" class="form-control form-select" required>
                                    <option value="">Select Department</option>
                                    @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id', $post->department_id) ==
                                        $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Designation <span class="text-danger">*</span></label>
                                <select name="designation_id" class="form-control form-select" required>
                                    <option value="">Select Designation</option>
                                    @foreach($designations as $desig)
                                    <option value="{{ $desig->id }}" {{ old('designation_id', $post->designation_id) ==
                                        $desig->id ? 'selected' : '' }}>
                                        {{ $desig->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Max Assignees <span class="text-danger">*</span></label>
                                <input type="number" name="max_assignees" class="form-control"
                                    value="{{ old('max_assignees', $post->max_assignees) }}" min="1" required>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label>Description</label>
                                <textarea name="description" class="form-control"
                                    rows="3">{{ old('description', $post->description) }}</textarea>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Is Active?</label>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" {{
                                        old('is_active', $post->is_active) ? 'checked' : '' }}>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Is Vacant?</label>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="is_vacant" value="0">
                                    <input type="checkbox" name="is_vacant" value="1" class="form-check-input" {{
                                        old('is_vacant', $post->is_vacant) ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-success btn-lg px-5">
                                <i class="la la-save"></i> Update Post
                            </button>
                            <a href="{{ backpack_url('post') }}" class="btn btn-secondary btn-lg">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
