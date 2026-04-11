@extends(backpack_view('blank'))

@section('title', 'Edit Process')

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
                    <h2 class="mb-0">Edit Process</h2>
                </div>
                <div class="card-body">

                    <form method="POST" action="{{ backpack_url('process/' . $process->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <!-- Read Only Info -->
                            <div class="col-md-12 mb-4">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Process ID</label>
                                        <div class="readonly-value">{{ $process->id }}</div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Created At</label>
                                        <div class="readonly-value">
                                            {{ $process->created_at?->format('d-m-Y H:i') ?? '—' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Code <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control"
                                    value="{{ old('code', $process->code) }}" required maxlength="255">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Process Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control"
                                    value="{{ old('name', $process->name) }}" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Module <span class="text-danger">*</span></label>
                                <select name="module_id" class="form-control form-select" required>
                                    <option value="">Select Module</option>
                                    @foreach($modules as $module)
                                    <option value="{{ $module->id }}" {{ old('module_id', $process->module_id) ==
                                        $module->id ? 'selected' : '' }}>
                                        {{ $module->name }} ({{ $module->code ?? '' }})
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Is Active?</label>
                                <div class="form-check form-switch mt-2">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" {{
                                        old('is_active', $process->is_active) ? 'checked' : '' }}>
                                </div>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label>Description</label>
                                <textarea name="description" class="form-control"
                                    rows="5">{{ old('description', $process->description) }}</textarea>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-success btn-lg px-5">
                                <i class="la la-save"></i> Update Process
                            </button>
                            <a href="{{ backpack_url('process') }}" class="btn btn-secondary btn-lg">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection