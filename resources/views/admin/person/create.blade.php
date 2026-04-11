@extends(backpack_view('blank'))

@section('title', 'Add New Person')

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
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header text-black">
                    <h2 class="mb-0">Add New Person</h2>
                </div>
                <div class="card-body">

                    <form method="POST" action="{{ backpack_url('person') }}">
                        @csrf

                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label>Code <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control" value="{{ old('code') }}" required>
                            </div>

                            <div class="col-md-2 mb-3">
                                <label>Salutation</label>
                                <select name="salutation" class="form-control form-select">
                                    <option value="">Select</option>
                                    <option value="Mr" {{ old('salutation')=='Mr' ? 'selected' : '' }}>Mr</option>
                                    <option value="Mrs" {{ old('salutation')=='Mrs' ? 'selected' : '' }}>Mrs</option>
                                    <option value="Ms" {{ old('salutation')=='Ms' ? 'selected' : '' }}>Ms</option>
                                    <option value="Dr" {{ old('salutation')=='Dr' ? 'selected' : '' }}>Dr</option>
                                </select>
                            </div>

                            <div class="col-md-7 mb-3">
                                <label>Display Name</label>
                                <input type="text" name="display_name" class="form-control"
                                    value="{{ old('display_name') }}">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>First Name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" class="form-control"
                                    value="{{ old('first_name') }}" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Middle Name</label>
                                <input type="text" name="middle_name" class="form-control"
                                    value="{{ old('middle_name') }}">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Last Name <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" class="form-control" value="{{ old('last_name') }}"
                                    required>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Gender</label>
                                <select name="gender" class="form-control form-select">
                                    <option value="">Select</option>
                                    <option value="male" {{ old('gender')=='male' ? 'selected' : '' }}>Male</option>
                                    <option value="female" {{ old('gender')=='female' ? 'selected' : '' }}>Female
                                    </option>
                                    <option value="other" {{ old('gender')=='other' ? 'selected' : '' }}>Other</option>
                                    <option value="prefer_not_to_say" {{ old('gender')=='prefer_not_to_say' ? 'selected'
                                        : '' }}>Prefer not to say</option>
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Date of Birth</label>
                                <input type="date" name="dob" class="form-control" value="{{ old('dob') }}">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Occupation</label>
                                <input type="text" name="occupation" class="form-control"
                                    value="{{ old('occupation') }}">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Email Primary</label>
                                <input type="email" name="email_primary" class="form-control"
                                    value="{{ old('email_primary') }}">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Mobile Primary</label>
                                <input type="text" name="mobile_primary" class="form-control"
                                    value="{{ old('mobile_primary') }}">
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-success btn-lg px-5">
                                <i class="la la-save"></i> Create Person
                            </button>
                            <a href="{{ backpack_url('person') }}" class="btn btn-secondary btn-lg">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection