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
                <div class="card-header">
                    <h2 class="mb-0">Add New Person</h2>
                </div>
                <div class="card-body">

                    <form method="POST" action="{{ backpack_url('person') }}">
                        @csrf

                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label>Entity Type <span class="text-danger">*</span></label>
                                <select name="entity_type" class="form-control form-select" required>
                                    <option value="individual">Individual</option>
                                    <option value="legal_entity">Legal Entity (Firm/Company)</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Salutation</label>
                                <select name="salutation" class="form-control form-select">
                                    <option value="">Select</option>
                                    <option value="Mr">Mr</option>
                                    <option value="Mrs">Mrs</option>
                                    <option value="Ms">Ms</option>
                                    <option value="Dr">Dr</option>
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>First Name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Middle Name</label>
                                <input type="text" name="middle_name" class="form-control">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Last Name</label>
                                <input type="text" name="last_name" class="form-control">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Display Name</label>
                                <input type="text" name="display_name" class="form-control">
                            </div>



                            <div class="col-md-3 mb-3">
                                <label>Gender</label>
                                <select name="gender" class="form-control form-select">
                                    <option value="">Select</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                    <option value="prefer_not_to_say">Prefer not to say</option>
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Date of Birth</label>
                                <input type="date" name="dob" class="form-control">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Occupation</label>
                                <input type="text" name="occupation" class="form-control">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>PAN No</label>
                                <input type="text" name="pan_no" class="form-control" maxlength="10">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Aadhaar No</label>
                                <input type="text" name="aadhaar_no" class="form-control" maxlength="12">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>GSTIN</label>
                                <input type="text" name="gst_no" class="form-control" maxlength="15">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>TAN No</label>
                                <input type="text" name="tan_no" class="form-control" maxlength="20">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Marital Status</label>
                                <select name="marital_status" class="form-control form-select">
                                    <option value="">Select</option>
                                    <option value="single">Single</option>
                                    <option value="married">Married</option>
                                    <option value="divorced">Divorced</option>
                                    <option value="widowed">Widowed</option>
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Spouse Name</label>
                                <input type="text" name="spouse_name" class="form-control">
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