@extends(backpack_view('blank'))

@section('title', 'Add New Permission')

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
                    <h2 class="mb-0">Add New Permission</h2>
                </div>
                <div class="card-body">

                    <form method="POST" action="{{ backpack_url('permission') }}">
                        @csrf

                        <div class="row">

                            <div class="col-md-6 mb-3">
                                <label>Permission Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required
                                    maxlength="255">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Guard Name <span class="text-danger">*</span></label>
                                <select name="guard_name" class="form-control form-select" required>
                                    <option value="">Select Guard</option>
                                    <option value="web" {{ old('guard_name')==='web' ? 'selected' : '' }}>Web</option>
                                    <option value="api" {{ old('guard_name')==='api' ? 'selected' : '' }}>API</option>
                                </select>
                            </div>

                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-success btn-lg px-5">
                                <i class="la la-save"></i> Create Permission
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