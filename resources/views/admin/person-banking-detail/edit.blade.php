@extends(backpack_view('blank'))

@section('title', 'Edit Banking Detail')

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
                    <h2 class="mb-0">Edit Banking Detail</h2>
                </div>
                <div class="card-body">

                    <form method="POST" action="{{ backpack_url('person-banking-detail/' . $banking->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <!-- Read Only -->
                            <div class="col-md-12 mb-4">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Banking ID</label>
                                        <div class="readonly-value">{{ $banking->id }}</div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Created At</label>
                                        <div class="readonly-value">
                                            {{ $banking->created_at?->format('d-m-Y H:i') ?? '—' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Person</label>
                                <select name="person_code" class="form-control form-select" required>
                                    @foreach($persons as $p)
                                    <option value="{{ $p->person_code }}" {{ old('person_code', $banking->person_code)
                                        == $p->person_code
                                        ?
                                        'selected' : '' }}>
                                        {{ $p->first_name }} {{ $p->last_name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Bank Name <span class="text-danger">*</span></label>
                                <input type="text" name="bank_name" class="form-control"
                                    value="{{ old('bank_name', $banking->bank_name) }}" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Account Holder Name <span class="text-danger">*</span></label>
                                <input type="text" name="account_holder_name" class="form-control"
                                    value="{{ old('account_holder_name', $banking->account_holder_name) }}" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Account Number <span class="text-danger">*</span></label>
                                <input type="text" name="account_number" class="form-control"
                                    value="{{ old('account_number', $banking->account_number) }}" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>IFSC Code <span class="text-danger">*</span></label>
                                <input type="text" name="ifsc_code" class="form-control"
                                    value="{{ old('ifsc_code', $banking->ifsc_code) }}" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Account Type <span class="text-danger">*</span></label>
                                <select name="account_type" class="form-control form-select" required>
                                    <option value="Primary" {{ old('account_type', $banking->account_type) == 'Primary'
                                        ? 'selected' : '' }}>Primary</option>
                                    <option value="Secondary" {{ old('account_type', $banking->account_type) ==
                                        'Secondary' ? 'selected' : '' }}>Secondary</option>
                                    <option value="Joint" {{ old('account_type', $banking->account_type) == 'Joint' ?
                                        'selected' : '' }}>Joint</option>
                                    <option value="Trust" {{ old('account_type', $banking->account_type) == 'Trust' ?
                                        'selected' : '' }}>Trust</option>
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Branch Name</label>
                                <input type="text" name="branch_name" class="form-control"
                                    value="{{ old('branch_name', $banking->branch_name) }}">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>MICR Code</label>
                                <input type="text" name="micr_code" class="form-control"
                                    value="{{ old('micr_code', $banking->micr_code) }}">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Account Nature</label>
                                <select name="account_nature" class="form-control form-select">
                                    <option value="Savings" {{ old('account_nature', $banking->account_nature) ==
                                        'Savings' ? 'selected' : '' }}>Savings</option>
                                    <option value="Current" {{ old('account_nature', $banking->account_nature) ==
                                        'Current' ? 'selected' : '' }}>Current</option>
                                    <option value="Salary" {{ old('account_nature', $banking->account_nature) ==
                                        'Salary' ? 'selected' : '' }}>Salary</option>
                                    <option value="NRO" {{ old('account_nature', $banking->account_nature) == 'NRO' ?
                                        'selected' : '' }}>NRO</option>
                                    <option value="NRE" {{ old('account_nature', $banking->account_nature) == 'NRE' ?
                                        'selected' : '' }}>NRE</option>
                                </select>
                            </div>

                            <div class="col-md-1 mb-3">
                                <label class="form-label">Is Verified?</label>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="is_verified" value="0">
                                    <input type="checkbox" name="is_verified" value="1" class="form-check-input" {{
                                        old('is_verified', $banking->is_verified) ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-success btn-lg px-5">
                                <i class="la la-save"></i> Update Banking Detail
                            </button>
                            <a href="{{ backpack_url('person-banking-detail') }}"
                                class="btn btn-secondary btn-lg">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection