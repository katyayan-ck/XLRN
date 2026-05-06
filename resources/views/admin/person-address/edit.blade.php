@extends(backpack_view('blank'))

@section('title', 'Edit Person Address')

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
        font-weight: 500;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header text-black">
                    <h2 class="mb-0">Edit Person Address</h2>
                </div>
                <div class="card-body">

                    <form method="POST" action="{{ backpack_url('person-address/' . $address->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <!-- Read Only Fields -->
                            <div class="col-md-12 mb-4">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Address ID</label>
                                        <div class="readonly-value">{{ $address->id }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Person Code</label>
                                        <div class="readonly-value">{{ $address->person_code }}</div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Created At</label>
                                        <div class="readonly-value">
                                            {{ $address->created_at?->format('d-m-Y H:i') ?? '—' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Editable Fields -->
                            <div class="col-md-3 mb-3">
                                <label>Person</label>
                                <select name="person_code" class="form-control form-select" required>
                                    @foreach($persons as $p)
                                    <option value="{{ $p->person_code }}" {{ old('person_code', $address->person_code)
                                        == $p->person_code ? 'selected' : '' }}>
                                        {{ $p->display_name ?? $p->first_name . ' ' . $p->last_name }}
                                        ({{ $p->person_code }})
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Address Type <span class="text-danger">*</span></label>
                                <select name="address_type" class="form-control form-select" required>
                                    <option value="Primary" {{ old('address_type', $address->address_type) == 'Primary'
                                        ? 'selected' : '' }}>Primary</option>
                                    <option value="Office" {{ old('address_type', $address->address_type) == 'Office' ?
                                        'selected' : '' }}>Office</option>
                                    <option value="Home" {{ old('address_type', $address->address_type) == 'Home' ?
                                        'selected' : '' }}>Home</option>
                                    <option value="Alternate" {{ old('address_type', $address->address_type) ==
                                        'Alternate' ? 'selected' : '' }}>Alternate</option>
                                    <option value="Permanent" {{ old('address_type', $address->address_type) ==
                                        'Permanent' ? 'selected' : '' }}>Permanent</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Address Line 1 <span class="text-danger">*</span></label>
                                <input type="text" name="address_line_1" class="form-control"
                                    value="{{ old('address_line_1', $address->address_line_1) }}" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Address Line 2</label>
                                <input type="text" name="address_line_2" class="form-control"
                                    value="{{ old('address_line_2', $address->address_line_2) }}">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Landmark</label>
                                <input type="text" name="landmark" class="form-control"
                                    value="{{ old('landmark', $address->landmark) }}">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>City <span class="text-danger">*</span></label>
                                <input type="text" name="city" class="form-control"
                                    value="{{ old('city', $address->city) }}" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Taluka</label>
                                <input type="text" name="taluka" class="form-control"
                                    value="{{ old('taluka', $address->taluka) }}">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>District</label>
                                <input type="text" name="district" class="form-control"
                                    value="{{ old('district', $address->district) }}">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Latitude</label>
                                <input type="text" name="latitude" class="form-control"
                                    value="{{ old('latitude', $address->latitude) }}">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Longitude</label>
                                <input type="text" name="longitude" class="form-control"
                                    value="{{ old('longitude', $address->longitude) }}">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>State <span class="text-danger">*</span></label>
                                <input type="text" name="state" class="form-control"
                                    value="{{ old('state', $address->state) }}" required>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Pincode</label>
                                <input type="text" name="pincode" class="form-control"
                                    value="{{ old('pincode', $address->pincode) }}" maxlength="6">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Country</label>
                                <select name="country" class="form-control form-select" required>
                                    <option value="">Select Country</option>
                                    <option value="India" {{ old('country', $address->country ?? '') == 'India' ?
                                        'selected' : '' }}>India</option>
                                    <option value="USA" {{ old('country', $address->country ?? '') == 'USA' ? 'selected'
                                        : '' }}>USA</option>
                                    <option value="UK" {{ old('country', $address->country ?? '') == 'UK' ? 'selected' :
                                        '' }}>UK</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-success btn-lg px-5">
                                <i class="la la-save"></i> Update Address
                            </button>
                            <a href="{{ backpack_url('person-address') }}" class="btn btn-secondary btn-lg">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
