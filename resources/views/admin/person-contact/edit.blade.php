@extends(backpack_view('blank'))

@section('title', 'Edit Person Contact')

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
                    <h2 class="mb-0">Edit Person Contact</h2>
                </div>
                <div class="card-body">

                    <form method="POST" action="{{ backpack_url('person-contact/' . $contact->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <!-- Read Only -->
                            <div class="col-md-12 mb-4">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Contact ID</label>
                                        <div class="readonly-value">{{ $contact->id }}</div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Created At</label>
                                        <div class="readonly-value">
                                            {{ $contact->created_at?->format('d-m-Y H:i') ?? '—' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Person</label>
                                <select name="person_id" class="form-control" required>
                                    @foreach($persons as $p)
                                    <option value="{{ $p->id }}" {{ old('person_id', $contact->person_id) == $p->id ?
                                        'selected' : '' }}>
                                        {{ $p->first_name }} {{ $p->last_name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Contact Type <span class="text-danger">*</span></label>
                                <select name="type" id="type" class="form-control form-select" required>
                                    <option value="">Select Type</option>
                                    <option value="mobile" {{ old('type', $contact->type) == 'mobile' ? 'selected' : ''
                                        }}>Mobile</option>
                                    <option value="email" {{ old('type', $contact->type) == 'email' ? 'selected' : ''
                                        }}>Email</option>
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control"
                                    value="{{ old('name', $contact->name) }}" required>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Mobile <span id="mobile_star" class="text-danger">*</span></label>
                                <input type="text" name="mobile" id="mobile" class="form-control"
                                    value="{{ old('mobile', $contact->mobile) }}" maxlength="10" pattern="[0-9]{10}">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Email <span id="email_star" class="text-danger">*</span></label>
                                <input type="email" name="email" id="email" class="form-control"
                                    value="{{ old('email', $contact->email) }}">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Relationship</label>
                                <input type="text" name="relationship" class="form-control"
                                    value="{{ old('relationship', $contact->relationship) }}">
                            </div>

                            <div class="col-md-11 mb-3">
                                <label>Notes</label>
                                <textarea name="notes" class="form-control"
                                    rows="3">{{ old('notes', $contact->notes) }}</textarea>
                            </div>

                            <div class="col-md-1 mb-3">
                                <label class="form-label">Is Primary?</label>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="is_primary" value="0">
                                    <input type="checkbox" name="is_primary" value="1" class="form-check-input" {{
                                        old('is_primary', $contact->is_primary) ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-success btn-lg px-5">
                                <i class="la la-save"></i> Update Contact
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
@push('after_scripts')
<script>
    function toggleRequiredFields() {
        const type = document.getElementById('type').value;
        const mobileInput = document.getElementById('mobile');
        const emailInput = document.getElementById('email');
        const mobileStar = document.getElementById('mobile_star');
        const emailStar = document.getElementById('email_star');

        if (type === 'mobile') {
            mobileInput.required = true;
            emailInput.required = false;
            mobileStar.style.display = 'inline';
            emailStar.style.display = 'none';
        } else if (type === 'email') {
            mobileInput.required = false;
            emailInput.required = true;
            mobileStar.style.display = 'none';
            emailStar.style.display = 'inline';
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const typeSelect = document.getElementById('type');
        typeSelect.addEventListener('change', toggleRequiredFields);
        toggleRequiredFields();
    });
</script>
@endpush