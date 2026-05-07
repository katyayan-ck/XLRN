@extends(backpack_view('blank'))

@section('title', 'Edit Location Assignment')

@push('after_styles')
<style>
    .card {
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
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
            <div class="card">
                <div class="card-header">
                    <h2 class="mb-0">Edit Location Assignment</h2>
                </div>
                <div class="card-body">

                    <form method="POST" action="{{ backpack_url('employee-location-assignment/' . $assignment->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">

                            <!-- Read Only -->
                            <div class="col-md-12 mb-4">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="fw-bold">ID</label>
                                        <div class="readonly-value">{{ $assignment->id }}</div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="fw-bold">Created At</label>
                                        <div class="readonly-value">
                                            {{ $assignment->created_at?->format('d-m-Y H:i') }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Employee Info -->
                            <div class="col-md-4 mb-3">
                                <label>Employee</label>
                                <div class="readonly-value">
                                    @php
                                    $emp = $assignment->employee;
                                    $person = $emp?->person;
                                    $fullName = $person
                                    ? trim($person->first_name . ' ' . ($person->last_name ?? ''))
                                    : 'No Person Data';
                                    $display = $emp ? ($emp->code . ' - ' . $fullName) : 'Unknown Employee';
                                    @endphp
                                    {{ $display }}
                                </div>
                                <input type="hidden" name="employee_id" value="{{ $assignment->employee_id }}">
                            </div>

                            <!-- Location -->
                            <div class="col-md-4 mb-3">
                                <label>Location <span class="text-danger">*</span></label>
                                <select name="location_code" class="form-control form-select" required>
                                    @foreach($locations as $loc)
                                    <option value="{{ $loc->code }}" {{ old('location_code', $assignment->location_code)
                                        == $loc->code ? 'selected' : '' }}>
                                        {{ $loc->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Branch (Optional)</label>
                                <select name="branch_code" class="form-control form-select">
                                    <option value="">— No Branch —</option>
                                    @foreach(App\Models\Admin\Branch::orderBy('name')->get() as $branch)
                                    <option value="{{ $branch->code }}" {{ $branch->code == $assignment->branch_code ?
                                        'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <!-- Assignment Type -->
                            <div class="col-md-4 mb-3">
                                <label>Assignment Type <span class="text-danger">*</span></label>
                                <select name="assignment_type" class="form-control form-select" required>
                                    <option value="explicit" {{ old('assignment_type', $assignment->assignment_type) ==
                                        'explicit' ? 'selected' : '' }}>Explicit</option>
                                    <option value="inherited" {{ old('assignment_type', $assignment->assignment_type) ==
                                        'inherited' ? 'selected' : '' }}>Inherited</option>
                                    <option value="excluded" {{ old('assignment_type', $assignment->assignment_type) ==
                                        'excluded' ? 'selected' : '' }}>Excluded</option>
                                </select>
                            </div>

                            <!-- Is Primary Work -->
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Is Primary Work Location?</label>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="is_primary_work" value="0">
                                    <input type="checkbox" name="is_primary_work" value="1" class="form-check-input" {{
                                        old('is_primary_work', $assignment->is_primary_work) ? 'checked' : '' }}>
                                </div>
                            </div>





                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-success btn-lg px-5">
                                <i class="la la-save"></i> Update Assignment
                            </button>
                            <a href="{{ backpack_url('employee-location-assignment') }}"
                                class="btn btn-secondary btn-lg">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection