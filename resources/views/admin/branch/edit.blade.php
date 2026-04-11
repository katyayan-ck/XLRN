@extends(backpack_view('blank'))

@section('title', 'Edit Branch - ' . $branch->name)

@push('after_styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    .readonly-value {
        background-color: #f8f9fa;
        border: 1px solid #ced4da;
        border-radius: 6px;
        padding: 10px 15px;
        min-height: 42px;
        display: flex;
        align-items: center;
    }

    .card {
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    }

    .form-control:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, .25);
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">

            <!-- Back Button -->
            {{-- <a href="{{ backpack_url('branch') }}" class="btn btn-link mb-3">
                ← Back to All Branches
            </a> --}}

            <div class="card">
                <div class="card-header text-black">
                    <h2 class="mb-0">Edit Branch Information</h2>
                </div>
                <div class="card-body">

                    <form method="POST" action="{{ backpack_url('branch/' . $branch->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">

                            <!-- Read Only Section -->
                            <div class="col-md-12 mb-4">
                                {{-- <h5 class="text-muted border-bottom pb-2">Basic Information (Read Only)</h5> --}}
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Branch ID</label>
                                        <div class="readonly-value">{{ $branch->id }}</div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Created At</label>
                                        <div class="readonly-value">
                                            {{ $branch->created_at ? $branch->created_at->format('d-m-Y H:i') : '—' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Editable Fields -->
                            <div class="col-md-4 mb-3">
                                <label>Branch Code <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control"
                                    value="{{ old('code', $branch->code) }}" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Branch Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control"
                                    value="{{ old('name', $branch->name) }}" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Short Name <span class="text-danger">*</span></label>
                                <input type="text" name="short_name" class="form-control"
                                    value="{{ old('short_name', $branch->short_name) }}" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Phone</label>
                                <input type="text" name="phone" class="form-control"
                                    value="{{ old('phone', $branch->phone) }}">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control"
                                    value="{{ old('email', $branch->email) }}">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>City <span class="text-danger">*</span></label>
                                <input type="text" name="city" class="form-control"
                                    value="{{ old('city', $branch->city) }}" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>State <span class="text-danger">*</span></label>
                                <input type="text" name="state" class="form-control"
                                    value="{{ old('state', $branch->state) }}" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Pincode <span class="text-danger">*</span></label>
                                <input type="text" name="pincode" class="form-control"
                                    value="{{ old('pincode', $branch->pincode) }}" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Is Head Office?</label>
                                <div class="form-check form-switch">
                                    <!-- Hidden fallback -->
                                    <input type="hidden" name="is_head_office" value="0">

                                    <!-- Checkbox -->
                                    <input type="checkbox" name="is_head_office" value="1" class="form-check-input" {{
                                        old('is_head_office', $branch->is_head_office) ? 'checked' : '' }}>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Is Active?</label>
                                <div class="form-check form-switch">
                                    <!-- Hidden fallback -->
                                    <input type="hidden" name="is_active" value="0">

                                    <!-- Checkbox -->
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" {{
                                        old('is_active', $branch->is_active) ? 'checked' : '' }}>
                                </div>
                            </div>


                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-success btn-lg px-5">
                                <i class="la la-save"></i> Update Branch
                            </button>
                            <a href="{{ backpack_url('branch') }}" class="btn btn-secondary btn-lg">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection