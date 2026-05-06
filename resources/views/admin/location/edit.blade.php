@extends(backpack_view('blank'))

@section('title', 'Edit Location - ' . $location->name)

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

            <div class="card">
                <div class="card-header text-black">
                    <h2 class="mb-0">Edit Location Information</h2>
                </div>

                <div class="card-body">

                    <form method="POST" action="{{ backpack_url('location/' . $location->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">

                            <!-- READ ONLY -->
                            <div class="col-md-12 mb-4">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Location ID</label>
                                        <div class="readonly-value">{{ $location->id }}</div>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Created At</label>
                                        <div class="readonly-value">
                                            {{ $location->created_at ? $location->created_at->format('d-m-Y H:i') : '—'
                                            }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Code -->
                            <div class="col-md-3 mb-3">
                                <label>Code <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control"
                                    value="{{ old('code', $location->code) }}" required>
                            </div>

                            <!-- Name -->
                            <div class="col-md-3 mb-3">
                                <label>Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control"
                                    value="{{ old('name', $location->name) }}" required>
                            </div>

                            <!-- Branch -->
                            <div class="col-md-3 mb-3">
                                <label>Branch <span class="text-danger">*</span></label>
                                <select name="branch_code" class="form-control form-select" required>
                                    <option value="">Select Branch</option>
                                    @foreach($branches as $branch)
                                    <option value="{{ $branch->code }}" {{ old('branch_code', $location->branch_code) ==
                                        $branch->code ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Description -->
                            <div class="col-md-3 mb-3">
                                <label>Description</label>
                                <textarea name="description"
                                    class="form-control">{{ old('description', $location->description) }}</textarea>
                            </div>

                            <!-- Phone -->
                            <div class="col-md-3 mb-3">
                                <label>Phone</label>
                                <input type="text" name="phone" class="form-control"
                                    value="{{ old('phone', $location->phone) }}">
                            </div>

                            <!-- Email -->
                            <div class="col-md-3 mb-3">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control"
                                    value="{{ old('email', $location->email) }}">
                            </div>

                            <!-- Address -->
                            <div class="col-md-6 mb-3">
                                <label>Address</label>
                                <textarea name="address" class="form-control"
                                    rows="3">{{ old('address', $location->address) }}</textarea>
                            </div>

                            <!-- City -->
                            <div class="col-md-3 mb-3">
                                <label>City</label>
                                <input type="text" name="city" class="form-control"
                                    value="{{ old('city', $location->city) }}">
                            </div>

                            <!-- State -->
                            <div class="col-md-3 mb-3">
                                <label>State</label>
                                <input type="text" name="state" class="form-control"
                                    value="{{ old('state', $location->state) }}">
                            </div>

                            <!-- Pincode -->
                            <div class="col-md-3 mb-3">
                                <label>Pincode</label>
                                <input type="text" name="pincode" class="form-control"
                                    value="{{ old('pincode', $location->pincode) }}" maxlength="6"
                                    pattern="[1-9][0-9]{5}" required>
                            </div>

                            <!-- Latitude -->
                            <div class="col-md-3 mb-3">
                                <label>Latitude</label><small class="text-muted"> (Range: -90 to 90)</small>
                                <input type="text" name="latitude" class="form-control"
                                    value="{{ old('latitude', $location->latitude) }}">
                            </div>

                            <!-- Longitude -->
                            <div class="col-md-3 mb-3">
                                <label>Longitude</label><small class="text-muted"> (Range: -180 to 180)</small>
                                <input type="text" name="longitude" class="form-control"
                                    value="{{ old('longitude', $location->longitude) }}">
                            </div>

                            <!-- Status -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Is Active?</label>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" {{
                                        old('is_active', $location->is_active) ? 'checked' : '' }}>
                                </div>
                            </div>

                        </div>

                        <!-- 🔹 BUTTONS -->
                        <div class="mt-4">
                            <button type="submit" class="btn btn-success btn-lg px-5">
                                <i class="la la-save"></i> Update Location
                            </button>

                            <a href="{{ backpack_url('location') }}" class="btn btn-secondary btn-lg">
                                Cancel
                            </a>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>
</div>
@endsection