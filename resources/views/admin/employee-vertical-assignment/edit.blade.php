@extends(backpack_view('blank'))

@section('title', 'Edit Vertical Assignment')

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
                    <h2 class="mb-0">Edit Vertical Assignment</h2>
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

                    <form method="POST" action="{{ backpack_url('employee-vertical-assignment/' . $assignment->id) }}">
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
                                            {{ $assignment->created_at?->format('d-m-Y H:i') ?? '—' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Employee (Read Only) -->
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
                            </div>

                            <!-- Vertical -->
                            <div class="col-md-4 mb-3">
                                <label>Vertical <span class="text-danger">*</span></label>
                                <select name="vertical_code" class="form-control form-select" required>
                                    <option value="">-- Select Vertical --</option>
                                    @foreach($verticals as $vertical)
                                    <option value="{{ $vertical->code }}" {{ old('vertical_code', $assignment->
                                        vertical_code) == $vertical->code ? 'selected' : '' }}>
                                        {{ $vertical->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Is Current -->
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Is Current Assignment?</label>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="is_current" value="0">
                                    <input type="checkbox" name="is_current" value="1" class="form-check-input" {{
                                        old('is_current', $assignment->is_current) ? 'checked' : '' }}>
                                </div>
                            </div>

                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-success btn-lg px-5">
                                <i class="la la-save"></i> Update Assignment
                            </button>
                            <a href="{{ backpack_url('employee-vertical-assignment') }}"
                                class="btn btn-secondary btn-lg">Cancel</a>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection