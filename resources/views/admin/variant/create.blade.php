@extends(backpack_view('blank'))

@section('title', 'Add New Variant')

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
                    <h2 class="mb-0">Add New Variant</h2>
                </div>

                <div class="card-body">

                    <form method="POST" action="{{ backpack_url('variant') }}">
                        @csrf

                        <div class="row">

                            <!-- RELATIONS -->
                            <div class="col-md-3 mb-3">
                                <label>Brand *</label>
                                <select name="brand_id" class="form-control" required>
                                    <option value="">Select</option>
                                    @foreach($brands as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Segment *</label>
                                <select name="segment_id" class="form-control" required>
                                    <option value="">Select</option>
                                    @foreach($segments as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Model *</label>
                                <select name="vehicle_model_id" class="form-control" required>
                                    <option value="">Select</option>
                                    @foreach($vehiclemodels as $m)
                                    <option value="{{ $m->id }}">{{ $m->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Sub Segment</label>
                                <select name="sub_segment_id" class="form-control">
                                    <option value="">Select</option>
                                    @foreach($subsegments as $ss)
                                    <option value="{{ $ss->id }}">{{ $ss->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- CORE -->
                            <div class="col-md-4 mb-3">
                                <label>Variant Code *</label>
                                <input type="text" name="code" class="form-control" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>OEM Name</label>
                                <input type="text" name="oem_name" class="form-control">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Custom Name</label>
                                <input type="text" name="custom_name" class="form-control">
                            </div>

                            <!-- BASIC SPECS -->
                            <div class="col-md-3 mb-3">
                                <label>Seating</label>
                                <input type="number" name="seating_capacity" class="form-control">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Wheels</label>
                                <input type="number" name="wheels" class="form-control" value="4">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>GVW</label>
                                <input type="number" name="gvw" class="form-control">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>CC Capacity</label>
                                <input type="text" name="cc_capacity" class="form-control">
                            </div>

                            <!-- VEHICLE DETAILS -->
                            <div class="col-md-3 mb-3">
                                <label>Transmission</label>
                                <input type="text" name="transmission" class="form-control">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Drivetrain</label>
                                <input type="text" name="drivetrain" class="form-control">
                            </div>

                            <!-- LOOKUPS -->
                            <div class="col-md-3 mb-3">
                                <label>Permit</label>
                                <select name="permit_id" class="form-control">
                                    <option value="">Select</option>
                                    @foreach(\App\Models\Vehicle\Variant::getPermitOptions() as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Fuel Type</label>
                                <select name="fuel_type_id" class="form-control">
                                    <option value="">Select</option>
                                    @foreach(\App\Models\Vehicle\Variant::getFuelTypeOptions() as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Body Type</label>
                                <select name="body_type_id" class="form-control">
                                    <option value="">Select</option>
                                    @foreach(\App\Models\Vehicle\Variant::getBodyTypeOptions() as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Body Make</label>
                                <select name="body_make_id" class="form-control">
                                    <option value="">Select</option>
                                    @foreach(\App\Models\Vehicle\Variant::getBodyMakeOptions() as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Status</label>
                                <select name="status_id" class="form-control">
                                    <option value="">Select</option>
                                    @foreach(\App\Models\Vehicle\Variant::getStatusOptions() as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>CSD Index</label>
                                <input type="number" step="0.01" name="csd_index" class="form-control">
                            </div>

                            <!-- SWITCHES -->
                            <div class="col-md-2 mb-3">
                                <label>Is CSD</label><br>
                                <input type="hidden" name="is_csd" value="0">
                                <input type="checkbox" name="is_csd" value="1">
                            </div>

                            <div class="col-md-2 mb-3">
                                <label>Active</label><br>
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" checked>
                            </div>

                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-success btn-lg">Create Variant</button>
                            <a href="{{ backpack_url('variant') }}" class="btn btn-secondary">Cancel</a>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>
</div>
@endsection