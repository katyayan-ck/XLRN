@extends(backpack_view('blank'))

@section('title', 'Edit Person - ' . ($person->display_name ?? $person->full_name))

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
                    <h2 class="mb-0">Edit Person Information</h2>
                </div>
                <div class="card-body">

                    @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <form method="POST" action="{{ backpack_url('person/' . $person->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">

                            <!-- Read Only Fields -->
                            <div class="col-md-12 mb-4">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Person ID</label>
                                        <div class="readonly-value">{{ $person->id }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Person Code (Immutable)</label>
                                        <div class="readonly-value">{{ $person->person_code }}
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Entity Type</label>
                                        <div class="readonly-value">
                                            {{ ucwords(str_replace('_', ' ', $person->entity_type)) }}
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-bold">Created At</label>
                                        <div class="readonly-value">
                                            {{ $person->created_at?->format('d-m-Y H:i') ?? '—' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Editable Fields -->
                            <div class="col-md-3 mb-3">
                                <label>Salutation</label>
                                <select name="salutation" class="form-control form-select">
                                    <option value="">Select</option>
                                    <option value="Mr" {{ old('salutation', $person->salutation) == 'Mr' ? 'selected' :
                                        '' }}>Mr</option>
                                    <option value="Mrs" {{ old('salutation', $person->salutation) == 'Mrs' ? 'selected'
                                        : '' }}>Mrs</option>
                                    <option value="Ms" {{ old('salutation', $person->salutation) == 'Ms' ? 'selected' :
                                        '' }}>Ms</option>
                                    <option value="Dr" {{ old('salutation', $person->salutation) == 'Dr' ? 'selected' :
                                        '' }}>Dr</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>First Name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" class="form-control"
                                    value="{{ old('first_name', $person->first_name) }}" required>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Middle Name</label>
                                <input type="text" name="middle_name" class="form-control"
                                    value="{{ old('middle_name', $person->middle_name) }}">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Last Name</label>
                                <input type="text" name="last_name" class="form-control"
                                    value="{{ old('last_name', $person->last_name) }}">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Display Name</label>
                                <input type="text" name="display_name" class="form-control"
                                    value="{{ old('display_name', $person->display_name) }}">
                            </div>



                            <div class="col-md-3 mb-3">
                                <label>Gender</label>
                                <select name="gender" class="form-control form-select">
                                    <option value="male" {{ old('gender', $person->gender) == 'male' ? 'selected' : ''
                                        }}>Male</option>
                                    <option value="female" {{ old('gender', $person->gender) == 'female' ? 'selected' :
                                        '' }}>Female</option>
                                    <option value="other" {{ old('gender', $person->gender) == 'other' ? 'selected' : ''
                                        }}>Other</option>
                                    <option value="prefer_not_to_say" {{ old('gender', $person->gender) ==
                                        'prefer_not_to_say' ? 'selected' : '' }}>Prefer not to say</option>
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Date of Birth</label>
                                <input type="date" name="dob" class="form-control"
                                    value="{{ old('dob', $person->dob?->format('Y-m-d')) }}">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Occupation</label>
                                <input type="text" name="occupation" class="form-control"
                                    value="{{ old('occupation', $person->occupation) }}">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>PAN No</label>
                                <input type="text" name="pan_no" class="form-control" maxlength="10"
                                    value="{{ old('pan_no', $person->pan_no) }}">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Aadhaar No</label>
                                <input type="text" name="aadhaar_no" class="form-control" maxlength="12"
                                    value="{{ old('aadhaar_no', $person->aadhaar_no) }}">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>GSTIN</label>
                                <input type="text" name="gst_no" class="form-control" maxlength="15"
                                    value="{{ old('gst_no', $person->gst_no) }}">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>TAN No</label>
                                <input type="text" name="tan_no" class="form-control" maxlength="20"
                                    value="{{ old('tan_no', $person->tan_no) }}">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Marital Status</label>
                                <select name="marital_status" class="form-control form-select">
                                    <option value="single" {{ old('marital_status', $person->marital_status) == 'single'
                                        ? 'selected' : '' }}>Single</option>
                                    <option value="married" {{ old('marital_status', $person->marital_status) ==
                                        'married' ? 'selected' : '' }}>Married</option>
                                    <option value="divorced" {{ old('marital_status', $person->marital_status) ==
                                        'divorced' ? 'selected' : '' }}>Divorced</option>
                                    <option value="widowed" {{ old('marital_status', $person->marital_status) ==
                                        'widowed' ? 'selected' : '' }}>Widowed</option>
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Spouse Name</label>
                                <input type="text" name="spouse_name" class="form-control"
                                    value="{{ old('spouse_name', $person->spouse_name) }}">
                            </div>

                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-success btn-lg px-5">
                                <i class="la la-save"></i> Update Person
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