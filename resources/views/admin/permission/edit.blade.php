@extends(backpack_view('blank'))

@section('title', 'Edit Permission')

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
                    <h2 class="mb-0">Edit Permission</h2>
                </div>
                <div class="card-body">

                    <form method="POST" action="{{ backpack_url('permission/' . $permission->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-12 mb-4">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Permission ID</label>
                                        <div class="readonly-value">{{ $permission->id }}</div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Created At</label>
                                        <div class="readonly-value">
                                            {{ $permission->created_at?->format('d-m-Y H:i') ?? '—' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Permission Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control"
                                    value="{{ old('name', $permission->name) }}" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Guard Name <span class="text-danger">*</span></label>
                                <select name="guard_name" class="form-control form-select" required>
                                    <option value="web" {{ old('guard_name', $permission->guard_name) === 'web' ?
                                        'selected' : '' }}>Web</option>
                                    <option value="api" {{ old('guard_name', $permission->guard_name) === 'api' ?
                                        'selected' : '' }}>API</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-success btn-lg px-5">
                                <i class="la la-save"></i> Update Permission
                            </button>
                            <a href="{{ backpack_url('permission') }}" class="btn btn-secondary btn-lg">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection