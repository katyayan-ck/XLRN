@extends(backpack_view('blank'))

@section('title', 'Add New Branch')

@push('after_styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
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

            {{-- <a href="{{ backpack_url('branch') }}" class="btn btn-link mb-3">
                ← Back to All Branches
            </a> --}}

            <div class="card">
                <div class="card-header text-black">
                    <h2 class="mb-0">Add New Branch</h2>
                </div>
                <div class="card-body">

                    <form method="POST" action="{{ backpack_url('branch') }}">
                        @csrf

                        <div class="row">

                            <div class="col-md-4 mb-3">
                                <label>Branch Code <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control" value="{{ old('code') }}" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Branch Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Short Name <span class="text-danger">*</span></label>
                                <input type="text" name="short_name" class="form-control"
                                    value="{{ old('short_name') }}" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Phone</label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>City <span class="text-danger">*</span></label>
                                <input type="text" name="city" class="form-control" value="{{ old('city') }}" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>State <span class="text-danger">*</span></label>
                                <input type="text" name="state" class="form-control" value="{{ old('state') }}"
                                    required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Pincode <span class="text-danger">*</span></label>
                                <input type="text" name="pincode" class="form-control" value="{{ old('pincode') }}"
                                    required>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label>Address</label>
                                <textarea name="address" class="form-control" rows="3">{{ old('address') }}</textarea>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Is Head Office?</label>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="is_head_office" value="0">
                                    <input type="checkbox" name="is_head_office" value="1" class="form-check-input" {{
                                        old('is_head_office') ? 'checked' : '' }}>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Is Active?</label>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" {{
                                        old('is_active', true) ? 'checked' : '' }}>
                                </div>
                            </div>

                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-success btn-lg px-5">
                                <i class="la la-save"></i> Create Branch
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
