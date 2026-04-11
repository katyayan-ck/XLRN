@extends(backpack_view('blank'))

@section('title', 'Add New Person Contact')

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
                    <h2 class="mb-0">Add New Person Contact</h2>
                </div>
                <div class="card-body">

                    <form method="POST" action="{{ backpack_url('person-contact') }}">
                        @csrf

                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label>Person <span class="text-danger">*</span></label>
                                <select name="person_id" class="form-control form-select" required>
                                    <option value="">Select Person</option>
                                    @foreach($persons as $p)
                                    <option value="{{ $p->id }}" {{ old('person_id')==$p->id ? 'selected' : '' }}>
                                        {{ $p->first_name }} {{ $p->last_name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Contact Type <span class="text-danger">*</span></label>
                                <select name="type" class="form-control form-select" required>
                                    <option value="">Select Type</option>
                                    <option value="phone" {{ old('type')=='phone' ? 'selected' : '' }}>Phone</option>
                                    <option value="email" {{ old('type')=='email' ? 'selected' : '' }}>Email</option>
                                    <option value="mobile" {{ old('type')=='mobile' ? 'selected' : '' }}>Mobile</option>
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Mobile</label>
                                <input type="text" name="mobile" class="form-control" value="{{ old('mobile') }}">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Relationship</label>
                                <input type="text" name="relationship" class="form-control"
                                    value="{{ old('relationship') }}">
                            </div>

                            <div class="col-md-12 mb-3">
                                <label>Notes</label>
                                <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Is Primary?</label>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="is_primary" value="0">
                                    <input type="checkbox" name="is_primary" value="1" class="form-check-input" {{
                                        old('is_primary') ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-success btn-lg px-5">
                                <i class="la la-save"></i> Create Contact
                            </button>
                            <a href="{{ backpack_url('person-contact') }}" class="btn btn-secondary btn-lg">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection