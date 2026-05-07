@extends(backpack_view('blank'))

@section('title', 'Edit Branch Assignment')

@section('content')
<div class="container-fluid">
    <div class="card p-4">
        <h3>Edit Branch Assignment</h3>

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

        <form method="POST" action="{{ backpack_url('employee-branch-assignment/' . $assignment->id) }}">
            @csrf
            @method('PUT')

            <div class="row">

                {{-- EMPLOYEE (READ ONLY) --}}
                <div class="col-md-4 mb-3">
                    <label>Employee</label>
                    <input type="text" class="form-control" value="{{ $assignment->employee_code }} -
                        {{ $assignment->employee && $assignment->employee->person
                            ? trim($assignment->employee->person->first_name.' '.$assignment->employee->person->last_name)
                            : 'N/A' }}" readonly>
                </div>

                {{-- BRANCH --}}
                <div class="col-md-4 mb-3">
                    <label>Branch *</label>
                    <select name="branch_code" class="form-control form-select" required>
                        <option value="">Select Branch</option>
                        @foreach($branches as $branch)
                        <option value="{{ $branch->code }}" {{ old('branch_code', $assignment->branch_code) ==
                            $branch->code ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- ASSIGNMENT TYPE --}}
                <div class="col-md-4 mb-3">
                    <label>Assignment Type <span class="text-danger">*</span></label>
                    <select name="assignment_type" class="form-control form-select" required>
                        <option value="primary" {{ old('assignment_type', $assignment->assignment_type) == 'primary' ?
                            'selected' : '' }}>Primary</option>
                        <option value="additional" {{ old('assignment_type', $assignment->assignment_type) ==
                            'additional' ? 'selected' : '' }}>Additional</option>
                        <option value="inherited" {{ old('assignment_type', $assignment->assignment_type) == 'inherited'
                            ? 'selected' : '' }}>Inherited</option>
                    </select>
                </div>

                {{-- FROM DATE --}}
                <div class="col-md-4 mb-3">
                    <label>From Date *</label>
                    <input type="date" name="from_date" class="form-control"
                        value="{{ old('from_date', $assignment->from_date ? $assignment->from_date->format('Y-m-d') : '') }}"
                        required>
                </div>

                {{-- TO DATE --}}
                <div class="col-md-4 mb-3">
                    <label>To Date</label>
                    <input type="date" name="to_date" class="form-control"
                        value="{{ old('to_date', $assignment->to_date ? $assignment->to_date->format('Y-m-d') : '') }}">
                </div>

                {{-- IS CURRENT --}}
                <div class="col-md-4 mb-3">
                    <label class="form-label">Is Current?</label>
                    <div class="form-check form-switch">
                        <input type="hidden" name="is_current" value="0">
                        <input type="checkbox" name="is_current" value="1" class="form-check-input" {{ old('is_current',
                            $assignment->is_current) ? 'checked' : '' }}>
                    </div>
                </div>

            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-success">
                    Update Assignment
                </button>
                <a href="{{ backpack_url('employee-branch-assignment') }}" class="btn btn-secondary">
                    Cancel
                </a>
            </div>

        </form>
    </div>
</div>
@endsection