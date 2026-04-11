@extends(backpack_view('blank'))

@section('title', 'Edit Vehicle Model - ' . $vehiclemodel->name)

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
                    <h2 class="mb-0">Edit Vehicle Model Information</h2>
                </div>
                <div class="card-body">

                    <form method="POST" action="{{ backpack_url('vehicle-model/' . $vehiclemodel->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">

                            <!-- Read Only Section -->
                            <div class="col-md-12 mb-4">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Vehicle Model ID</label>
                                        <div class="readonly-value">{{ $vehiclemodel->id }}</div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Created At</label>
                                        <div class="readonly-value">
                                            {{ $vehiclemodel->created_at ? $vehiclemodel->created_at->format('d-m-Y
                                            H:i') : '—' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Editable Fields -->
                            <div class="col-md-3 mb-3">
                                <label>Brand <span class="text-danger">*</span></label>
                                <select name="brand_id" class="form-control form-select" required>
                                    <option value="">Select Brand</option>
                                    @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}" {{ old('brand_id', $vehiclemodel->brand_id) ==
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
                                    <option value="{{ $segment->id }}" {{ old('segment_id', $vehiclemodel->segment_id)
                                        == $segment->id ? 'selected' : '' }}>
                                        {{ $segment->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Sub Segment (Optional)</label>
                                <select name="sub_segment_id" class="form-control form-select">
                                    <option value="">Select Sub Segment</option>
                                    @foreach($subsegments as $subsegment)
                                    <option value="{{ $subsegment->id }}" {{ old('sub_segment_id', $vehiclemodel->
                                        sub_segment_id) == $subsegment->id ? 'selected' : '' }}>
                                        {{ $subsegment->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>OEM Code</label>
                                <input type="text" name="oem_code" class="form-control text-uppercase"
                                    value="{{ old('oem_code', $vehiclemodel->oem_code) }}"
                                    style="text-transform: uppercase;">
                                <small class="text-muted">e.g. BREZ1, EXTER1, NEXON</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Model Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control"
                                    value="{{ old('name', $vehiclemodel->name) }}" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Custom Name (Optional)</label>
                                <input type="text" name="custom_name" class="form-control"
                                    value="{{ old('custom_name', $vehiclemodel->custom_name) }}">
                            </div>

                            <div class="col-md-11 mb-3">
                                <label>Description</label>
                                <textarea name="description" class="form-control"
                                    rows="3">{{ old('description', $vehiclemodel->description) }}</textarea>
                            </div>

                            <div class="col-md-1 mb-3">
                                <label class="form-label">Is Active?</label>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" {{
                                        old('is_active', $vehiclemodel->is_active) ? 'checked' : '' }}>
                                </div>
                            </div>

                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-success btn-lg px-5">
                                <i class="la la-save"></i> Update Vehicle Model
                            </button>
                            <a href="{{ backpack_url('vehicle-model') }}" class="btn btn-secondary btn-lg">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection