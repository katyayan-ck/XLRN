@extends(backpack_view('blank'))

@section('title', 'Edit Color - ' . $color->name)

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
                    <h2 class="mb-0">Edit Color Information</h2>
                </div>
                <div class="card-body">

                    <form method="POST" action="{{ backpack_url('color/' . $color->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">

                            <!-- Read Only Section -->
                            <div class="col-md-12 mb-4">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Color ID</label>
                                        <div class="readonly-value">{{ $color->id }}</div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Created At</label>
                                        <div class="readonly-value">
                                            {{ $color->created_at ? $color->created_at->format('d-m-Y H:i') : '—' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Editable Fields -->
                            <div class="col-md-4 mb-3">
                                <label>Brand <span class="text-danger">*</span></label>
                                <select name="brand_id" class="form-control form-select" required>
                                    <option value="">Select Brand</option>
                                    @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}" {{ old('brand_id', $color->brand_id) == $brand->id
                                        ? 'selected' : '' }}>
                                        {{ $brand->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Color Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control"
                                    value="{{ old('name', $color->name) }}" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Color Code <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control text-uppercase"
                                    value="{{ old('code', $color->code) }}" style="text-transform: uppercase;" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Hex Code <span class="text-danger">*</span></label>
                                <input type="text" name="hex_code" class="form-control"
                                    value="{{ old('hex_code', $color->hex_code) }}" placeholder="#FF0000" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Is Active?</label>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" {{
                                        old('is_active', $color->is_active) ? 'checked' : '' }}>
                                </div>
                            </div>

                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-success btn-lg px-5">
                                <i class="la la-save"></i> Update Color
                            </button>
                            <a href="{{ backpack_url('color') }}" class="btn btn-secondary btn-lg">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection