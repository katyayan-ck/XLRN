@extends(backpack_view('blank'))

@section('title', 'Edit Variant - ' . $variant->name)

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
                    <h2 class="mb-0">Edit Variant Information</h2>
                </div>
                <div class="card-body">

                    <form method="POST" action="{{ backpack_url('variant/' . $variant->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">

                            <!-- Read Only Section -->
                            <div class="col-md-12 mb-4">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Variant ID</label>
                                        <div class="readonly-value">{{ $variant->id }}</div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Created At</label>
                                        <div class="readonly-value">
                                            {{ $variant->created_at ? $variant->created_at->format('d-m-Y H:i') : '—' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Hierarchy -->
                            <div class="col-md-3 mb-3">
                                <label>Brand <span class="text-danger">*</span></label>
                                <select name="brand_id" class="form-control form-select" required>
                                    <option value="">Select Brand</option>
                                    @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}" {{ old('brand_id', $variant->brand_id) ==
                                        $brand->id ? 'selected' : '' }}>
                                        {{ $brand->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Segment <span class="text-danger">*</span></label>
                                <select name="segment_id" class="form-control form-select" required>
                                    <option value="">Select Segment</option>
                                    @foreach($segments as $segment)
                                    <option value="{{ $segment->id }}" {{ old('segment_id', $variant->segment_id) ==
                                        $segment->id ? 'selected' : '' }}>
                                        {{ $segment->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Vehicle Model <span class="text-danger">*</span></label>
                                <select name="vehicle_model_id" class="form-control form-select" required>
                                    <option value="">Select Model</option>
                                    @foreach($vehiclemodels as $model)
                                    <option value="{{ $model->id }}" {{ old('vehicle_model_id', $variant->
                                        vehicle_model_id) == $model->id ? 'selected' : '' }}>
                                        {{ $model->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Sub Segment (Optional)</label>
                                <select name="sub_segment_id" class="form-control form-select">
                                    <option value="">Select Sub Segment</option>
                                    @foreach($subsegments as $sub)
                                    <option value="{{ $sub->id }}" {{ old('sub_segment_id', $variant->sub_segment_id) ==
                                        $sub->id ? 'selected' : '' }}>
                                        {{ $sub->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Variant Details -->
                            <div class="col-md-6 mb-3">
                                <label>Variant Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control"
                                    value="{{ old('name', $variant->name) }}" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Custom Name (Optional)</label>
                                <input type="text" name="custom_name" class="form-control"
                                    value="{{ old('custom_name', $variant->custom_name) }}">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>OEM Code</label>
                                <input type="text" name="oem_code" class="form-control text-uppercase"
                                    value="{{ old('oem_code', $variant->oem_code) }}"
                                    style="text-transform: uppercase;">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Seating Capacity</label>
                                <input type="number" name="seating_capacity" class="form-control"
                                    value="{{ old('seating_capacity', $variant->seating_capacity) }}" min="1">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Wheels</label>
                                <input type="number" name="wheels" class="form-control"
                                    value="{{ old('wheels', $variant->wheels ?? 4) }}" min="2">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>GVW (kg)</label>
                                <input type="number" name="gvw" class="form-control"
                                    value="{{ old('gvw', $variant->gvw) }}">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Engine CC</label>
                                <input type="text" name="cc_capacity" class="form-control"
                                    value="{{ old('cc_capacity', $variant->cc_capacity) }}">
                            </div>

                            <div class="col-md-10 mb-3">
                                <label>Description</label>
                                <textarea name="description" class="form-control"
                                    rows="3">{{ old('description', $variant->description) }}</textarea>
                            </div>

                            <div class="col-md-1 mb-3">
                                <label class="form-label">CSD Available?</label>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="is_csd" value="0">
                                    <input type="checkbox" name="is_csd" value="1" class="form-check-input" {{
                                        old('is_csd', $variant->is_csd) ? 'checked' : '' }}>
                                </div>
                            </div>

                            <div class="col-md-1 mb-3">
                                <label class="form-label">Is Active?</label>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" {{
                                        old('is_active', $variant->is_active) ? 'checked' : '' }}>
                                </div>
                            </div>

                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-success btn-lg px-5">
                                <i class="la la-save"></i> Update Variant
                            </button>
                            <a href="{{ backpack_url('variant') }}" class="btn btn-secondary btn-lg">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection