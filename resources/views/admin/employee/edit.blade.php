@extends(backpack_view('blank'))

@section('title', 'Edit Employee')

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
                    <h2 class="mb-0">Edit Employee Information</h2>
                </div>
                <div class="card-body">

                    <form method="POST" action="{{ backpack_url('employee/' . $employee->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">

                            <!-- Employee Code -->
                            <div class="col-md-4 mb-3">
                                <label>Employee Code</label>
                                <input type="text" name="code" class="form-control" value="{{ $employee->code }}"
                                    readonly>
                            </div>

                            <!-- Person -->
                            <div class="col-md-4 mb-3">
                                <label>Person</label>
                                <select name="person_code" class="form-control form-select">
                                    @foreach($persons as $p)
                                    <option value="{{ $p->person_code }}" {{ $employee->person_code == $p->person_code ?
                                        'selected' : '' }}>
                                        {{ $p->first_name }} {{ $p->last_name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Designation -->
                            <div class="col-md-4 mb-3">
                                <label>Designation</label>
                                <select name="desig_code" class="form-control form-select">
                                    @foreach($designations as $d)
                                    <option value="{{ $d->code }}" {{ $employee->desig_code == $d->code ? 'selected' :
                                        '' }}>
                                        {{ $d->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Branch -->
                            <div class="col-md-4 mb-3">
                                <label>Primary Branch</label>
                                <select name="primary_branch_code" class="form-control form-select">
                                    @foreach($branches as $b)
                                    <option value="{{ $b->code }}" {{ $employee->primary_branch_code == $b->code ?
                                        'selected' : '' }}>
                                        {{ $b->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Department -->
                            <div class="col-md-4 mb-3">
                                <label>Primary Department</label>
                                <select name="primary_dept_code" class="form-control form-select">
                                    @foreach($departments as $d)
                                    <option value="{{ $d->code }}" {{ $employee->primary_dept_code == $d->code ?
                                        'selected' : '' }}>
                                        {{ $d->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Division -->
                            <div class="col-md-4 mb-3">
                                <label>Division</label>
                                <input type="text" name="primary_div_code" class="form-control"
                                    value="{{ $employee->primary_div_code }}">
                            </div>

                            <!-- Location -->
                            <div class="col-md-4 mb-3">
                                <label>Location</label>
                                <input type="text" name="primary_loc_code" class="form-control"
                                    value="{{ $employee->primary_loc_code }}">
                            </div>

                            <!-- Vertical -->
                            <div class="col-md-4 mb-3">
                                <label>Vertical</label>
                                <input type="text" name="vertical_code" class="form-control"
                                    value="{{ $employee->vertical_code }}">
                            </div>

                            <!-- Segment -->
                            <div class="col-md-4 mb-3">
                                <label>Segment (5 Characters)</label>
                                <input type="text" name="segment_code" class="form-control"
                                    value="{{ $employee->segment_code }}">
                            </div>

                            <!-- Sub Segment -->
                            <div class="col-md-4 mb-3">
                                <label>Sub Segment (5 Characters)</label>
                                <input type="text" name="sub_segment_code" class="form-control"
                                    value="{{ $employee->sub_segment_code }}">
                            </div>

                        </div>

                        <br>

                        <button class="btn btn-success">Update</button>
                        <a href="{{ backpack_url('employee') }}" class="btn btn-secondary">Cancel</a>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
