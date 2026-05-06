@extends(backpack_view('blank'))

@section('title', 'Add New Vehicle Model')

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
                    <h2 class="mb-0">Add New Vehicle Model</h2>
                </div>
                <div class="card-body">

                    <form method="POST" action="{{ backpack_url('vehicle-model') }}">
                        @csrf

                        <div class="row">

                            <div class="col-md-3 mb-3">
                                <label>Brand <span class="text-danger">*</span></label>
                                <select name="brand_code" class="form-control form-select" required>
                                    <option value="">Select Brand</option>
                                    @foreach($brands as $brand)
                                    <option value="{{ $brand->code }}" {{ old('brand_code')==$brand->code ? 'selected' :
                                        ''
                                        }}>
                                        {{ $brand->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Segment <span class="text-danger">*</span></label>
                                <select name="segment_code" class="form-control form-select" required>
                                    <option value="">Select Segment</option>
                                    @foreach($segments as $segment)
                                    <option value="{{ $segment->code }}" {{ old('segment_code')==$segment->code ?
                                        'selected' :
                                        '' }}>
                                        {{ $segment->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Sub Segment Code <span class="text-danger">*</span></label>
                                <select name="sub_segment_code" class="form-control form-select" required>
                                    <option value="">Select Sub Segment</option>
                                    @foreach($subsegments as $subsegment)
                                    <option value="{{ $subsegment->code }}" {{ old('sub_segment_code')==$subsegment->
                                        code ? 'selected' :
                                        '' }}>
                                        {{ $subsegment->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>



                            <div class="col-md-3 mb-3">
                                <label>OEM Name</label>
                                <input type="text" name="oem_name" class="form-control text-uppercase"
                                    value="{{ old('oem_name') }}" style="text-transform: uppercase;">
                                <small class="text-muted">e.g. BREZ1, EXTER1, NEXON</small>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Model Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                            </div>





                            <div class="col-md-1 mb-3">
                                <label class="form-label">Is Active?</label>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" {{
                                        old('is_active', true) ? 'checked' : '' }}>
                                </div>
                            </div>

                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-success btn-lg px-5">
                                <i class="la la-save"></i> Create Vehicle Model
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