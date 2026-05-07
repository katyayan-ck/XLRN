@extends(backpack_view('blank'))

@section('title', 'Edit Employee')

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

    .is-invalid {
        border-color: #dc3545;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header text-black">
                    <h2 class="mb-0">Edit Employee Information</h2>
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

                    <form method="POST" action="{{ backpack_url('employee/' . $employee->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">

                            <!-- Employee Code -->
                            <div class="col-md-4 mb-3">
                                <label>Employee Code <span class="text-danger">*</span></label>
                                <input type="text" name="code"
                                    class="form-control {{ $errors->has('code') ? 'is-invalid' : '' }}"
                                    value="{{ old('code', $employee->code) }}" readonly>
                                @error('code')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Person -->
                            <div class="col-md-4 mb-3">
                                <label>Person <span class="text-danger">*</span></label>
                                <select name="person_code"
                                    class="form-control form-select {{ $errors->has('person_code') ? 'is-invalid' : '' }}">
                                    @foreach($persons as $p)
                                    <option value="{{ $p->person_code }}" {{ old('person_code', $employee->person_code)
                                        == $p->person_code ? 'selected' : '' }}>
                                        {{ $p->first_name }} {{ $p->last_name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('person_code')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Designation -->
                            <div class="col-md-4 mb-3">
                                <label>Designation <span class="text-danger">*</span></label>
                                <select name="desig_code"
                                    class="form-control form-select {{ $errors->has('desig_code') ? 'is-invalid' : '' }}">
                                    @foreach($designations as $d)
                                    <option value="{{ $d->code }}" {{ old('desig_code', $employee->desig_code) ==
                                        $d->code ? 'selected' : '' }}>
                                        {{ $d->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('desig_code')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Primary Branch -->
                            <div class="col-md-4 mb-3">
                                <label>Primary Branch <span class="text-danger">*</span></label>
                                <select name="primary_branch_code"
                                    class="form-control form-select {{ $errors->has('primary_branch_code') ? 'is-invalid' : '' }}">
                                    @foreach($branches as $b)
                                    <option value="{{ $b->code }}" {{ old('primary_branch_code', $employee->
                                        primary_branch_code) == $b->code ? 'selected' : '' }}>
                                        {{ $b->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('primary_branch_code')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Primary Department -->
                            <div class="col-md-4 mb-3">
                                <label>Primary Department <span class="text-danger">*</span></label>
                                <select name="primary_dept_code"
                                    class="form-control form-select {{ $errors->has('primary_dept_code') ? 'is-invalid' : '' }}">
                                    @foreach($departments as $d)
                                    <option value="{{ $d->code }}" {{ old('primary_dept_code', $employee->
                                        primary_dept_code) == $d->code ? 'selected' : '' }}>
                                        {{ $d->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('primary_dept_code')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Division -->
                            <div class="col-md-4 mb-3">
                                <label>Division</label>
                                <select name="primary_div_code"
                                    class="form-control form-select {{ $errors->has('primary_div_code') ? 'is-invalid' : '' }}">
                                    <option value="">Select Division</option>
                                    @foreach($divisions as $div)
                                    <option value="{{ $div->code }}" {{ old('primary_div_code', $employee->
                                        primary_div_code) == $div->code ? 'selected' : '' }}>
                                        {{ $div->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('primary_div_code')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Location -->
                            <div class="col-md-4 mb-3">
                                <label>Location (5 Characters)</label>
                                <input type="text" name="primary_loc_code"
                                    class="form-control {{ $errors->has('primary_loc_code') ? 'is-invalid' : '' }}"
                                    value="{{ old('primary_loc_code', $employee->primary_loc_code) }}">
                                @error('primary_loc_code')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Vertical -->
                            <div class="col-md-4 mb-3">
                                <label>Vertical (10 Characters)</label>
                                <input type="text" name="vertical_code"
                                    class="form-control {{ $errors->has('vertical_code') ? 'is-invalid' : '' }}"
                                    value="{{ old('vertical_code', $employee->vertical_code) }}">
                                @error('vertical_code')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Segment -->
                            <div class="col-md-4 mb-3">
                                <label>Segment (5 Characters)</label>
                                <input type="text" name="segment_code"
                                    class="form-control {{ $errors->has('segment_code') ? 'is-invalid' : '' }}"
                                    value="{{ old('segment_code', $employee->segment_code) }}">
                                @error('segment_code')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Sub Segment -->
                            <div class="col-md-4 mb-3">
                                <label>Sub Segment (5 Characters)</label>
                                <input type="text" name="sub_segment_code"
                                    class="form-control {{ $errors->has('sub_segment_code') ? 'is-invalid' : '' }}"
                                    value="{{ old('sub_segment_code', $employee->sub_segment_code) }}">
                                @error('sub_segment_code')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                        </div>

                        <br>
                        <button type="submit" class="btn btn-success">Update Employee</button>
                        <a href="{{ backpack_url('employee') }}" class="btn btn-secondary">Cancel</a>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection