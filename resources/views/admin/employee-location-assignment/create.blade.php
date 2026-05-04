@extends(backpack_view('blank'))

@section('title', 'Assign Location')

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
                <div class="card-header">
                    <h2 class="mb-0">Assign Location to Employee</h2>
                </div>
                <div class="card-body">

                    <form method="POST" action="{{ backpack_url('employee-location-assignment') }}">
                        @csrf

                        <div class="row">

                            <div class="col-md-4 mb-3">
                                <label>Employee <span class="text-danger">*</span></label>
                                <select name="employee_id" class="form-control form-select" required>
                                    <option value="">-- Select Employee --</option>
                                    @foreach(App\Models\Admin\Employee::with('person')->orderBy('code')->get() as $emp)
                                    <option value="{{ $emp->id }}">
                                        {{ $emp->code }} - {{ $emp->person ? trim($emp->person->first_name.'
                                        '.$emp->person->last_name) : 'N/A' }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Location <span class="text-danger">*</span></label>
                                <select name="location_id" class="form-control form-select" required>
                                    <option value="">-- Select Location --</option>
                                    @foreach(App\Models\Admin\Location::orderBy('name')->get() as $loc)
                                    <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Branch (Optional)</label>
                                <select name="branch_code" class="form-control form-select">
                                    <option value="">— No Branch —</option>
                                    @foreach(App\Models\Admin\Branch::orderBy('name')->get() as $branch)
                                    <option value="{{ $branch->code }}">{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>





                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-success btn-lg px-5">
                                <i class="la la-save"></i> Assign Location
                            </button>
                            <a href="{{ backpack_url('employee-location-assignment') }}"
                                class="btn btn-secondary btn-lg">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection