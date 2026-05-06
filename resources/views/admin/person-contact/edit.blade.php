@extends(backpack_view('blank'))

@section('title', 'Edit Person Contact - ' . ($contact->contact_detail ?? 'Contact'))

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
        font-weight: 500;
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
                            <!-- Read Only Fields -->
                            <div class="col-md-12 mb-4">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Contact ID</label>
                                        <div class="readonly-value">{{ $contact->id }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Person Code</label>
                                        <div class="readonly-value">{{ $contact->person_code }}</div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Created At</label>
                                        <div class="readonly-value">
                                            {{ $contact->created_at?->format('d-m-Y H:i') ?? '—' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Editable Fields -->
                            <div class="col-md-4 mb-3">
                                <label>Person</label>
                                <select name="person_code" class="form-control form-select" required>
                                    @foreach($persons as $p)
                                    <option value="{{ $p->person_code }}" {{ old('person_code', $contact->person_code)
                                        == $p->person_code ? 'selected' : '' }}>
                                        {{ $p->display_name ?? $p->first_name . ' ' . $p->last_name }}
                                        ({{ $p->person_code }})
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Data Type <span class="text-danger">*</span></label>
                                <select name="data_type" id="data_type" class="form-control form-select" required>
                                    <option value="Mobile" {{ old('data_type', $contact->data_type) == 'Mobile' ?
                                        'selected' : '' }}>Mobile</option>
                                    <option value="Email" {{ old('data_type', $contact->data_type) == 'Email' ?
                                        'selected' : '' }}>Email</option>
                                    <option value="Landline" {{ old('data_type', $contact->data_type) == 'Landline' ?
                                        'selected' : '' }}>Landline</option>
                                    <option value="Fax" {{ old('data_type', $contact->data_type) == 'Fax' ? 'selected' :
                                        '' }}>Fax</option>
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Contact Type</label>
                                <select name="contact_type" class="form-control form-select">
                                    <option value="">Select</option>
                                    <option value="Primary" {{ old('contact_type', $contact->contact_type) == 'Primary'
                                        ? 'selected' : '' }}>Primary</option>
                                    <option value="Alternate" {{ old('contact_type', $contact->contact_type) ==
                                        'Alternate' ? 'selected' : '' }}>Alternate</option>
                                    <option value="Office" {{ old('contact_type', $contact->contact_type) == 'Office' ?
                                        'selected' : '' }}>Office</option>
                                    <option value="Home" {{ old('contact_type', $contact->contact_type) == 'Home' ?
                                        'selected' : '' }}>Home</option>
                                    <option value="Emergency" {{ old('contact_type', $contact->contact_type) ==
                                        'Emergency' ? 'selected' : '' }}>Emergency</option>
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Contact Detail <span class="text-danger">*</span></label>
                                <input type="text" name="contact_detail" id="contact_detail" pattern="[6-9]{1}[0-9]{9}"
                                    maxlength="10" class="form-control"
                                    value="{{ old('contact_detail', $contact->contact_detail) }}" required>
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
    document.addEventListener('DOMContentLoaded', () => {
        const dataTypeSelect = document.getElementById('data_type');
        const contactDetailInput = document.getElementById('contact_detail');

        dataTypeSelect.addEventListener('change', function() {
            const type = this.value;
            if (type === 'Mobile' || type === 'Landline') {
                contactDetailInput.placeholder = 'Enter phone number';
            } else if (type === 'Email') {
                contactDetailInput.placeholder = 'Enter email address';
            } else {
                contactDetailInput.placeholder = 'Enter contact detail';
            }
        });
    });
</script>
@endpush