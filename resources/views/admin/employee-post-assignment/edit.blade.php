@extends(backpack_view('blank'))

@section('title', 'Edit Post Assignment')

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
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header text-black">
                    <h2 class="mb-0">Edit Post Assignment</h2>
                </div>
                <div class="card-body">

                    <form method="POST" action="{{ backpack_url('employee-post-assignment/' . $assignment->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <!-- Read Only -->
                            <div class="col-md-12 mb-4">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Assignment ID</label>
                                        <div class="readonly-value">{{ $assignment->id }}</div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Created At</label>
                                        <div class="readonly-value">
                                            {{ $assignment->created_at?->format('d-m-Y H:i') ?? '—' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Employee</label>
                                <select name="employee_id" class="form-control form-select" required>
                                    @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}" {{ old('employee_id', $assignment->employee_id) ==
                                        $emp->id ? 'selected' : '' }}>
                                        {{ $emp->code }} - {{ $emp->person ? trim($emp->person->first_name . ' ' .
                                        $emp->person->last_name) : 'No Person' }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Post <span class="text-danger">*</span></label>
                                <select name="post_id" class="form-control form-select" required>
                                    <option value="">Select Post</option>
                                    @foreach($posts as $p)
                                    <option value="{{ $p->id }}" {{ old('post_id', isset($assignment) ? $assignment->
                                        post_id : '') == $p->id ? 'selected' : '' }}>
                                        {{ $p->name ?? $p->display_name ?? $p->title ?? $p->code ?? 'Post #' . $p->id }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- <div class="col-md-4 mb-3">
                                <label>From Date <span class="text-danger">*</span></label>
                                <input type="date" name="from_date" class="form-control"
                                    value="{{ old('from_date', $assignment->from_date?->format('Y-m-d')) }}" required>
                            </div> --}}

                            <div class="col-md-4 mb-3">
                                <label>From Date <span class="text-danger">*</span></label>
                                <input type="date" name="from_date" class="form-control"
                                    value="{{ old('from_date', $assignment->from_date?->format('Y-m-d')) }}" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>To Date</label>
                                <input type="date" name="to_date" class="form-control"
                                    value="{{ old('to_date', $assignment->to_date?->format('Y-m-d')) }}">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Assignment Order</label>
                                <input type="number" name="assignment_order" class="form-control"
                                    value="{{ old('assignment_order', $assignment->assignment_order) }}" min="1">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Is Current?</label>
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
                            <a href="{{ backpack_url('employee-post-assignment') }}"
                                class="btn btn-secondary btn-lg">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection