@extends(backpack_view('blank'))

@section('title', 'Add New Employee')

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
                    <h2 class="mb-0">Add New Employee</h2>
                </div>
                <div class="card-body">

                    <form method="POST" action="{{ backpack_url('employee') }}">
                        @csrf

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label>Employee Code <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control" value="{{ old('code') }}" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Person <span class="text-danger">*</span></label>
                                <select name="person_code" class="form-control form-select" required>
                                    <option value="">Select Person</option>
                                    @foreach($persons as $p)
                                    <option value="{{ $p->person_code }}" {{ old('person_code')==$p->person_code ?
                                        'selected' : '' }}>
                                        {{ $p->first_name }} {{ $p->last_name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Designation</label>
                                <select name="desig_code" class="form-control form-select" required>
                                    <option value="">Select</option>
                                    @foreach($designations as $d)
                                    <option value="{{ $d->code }}">{{ $d->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Primary Branch</label>
                                <select name="primary_branch_code" class="form-control form-select" required>
                                    <option value="">Select</option>
                                    @foreach($branches as $b)
                                    <option value="{{ $b->code }}">{{ $b->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Primary Department</label>
                                <select name="primary_dept_code" class="form-control form-select" required>
                                    <option value="">Select</option>
                                    @foreach($departments as $d)
                                    <option value="{{ $d->code }}">{{ $d->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Division</label>
                                <input type="text" name="primary_div_code" class="form-control">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Location</label>
                                <input type="text" name="primary_loc_code" class="form-control">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Vertical</label>
                                <input type="text" name="vertical_code" class="form-control">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Segment (5 Characters)</label>
                                <input type="text" name="segment_code" class="form-control">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Sub Segment (5 Characters)</label>
                                <input type="text" name="sub_segment_code" class="form-control">
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-success btn-lg px-5">
                                    <i class="la la-save"></i> Create Employee
                                </button>
                                <a href="{{ backpack_url('employee') }}" class="btn btn-secondary btn-lg">Cancel</a>
                            </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection