@extends(backpack_view('blank'))

@section('title', 'Add Department Assignment')

@section('content')
<div class="container-fluid">
    <div class="card p-4">
        <h3>Add Department Assignment</h3>

        {{-- ERROR DISPLAY --}}
        @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ backpack_url('employee-department-assignment') }}">
            @csrf

            <div class="row">

                {{-- EMPLOYEE --}}
                <div class="col-md-4 mb-3">
                    <label>Employee <span class="text-danger">*</span></label>
                    <select name="employee_code" class="form-control form-select" required>
                        <option value="">Select Employee</option>
                        @foreach($employees as $emp)
                        <option value="{{ $emp->code }}" {{ old('employee_code')==$emp->code ? 'selected' : '' }}>
                            {{ $emp->code }} -
                            {{ $emp->person ? trim($emp->person->first_name . ' ' . $emp->person->last_name) : 'No Name'
                            }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- DEPARTMENT --}}
                <div class="col-md-4 mb-3">
                    <label>Department <span class="text-danger">*</span></label>
                    <select name="dept_code" class="form-control form-select" required>
                        <option value="">Select Department</option>
                        @foreach($departments as $dept)
                        <option value="{{ $dept->code }}" {{ old('dept_code')==$dept->code ? 'selected' : '' }}>
                            {{ $dept->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- DIVISION (FIXED) --}}
                <div class="col-md-4 mb-3">
                    <label>Division</label>
                    <select name="division_code" class="form-control form-select">
                        <option value="">Select Division</option>
                        @foreach($divisions as $div)
                        <option value="{{ $div->code }}" {{ old('division_code')==$div->code ? 'selected' : '' }}>
                            {{ $div->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- ASSIGNMENT TYPE --}}
                <div class="col-md-4 mb-3">
                    <label>Assignment Type <span class="text-danger">*</span></label>
                    <select name="assignment_type" class="form-control form-select" required>
                        <option value="primary" {{ old('assignment_type', 'primary' )=='primary' ? 'selected' : '' }}>
                            Primary</option>
                        <option value="secondary" {{ old('assignment_type')=='secondary' ? 'selected' : '' }}>Secondary
                        </option>
                    </select>
                </div>

                {{-- FROM DATE --}}
                <div class="col-md-4 mb-3">
                    <label>From Date *</label>
                    <input type="date" name="from_date" class="form-control" value="{{ old('from_date') }}" required>
                </div>

                {{-- TO DATE --}}
                <div class="col-md-4 mb-3">
                    <label>To Date</label>
                    <input type="date" name="to_date" class="form-control" value="{{ old('to_date') }}">
                </div>

                {{-- IS CURRENT --}}
                <div class="col-md-4 mb-3">
                    <label class="form-label">Is Current?</label>
                    <div class="form-check form-switch">
                        <input type="hidden" name="is_current" value="0">
                        <input type="checkbox" name="is_current" value="1" class="form-check-input" {{ old('is_current',
                            true) ? 'checked' : '' }}>
                    </div>
                </div>

            </div>

            <div class="mt-4">
                <button class="btn btn-success">Create Assignment</button>
                <a href="{{ backpack_url('employee-department-assignment') }}" class="btn btn-secondary">Cancel</a>
            </div>

        </form>
    </div>
</div>
@endsection