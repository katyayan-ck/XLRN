@extends(backpack_view('blank'))

@section('header')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">

<style>
    .required-mark {
        color: #dc3545;
        margin-left: 2px;
    }

    .form-control[readonly] {
        background-color: #e9ecef;
        opacity: 1;
    }

    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border: 1px solid rgba(0, 0, 0, 0.125);
    }

    .card-body h2 {
        font-weight: 600;
        color: #495057;
    }

    input.uppercase {
        text-transform: uppercase;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header flex-nowrap">
        <div class="row">
            <div class="col-lg-8">
                <div class="page-header-title">
                    <i class="ik ik-car bg-blue"></i>
                    <h3 class="d-inline-block mb-0">Edit Booking</h3>
                    <small class="text-muted ml-3">ID: {{ $entry->id }}</small>
                </div>
            </div>
            <div class="col-lg-4 text-end">
                <nav class="breadcrumb-container" aria-label="breadcrumb">
                    <ol class="breadcrumb justify-content-end">
                        <li class="breadcrumb-item"><a href="{{ backpack_url('dashboard') }}"><i class="ik ik-home"></i>
                                Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ backpack_url('booking') }}">Bookings</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="row">
        @include(backpack_view('inc.alerts'))

        <div class="col-md-12">
            <form id="bookingForm" method="POST" action="{{ backpack_url('booking/'.$entry->id) }}"
                enctype="multipart/form-data" class="forms-sample">
                @csrf
                @method('PUT')

                <div class="card mt-0">
                    <div class="card-body">
                        <h2 class="mb-4">Payment Details (Locked)</h2>
                        <div class="row">
                            <div class="col-sm-2 form-group">
                                <label>Customer Type</label>
                                <input type="text" class="form-control"
                                    value="{{ $entry->b_type == 'Active' ? 'Actual' : 'Dummy' }}" readonly>
                                <input type="hidden" name="customer_type" value="{{ $entry->b_type }}">
                            </div>
                            <div class="col-sm-3 form-group">
                                <label>Customer Category</label>
                                <input type="text" class="form-control" value="{{ $entry->b_cat }}" readonly>
                            </div>
                            <div class="col-sm-2 form-group">
                                <label>Booking Date</label>
                                <input type="text" class="form-control"
                                    value="{{ \Carbon\Carbon::parse($entry->booking_date)->format('d-M-Y') }}" readonly>
                                <input type="hidden" name="booking_date_actual" value="{{ $entry->booking_date }}">
                            </div>
                            <div class="col-sm-2 form-group">
                                <label>Collection Type</label>
                                <input type="text" class="form-control" readonly
                                    value="{{ $entry->col_type == 1 ? 'Receipt' : ($entry->col_type == 2 ? 'Field Collection By Sales Team' : ($entry->col_type == 3 ? 'Field Collection By DSA' : 'Used Car Purchase')) }}">
                                <input type="hidden" name="col_type" value="{{ $entry->col_type }}">
                            </div>
                            <div class="col-sm-3 form-group">
                                <label>Collected By</label>
                                <input type="text" class="form-control" value="{{ $data['collector_name'] }}" readonly>
                                <input type="hidden" name="user" value="{{ $entry->col_by }}">
                            </div>
                            <div class="col-sm-4 form-group">
                                <label>Booking Amount</label>
                                <input type="text" class="form-control" value="{{ $entry->booking_amount }}" readonly>
                                <input type="hidden" name="booking_amount" value="{{ $entry->booking_amount }}">
                            </div>
                            <div class="col-sm-4 form-group">
                                <label>Receipt/Voucher No.</label>
                                <input type="text" class="form-control" value="{{ $entry->receipt_no }}" readonly>
                                <input type="hidden" name="receipt_no" value="{{ $entry->receipt_no }}">
                            </div>
                            <div class="col-sm-4 form-group">
                                <label>Receipt/Voucher Date</label>
                                <input type="text" class="form-control"
                                    value="{{ $entry->receipt_date ? \Carbon\Carbon::parse($entry->receipt_date)->format('d-M-Y') : '' }}"
                                    readonly>
                                <input type="hidden" name="receipt_date_actual" value="{{ $entry->receipt_date }}">
                            </div>
                        </div>
                    </div>
                </div>


                <div class="card mt-4">
                    <div class="card-body">
                        <h2 class="mb-4">Customer Details</h2>
                        <div class="row">
                            <div class="col-sm-3 form-group">
                                <label for="name">Customer Name <span class="required-mark">*</span></label>
                                <input type="text" name="name" id="name" class="form-control uppercase"
                                    value="{{ $entry->name }}" required>
                            </div>

                            <div class="col-sm-3 form-group">
                                <label for="care_of">Care Of <span class="required-mark">*</span></label>
                                <select name="care_of" id="care_of" class="form-control select2" required>
                                    <option value="">Please Select...</option>
                                    <option value="1" {{ $entry->care_of_type == 1 ? 'selected' : '' }}>Son of</option>
                                    <option value="2" {{ $entry->care_of_type == 2 ? 'selected' : '' }}>Daughter of
                                    </option>
                                    <option value="3" {{ $entry->care_of_type == 3 ? 'selected' : '' }}>Married to
                                    </option>
                                    <option value="4" {{ $entry->care_of_type == 4 ? 'selected' : '' }}>Guardian Name
                                    </option>
                                    <option value="5" id="ownedByOption" {{ $entry->care_of_type == 5 ? 'selected' : ''
                                        }}>Owned By</option>
                                </select>
                            </div>

                            <div class="col-sm-3 form-group">
                                <label id="careofnamelabel">
                                    {{ $entry->b_cat === 'Firm' ? 'Owner Name' : 'Care Of Name' }} <span
                                        class="required-mark">*</span>
                                </label>
                                <input type="text" name="care_of_name" id="care_of_name" class="form-control uppercase"
                                    value="{{ $entry->care_of }}" required>
                            </div>

                            <div class="col-sm-3 form-group">
                                <label for="mobile">Contact No. <span class="required-mark">*</span></label>
                                <input type="text" name="mobile" id="mobile" class="form-control"
                                    value="{{ $entry->mobile }}" required>
                            </div>

                            <div class="col-sm-3 form-group">
                                <label for="alt_mobile">Alternate Contact No.</label>
                                <input type="text" name="alt_mobile" id="alt_mobile" class="form-control"
                                    value="{{ $entry->alt_mobile }}">
                            </div>

                            <div class="col-sm-3 form-group">
                                <label for="gender">Gender <span class="required-mark">*</span></label>
                                <select name="gender" id="gender" class="form-control select2" required>
                                    <option value="Male" {{ $entry->gender == 'Male' ? 'selected' : '' }}>Male</option>
                                    <option value="Female" {{ $entry->gender == 'Female' ? 'selected' : '' }}>Female
                                    </option>
                                    <option value="Transgender" {{ $entry->gender == 'Transgender' ? 'selected' : ''
                                        }}>Transgender</option>
                                </select>
                            </div>

                            <div class="col-sm-3 form-group">
                                <label for="occupation">Occupation <span class="required-mark">*</span></label>
                                <select name="occupation" id="occupation" class="form-control select2" required>
                                    <option value="">Please Select...</option>
                                    <option value="Agriculture" {{ $entry->occ == 'Agriculture' ? 'selected' : ''
                                        }}>Agriculture</option>
                                    <option value="Business" {{ $entry->occ == 'Business' ? 'selected' : '' }}>Business
                                    </option>
                                    <option value="Salaried (Govt.)" {{ $entry->occ == 'Salaried (Govt.)' ? 'selected' :
                                        '' }}>Salaried (Govt.)</option>
                                    <option value="Salaried (Pvt.)" {{ $entry->occ == 'Salaried (Pvt.)' ? 'selected' :
                                        '' }}>Salaried (Pvt.)</option>
                                    <option value="Self Employed (Professional)" {{ $entry->occ == 'Self Employed
                                        (Professional)' ? 'selected' : '' }}>Self Employed (Professional)</option>
                                    <option value="Pensioner" {{ $entry->occ == 'Pensioner' ? 'selected' : ''
                                        }}>Pensioner</option>
                                    <option value="Other" {{ $entry->occ == 'Other' ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>

                            <div class="col-sm-3 form-group">
                                <label for="pan_no">PAN Card No.</label>
                                <input type="text" name="pan_no" id="pan_no" class="form-control uppercase"
                                    value="{{ $entry->pan_no }}">
                            </div>

                            <div class="col-sm-3 form-group">
                                <label for="adhar_no">Aadhar No.</label>
                                <input type="text" name="adhar_no" id="adhar_no" class="form-control"
                                    value="{{ $entry->adhar_no }}">
                            </div>

                            <!-- GSTN with Unregistered Toggle -->
                            <div class="col-sm-3 form-group" id="gstn-group">
                                <label for="gstn">GSTN
                                    <span class="required-mark" id="gstn-required"
                                        style="display: {{ ($entry->gstn == '0' || $entry->gstn === null) ? 'none' : 'inline' }};">*</span>
                                </label>
                                <input type="text" name="gstn" id="gstn" class="form-control uppercase"
                                    value="{{ ($entry->gstn != '0' && $entry->gstn !== null) ? $entry->gstn : '' }}"
                                    placeholder="Enter GSTN No." {{ ($entry->gstn == '0' || $entry->gstn === null) ?
                                'disabled' : '' }}>
                                <div class="form-check mt-2">
                                    <input type="checkbox" class="form-check-input" id="gst_unregistered" {{
                                        ($entry->gstn == '0' || $entry->gstn === null) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="gst_unregistered">GST Unregistered</label>
                                </div>
                            </div>

                            <!-- DOB & Age -->
                            <div class="col-sm-3 form-group">
                                <label for="customer_dob">Customer D.O.B. <span class="required-mark">*</span></label>
                                <input type="text" name="customer_dob" id="customer_dob" class="form-control flatpickr"
                                    value="{{ $entry->c_dob ? \Carbon\Carbon::parse($entry->c_dob)->format('d-M-Y') : '' }}"
                                    placeholder="dd-mmm-yyyy" required>
                                <input type="hidden" name="hidden_customer_dob" id="hidden_customer_dob"
                                    value="{{ $entry->c_dob }}">
                            </div>

                            <div class="col-sm-3 form-group">
                                <label for="customer_age">Customer Age</label>
                                <input type="text" name="customer_age" id="customer_age" class="form-control"
                                    value="{{ $entry->c_dob ? \Carbon\Carbon::parse($entry->c_dob)->age : '' }}"
                                    readonly>
                            </div>

                            <!-- Branch & Location -->
                            <div class="col-sm-3 form-group">
                                <label for="branch">Branch <span class="required-mark">*</span></label>
                                <select name="branch" id="branch" class="form-control select2" required>
                                    <option value="">Please Select...</option>
                                    @foreach($data['branches'] as $branch)
                                    <option value="{{ $branch->id }}" {{ $entry->branch_id == $branch->id ? 'selected' :
                                        '' }}>
                                        {{ $branch->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-sm-3 form-group">
                                <label for="location">Location <span class="required-mark">*</span></label>
                                <select name="location_id" id="location" class="form-control select2" required>
                                    <option value="">Please Select...</option>
                                    @foreach($data['locations'] as $location)
                                    <option value="{{ $location['id'] }}" {{ $entry->location_id == $location['id'] ?
                                        'selected' : '' }}>
                                        {{ $location['name'] . ' - ' . $location['code'] }}
                                    </option>
                                    @endforeach
                                    <option value="0" {{ $entry->location_id == 0 ? 'selected' : '' }}>OTHER</option>
                                </select>
                            </div>

                            <div class="col-sm-3 form-group" id="location_other_group"
                                style="{{ $entry->location_id == 0 ? '' : 'display:none;' }}">
                                <label for="location_other">Other Location</label>
                                <input type="text" name="location_other" id="location_other"
                                    class="form-control uppercase" value="{{ $entry->location_other }}" {{
                                    $entry->location_id == 0 ? '' : 'disabled' }}>
                            </div>

                            <!-- Referred By Details -->

                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-body">
                        <div class="col-sm-12 mt-4">
                            <h2 class="mb-3">Referred By Details</h2>
                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="referred_by_checkbox"
                                    name="referred_by" {{ $entry->r_name ? 'checked' : '' }}>
                                <label class="form-check-label" for="referred_by_checkbox">Referred By</label>
                            </div>

                            <div class="row" id="referred_by_fields"
                                style="{{ $entry->r_name ? '' : 'display:none;' }}">
                                <div class="col-sm-3 form-group">
                                    <label for="ref_customer_name">Customer Name <span class="required-mark"
                                            style="display: {{ $entry->r_name ? 'inline' : 'none' }};">*</span></label>
                                    <input type="text" name="ref_customer_name" id="ref_customer_name"
                                        class="form-control uppercase" value="{{ $entry->r_name ?? '' }}"
                                        {{ $entry->r_name ? 'required' : '' }}>
                                </div>
                                <div class="col-sm-3 form-group">
                                    <label for="ref_mobile_no">Mobile No. <span class="required-mark"
                                            style="display: {{ $entry->r_name ? 'inline' : 'none' }};">*</span></label>
                                    <input type="text" name="ref_mobile_no" id="ref_mobile_no" class="form-control"
                                        value="{{ $entry->r_mobile ?? '' }}"
                                        {{ $entry->r_name ? 'required' : '' }}>
                                </div>
                                <div class="col-sm-3 form-group">
                                    <label for="ref_existing_model">Existing Model <span class="required-mark"
                                            style="display: {{ $entry->r_name ? 'inline' : 'none' }};">*</span></label>
                                    <input type="text" name="ref_existing_model" id="ref_existing_model"
                                        class="form-control uppercase" value="{{ $entry->r_model ?? '' }}"
                                        {{ $entry->r_name ? 'required' : '' }}>
                                </div>
                                <div class="col-sm-3 form-group">
                                    <label for="ref_variant">Variant <span class="required-mark"
                                            style="display: {{ $entry->r_name ? 'inline' : 'none' }};">*</span></label>
                                    <input type="text" name="ref_variant" id="ref_variant"
                                        class="form-control uppercase" value="{{ $entry->r_variant ?? '' }}"
                                        {{ $entry->r_name ? 'required' : '' }}>
                                </div>
                                <div class="col-sm-3 form-group">
                                    <label for="ref_chassis_reg_no">Chassis / Regn. No. <span class="required-mark"
                                            style="display: {{ $entry->r_name ? 'inline' : 'none' }};">*</span></label>
                                    <input type="text" name="ref_chassis_reg_no" id="ref_chassis_reg_no"
                                        class="form-control uppercase" value="{{ $entry->r_chassis ?? '' }}"
                                        {{ $entry->r_name ? 'required' : '' }}>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-body">
                        <h2 class="mb-4">Purchase Type Details</h2>
                        <div class="row">
                            <div class="col-sm-2 form-group">
                                <label for="buyer_type">Purchase Type <span class="required-mark">*</span></label>
                                <select name="buyer_type" id="buyer_type" class="form-control select2" required>
                                    <option value="First time Buyer" {{ $entry->buyer_type == 'First time Buyer' ?
                                        'selected' : '' }}>First time Buyer</option>
                                    <option value="Additional Buy" {{ $entry->buyer_type == 'Additional Buy' ?
                                        'selected' : '' }}>Additional Buy</option>
                                    <option value="Exchange Buy" {{ $entry->buyer_type == 'Exchange Buy' ? 'selected' :
                                        '' }}>Exchange Buy</option>
                                    <option value="Scrappage" {{ $entry->buyer_type == 'Scrappage' ? 'selected' : ''
                                        }}>Scrappage</option>
                                </select>
                            </div>

                            <div class="col-sm-2 form-group">
                                <label for="enum_master1">Brand (Make 1) <span class="required-mark"
                                        style="display: none;">*</span></label>
                                <select name="enum_master1" id="enum_master1" class="form-control select2" disabled>
                                    <option value="">Please Select...</option>
                                    @foreach ($data['enum_master'] as $enum)
                                    <option value="{{ $enum->id }}" {{ (int)$entry->exist_oem1 === (int)$enum->id ?
                                        'selected' : '' }}>
                                        {{ $enum->value }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-sm-3 form-group">
                                <label for="vehicle_details">Model & Variant 1 <span class="required-mark"
                                        style="display: none;">*</span></label>
                                <input type="text" name="vehicle_details" id="vehicle_details"
                                    class="form-control uppercase" value="{{ $entry->vh1_detail }}" disabled>
                            </div>

                            <div class="col-sm-2 form-group">
                                <label for="enum_master2">Brand (Make 2) <span class="required-mark"
                                        style="display: none;">*</span></label>
                                <select name="enum_master2" id="enum_master2" class="form-control select2" disabled>
                                    <option value="">Please Select...</option>
                                    @foreach ($data['enum_master'] as $enum)
                                    <option value="{{ $enum->id }}" {{ (int)$entry->exist_oem2 === (int)$enum->id ?
                                        'selected' : '' }}>
                                        {{ $enum->value }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-sm-3 form-group">
                                <label for="vehicle_details2">Model & Variant 2 <span class="required-mark"
                                        style="display: none;">*</span></label>
                                <input type="text" name="vehicle_details2" id="vehicle_details2"
                                    class="form-control uppercase" value="{{ $entry->vh2_detail }}" disabled>
                            </div>

                            <div class="col-sm-4 form-group">
                                <label for="registration_no">Vehicle Registration No. <span class="required-mark"
                                        style="display: none;">*</span></label>
                                <input type="text" name="registration_no" id="registration_no"
                                    class="form-control uppercase" value="{{ $entry->registration_no }}" disabled>
                            </div>

                            <div class="col-sm-4 form-group">
                                <label for="manufacturing_year">Vehicle Manufacturing Year <span class="required-mark"
                                        style="display: none;">*</span></label>
                                <input type="number" name="manufacturing_year" id="manufacturing_year"
                                    class="form-control" value="{{ $entry->make_year }}" disabled>
                            </div>

                            <div class="col-sm-4 form-group">
                                <label for="odometer_reading">Vehicle Odometer Reading <span class="required-mark"
                                        style="display: none;">*</span></label>
                                <input type="text" name="odometer_reading" id="odometer_reading" class="form-control"
                                    value="{{ $entry->odo_reading }}" disabled>
                            </div>

                            <div class="col-sm-3 form-group">
                                <label for="expected_price">Used Vehicle Expected Price <span class="required-mark"
                                        style="display: none;">*</span></label>
                                <input type="number" name="expected_price" id="expected_price" class="form-control"
                                    value="{{ $entry->expected_price }}" disabled>
                            </div>

                            <div class="col-sm-3 form-group">
                                <label for="offered_price">Used Vehicle Offered Price <span class="required-mark"
                                        style="display: none;">*</span></label>
                                <input type="number" name="offered_price" id="offered_price" class="form-control"
                                    value="{{ $entry->offered_price }}" disabled>
                            </div>

                            <div class="col-sm-3 form-group">
                                <label for="exchange_bonus">New Vehicle Exchange Bonus <span class="required-mark"
                                        style="display: none;">*</span></label>
                                <input type="number" name="exchange_bonus" id="exchange_bonus" class="form-control"
                                    value="{{ $entry->exchange_bonus }}" disabled>
                            </div>

                            <div class="col-sm-3 form-group">
                                <label for="difference">Price Gap</label>
                                <input type="text" name="difference" id="difference" class="form-control"
                                    value="{{ $entry->expected_price - ($entry->offered_price + $entry->exchange_bonus) }}"
                                    readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- NEW: Vehicle Details Card -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h2 class="mb-4">Vehicle Details</h2>
                        <div class="row">
                            <!-- Hidden vh_id -->
                            <input type="hidden" name="vh_id" id="vh_id" value="{{ $entry->vh_id }}">

                            <!-- Segment -->
                            <div class="col-sm-3 form-group">
                                <label for="segment_id">Segment <span class="required-mark">*</span></label>
                                <select name="segment_id" id="segment_id" class="form-control select2" required>
                                    <option value="">Please Select...</option>
                                    @foreach($data['segments'] as $seg)
                                    <option value="{{ $seg['id'] }}" {{ $entry->segment_id == $seg['id'] ? 'selected' :
                                        '' }}>
                                        {{ $seg['value'] }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Model -->
                            <div class="col-sm-3 form-group">
                                <label for="model">Model <span class="required-mark">*</span></label>
                                <select name="model" id="model" class="form-control select2" required disabled>
                                    <option value="">Please Select...</option>
                                </select>
                            </div>

                            <!-- Variant -->
                            <div class="col-sm-3 form-group">
                                <label for="variant">Variant <span class="required-mark">*</span></label>
                                <select name="variant" id="variant" class="form-control select2" required disabled>
                                    <option value="">Please Select...</option>
                                </select>
                            </div>

                            <!-- Color -->
                            <div class="col-sm-3 form-group">
                                <label for="color">Color <span class="required-mark">*</span></label>
                                <select name="color" id="color" class="form-control select2" required disabled>
                                    <option value="">Please Select...</option>
                                </select>
                            </div>

                            <!-- Seating -->
                            <div class="col-sm-3 form-group">
                                <label for="seating">Seating</label>
                                <input type="text" name="seating" id="seating" class="form-control"
                                    value="{{ $entry->seating }}" readonly>
                            </div>

                            <!-- Accessories -->
                            <div class="col-sm-4 form-group">
                                <label for="accessories">Select Accessories</label>
                                <select name="accessories[]" id="accessories" class="form-control select2" multiple
                                    disabled>
                                    <!-- AJAX से fill होगा -->
                                </select>
                            </div>

                            <!-- Accessories Amount -->
                            <div class="col-sm-3 form-group">
                                <label for="apack_amount">Accessories Amount</label>
                                <input type="text" name="apack_amount" id="apack_amount" class="form-control"
                                    value="{{ $entry->apack_amount }}" readonly>
                            </div>

                            <!-- Allotted Chassis No. -->
                            <div class="col-sm-3 form-group">
                                <label for="chassis">Allotted Chassis No.</label>
                                <select name="chassis" id="chassis" class="form-control select2" disabled>
                                    <option value="">Please Select...</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Booking Details Card -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h2 class="mb-4">Booking Details</h2>
                        <div class="row">

                            <!-- Booking Mode -->
                            <div class="col-sm-3 form-group">
                                <label for="booking_mode">Booking Mode <span class="required-mark">*</span></label>
                                <select name="booking_mode" id="booking_mode" class="form-control select2" required>
                                    <option value="">Please Select...</option>
                                    <option value="Dealer" {{ $entry->b_mode == 'Dealer' ? 'selected' : '' }}>Dealer
                                    </option>
                                    <option value="Online" {{ $entry->b_mode == 'Online' ? 'selected' : '' }}>Online
                                    </option>
                                </select>
                            </div>

                            <!-- Online Booking Reference No. -->
                            <div class="col-sm-3 form-group" id="refrence_no_group"
                                style="display: {{ $entry->b_mode == 'Online' ? 'block' : 'none' }};">
                                <label for="refrence_no">Online Book Ref No. <span
                                        class="required-mark">*</span></label>
                                <input type="text" name="refrence_no" id="refrence_no" class="form-control uppercase"
                                    value="{{ $entry->online_bk_ref_no }}" {{ $entry->b_mode == 'Online' ? 'required' :
                                'disabled' }}>
                            </div>

                            <!-- Booking Source -->
                            <div class="col-sm-3 form-group">
                                <label for="booking_source">Booking Source <span class="required-mark">*</span></label>
                                <select name="booking_source" id="booking_source" class="form-control select2" required>
                                    <option value="">Please Select...</option>
                                    <option value="Dealer" {{ $entry->b_source == 'Dealer' ? 'selected' : '' }}>Dealer
                                        Sourcing</option>
                                    <option value="DSA" {{ $entry->b_source == 'DSA' ? 'selected' : '' }}>DSA</option>
                                </select>
                            </div>

                            <!-- DSA Details -->
                            <div class="col-sm-3 form-group" id="dsa_details_group"
                                style="display: {{ $entry->b_source == 'DSA' ? 'block' : 'none' }};">
                                <label for="dsa_details">Select DSA <span class="required-mark">*</span></label>
                                <select name="dsa_details" id="dsa_details" class="form-control select2" {{
                                    $entry->b_source == 'DSA' ? 'required' : 'disabled' }}>
                                    <option value="">Please Select...</option>
                                    @foreach($data['dsa_details'] as $dsa)
                                    <option value="{{ $dsa->id }}" {{ $entry->dsa_id == $dsa->id ? 'selected' : '' }}>
                                        {{ $dsa->name }} - {{ $dsa->mobile }} - {{ $dsa->location }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Sales Consultant -->
                            <div class="col-sm-3 form-group">
                                <label for="saleconsultant">Sales Consultant <span
                                        class="required-mark">*</span></label>
                                <select name="saleconsultant" id="saleconsultant" class="form-control select2" required>
                                    <option value="">Please Select...</option>
                                    @foreach($data['saleconsultants'] as $consultant)
                                    <option value="{{ $consultant->id }}" {{ $entry->consultant == $consultant->id ?
                                        'selected' : '' }}>
                                        {{ $consultant->name }} - ({{ $consultant->emp_code }})
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-sm-3 form-group">
                                <label>Delivery Date Type <span class="required-mark">*</span></label>
                                <div class="mt-2">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="delivery_type"
                                            id="delivery_expected" value="Expected" {{ old('delivery_type',
                                            $entry->del_type) == 'Expected' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="delivery_expected">Expected</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="delivery_type"
                                            id="delivery_confirmed" value="Confirmed" {{ old('delivery_type',
                                            $entry->del_type) == 'Confirmed' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="delivery_confirmed">Confirmed</label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-3 form-group">
                                <label for="expected_del_date">Delivery Date <span
                                        class="required-mark">*</span></label>
                                <input type="text" name="expected_del_date" id="expected_del_date"
                                    class="form-control flatpickr"
                                    value="{{ $entry->del_date ? \Carbon\Carbon::parse($entry->del_date)->format('d-M-Y') : '' }}"
                                    placeholder="dd-mmm-yyyy" required>
                                <input type="hidden" name="expected_del_date_actual" id="hidden_expected_del_date"
                                    value="{{ $entry->del_date }}">
                            </div>

                            <!-- Finance Mode -->
                            <div class="col-sm-3 form-group">
                                <label for="fin_mode">Finance Mode <span class="required-mark">*</span></label>
                                <select name="fin_mode" id="fin_mode" class="form-control select2" required>
                                    <option value="">Please Select...</option>
                                    <option value="In-house" {{ $entry->fin_mode == 'In-house' ? 'selected' : ''
                                        }}>In-house</option>
                                    <option value="Customer Self" {{ $entry->fin_mode == 'Customer_self' ? 'selected' :
                                        '' }}>Customer Self</option>
                                    <option value="Cash" {{ $entry->fin_mode == 'Cash' ? 'selected' : '' }}>Cash
                                    </option>
                                    <option value="Yet To Decide" {{ $entry->fin_mode == 'Yet To Decide' ? 'selected' :
                                        '' }}>Yet To Decide</option>
                                </select>
                            </div>

                            <!-- Financier -->
                            <div class="col-sm-3 form-group" id="financier_box"
                                style="display: {{ $entry->fin_mode == 'In-house' ? 'block' : 'none' }};">
                                <label for="financier">Financier <span class="required-mark">*</span></label>
                                <select name="financier" id="financier" class="form-control select2" {{ $entry->fin_mode
                                    == 'In-house' ? 'required' : 'disabled' }}>
                                    <option value="">Select Financier</option>
                                    @foreach($data['financiers'] as $fin)
                                    <option value="{{ $fin->id }}" data-short_name="{{ $fin->short_name }}" {{ $entry->
                                        financier == $fin->id ? 'selected' : '' }}>
                                        {{ $fin->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Financier Short Name (inside a group for toggle) -->
                            <div class="col-sm-3 form-group" id="financier_short_name_box"
                                style="display: {{ $entry->fin_mode == 'In-house' ? 'block' : 'none' }};">
                                <label for="financier_short_name">Financier Short Name</label>
                                <input type="text" id="financier_short_name" class="form-control" readonly
                                    value="{{ $entry->financier ? collect($data['financiers'])->firstWhere('id', $entry->fin_mode == 'In-house' ? $entry->financier : null)->short_name ?? '' : '' }}">
                            </div>

                            <!-- Loan Status -->
                            <div class="col-sm-3 form-group" id="loan_status_box"
                                style="display: {{ $entry->fin_mode == 'In-house' ? 'block' : 'none' }};">
                                <label for="loan_status">Loan File Status <span class="required-mark">*</span></label>
                                <select name="loan_status" id="loan_status" class="form-control select2" {{
                                    $entry->fin_mode == 'In-house' ? 'required' : 'disabled' }}>
                                    <option value="">Please Select...</option>
                                    <option value="Pending" {{ $entry->loan_status == 'Pending' ? 'selected' : ''
                                        }}>Pending</option>
                                    <option value="Complete" {{ $entry->loan_status == 'Complete' ? 'selected' : ''
                                        }}>Complete</option>
                                </select>
                            </div>

                        </div>

                        <!-- Make Order Checkbox (only for Personal/BEV segments) -->
                        @php
                        $showMakeOrder = in_array(
                        collect($data['segments'])->firstWhere('id', $entry->segment_id)->value ?? '',
                        ['Personal', 'BEV']
                        );
                        @endphp
                        <div class="row mt-3" id="make_order_container"
                            style="display: {{ $showMakeOrder ? 'block' : 'none' }};">
                            <div class="col-sm-12">
                                <div class="form-check">
                                    <input type="checkbox" name="make_order" id="make_order" value="1"
                                        class="form-check-input" {{ $entry->order == 1 ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold" for="make_order">
                                        Do you want to create a new sales order (SO Number) against this booking?
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Remarks -->
                        <div class="row mt-4">
                            <div class="col-sm-12 form-group">
                                <label for="details">Remarks <span class="required-mark">*</span></label>
                                <textarea name="details" id="details" class="form-control" rows="3"
                                    required>{{ $entry->details }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Submit Buttons -->
                <div class="mt-4 text-end">
                    <a href="{{ backpack_url('booking') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" id="submitBtn" class="btn btn-primary ms-2">Update Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('after_scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/additional-methods.min.js"></script>

<script>
    $(document).ready(function() {
    $('.select2').select2({ theme: 'bootstrap-5' });

    // Safe convert any data to array
    function toArray(data) {
        if (Array.isArray(data)) return data;
        if (data && typeof data === 'object') return Object.values(data);
        return [];
    }

    // Populate Select2 with full reset
    function populateSelect2(selectId, rawData, valueKey, textKey = valueKey, extraData = {}) {
        const $select = $(selectId);
        const data = toArray(rawData);

        $select.val(null).trigger('change.select2');
        $select.empty().append('<option value="">Please Select...</option>');

        data.forEach(item => {
            if (!item) return;

            let value = item[valueKey] || item.name || item.colr_name || item.id || item.chasis_no || item.value;
            let text  = item[textKey] || item.name || item.colr_name || item.chasis_no || item.value || item.id;

            if (!value || !text) return;

            const option = new Option(text, value, false, false);

            if (extraData.color) {
                if (item.model_code) option.dataset.code = item.model_code;
                if (item.vid) option.dataset.vid = item.vid;
                if (item.seating) option.dataset.seating = item.seating;
            }
            if (extraData.accessory && item.price) {
                option.dataset.price = item.price;
            }

            $select.append(option);
        });

        $select.prop('disabled', data.length === 0);
    }

    function updateFromColor() {
        const $color = $('#color option:selected');
        if ($color.length) {
            $('#vh_id').val($color.data('vid') || '');
            $('#seating').val($color.data('seating') || '');
        } else {
            $('#vh_id').val('');
            $('#seating').val('');
        }
    }

    function updateAccessoriesAmount() {
        let total = 0;
        $('#accessories option:selected').each(function() {
            total += parseFloat($(this).data('price')) || 0;
        });
        $('#apack_amount').val(total);
    }


    // Customer Category change (edit में category readonly है, लेकिन अगर editable हो तो)
    $('#customercat').on('change', function() { // अगर category select हो तो
        const isFirm = this.value === 'Firm'; // या readonly text से check
        $('#ownedByOption').toggle(isFirm);

        if (!isFirm && $('#care_of').val() === '5') {
            $('#care_of').val('').trigger('change');
        }

        $('#careofnamelabel').html(isFirm ? 'Owner Name <span class="required-mark">*</span>' : 'Care Of Name <span class="required-mark">*</span>');
    }).trigger('change');

    // Page load पर initial label set (क्योंकि category locked है)

    // Segment Change
    $('#segment_id').on('change', function() {
        const segmentId = $(this).val();
        populateSelect2('#model', []);
        populateSelect2('#variant', []);
        populateSelect2('#color', []);
        populateSelect2('#chassis', []);
        populateSelect2('#accessories', []);
        $('#seating').val('');
        $('#apack_amount').val(0);

        if (!segmentId) return;

        const url = '{{ route("get.models", ":segment_id") }}'.replace(':segment_id', segmentId);
        $.get(url).done(function(data) {
            populateSelect2('#model', data, 'name');
            $('#model').prop('disabled', false);
        });
    });

    // Model Change
    $('#model').on('change', function() {
        const model = encodeURIComponent($(this).val());
        populateSelect2('#variant', []);
        populateSelect2('#color', []);
        populateSelect2('#chassis', []);
        populateSelect2('#accessories', []);
        $('#seating').val('');
        $('#apack_amount').val(0);

        if (!model) return;

        const url = '{{ route("get.variants", ":model") }}'.replace(':model', model);
        $.get(url).done(function(data) {
            populateSelect2('#variant', data, 'name');
            $('#variant').prop('disabled', false);
        });
    });

    // Variant Change
    $('#variant').on('change', function() {
        const variant = encodeURIComponent($(this).val());
        populateSelect2('#color', []);
        populateSelect2('#chassis', []);
        populateSelect2('#accessories', []);
        $('#seating').val('');
        $('#apack_amount').val(0);

        if (!variant) return;

        const colorUrl = '{{ route("get.colors", ":variant") }}'.replace(':variant', variant);
        $.get(colorUrl).done(function(data) {
            populateSelect2('#color', data, 'colr_name', 'colr_name', { color: true });
            $('#color').prop('disabled', false);
            updateFromColor();
        });

        // const segmentName = $('#segment_id option:selected').text().trim();
        // const modelVal = $('#model').val();
        // const accUrl = '{{ route("get.accessories", [":segment", ":model", ":variant"]) }}'
        //     .replace(':segment', encodeURIComponent(segmentName))
        //     .replace(':model', encodeURIComponent(modelVal || ''))
        //     .replace(':variant', encodeURIComponent(variant));

        // $.get(accUrl).done(function(data) {
        //     populateSelect2('#accessories', data, 'id', 'name', { accessory: true });
        //     $('#accessories').prop('disabled', false);

        //     const savedAcc = {!! json_encode(array_filter(explode(',', trim($entry->accessories ?? '')))) !!};
        //     if (savedAcc.length > 0) {
        //         $('#accessories').val(savedAcc).trigger('change.select2');
        //     }
        //     updateAccessoriesAmount();
        // });
    });

    // Color Change
    $('#color').on('change', function() {
        const code = $(this).find(':selected').data('code');
        updateFromColor();
        populateSelect2('#chassis', []);

        if (!code) return;

        const url = '{{ route("get.chasis", ":modelCode") }}'.replace(':modelCode', encodeURIComponent(code));
        $.get(url).done(function(data) {
            populateSelect2('#chassis', data, 'id', 'chasis_no');
            $('#chassis').prop('disabled', false);
        });
    });

    $('#accessories').on('change', updateAccessoriesAmount);

    // Initial Load - Restore saved values
    // ==================== RESTORE SAVED VALUES ON EDIT (Sequential) ====================
async function restoreVehicleDetails() {
    if (!{{ $entry->segment_id ?? 'null' }}) return;

    // 1. Set Segment
    $('#segment_id').val('{{ $entry->segment_id }}').trigger('change');

    // Wait for models to load
    await new Promise(resolve => {
        const check = setInterval(() => {
            if ($('#model option').length > 1) {
                clearInterval(check);
                resolve();
            }
        }, 100);
    });

    // 2. Set Model
    $('#model').val('{{ addslashes($entry->model) }}').trigger('change');

    // Wait for variants
    await new Promise(resolve => {
        const check = setInterval(() => {
            if ($('#variant option').length > 1) {
                clearInterval(check);
                resolve();
            }
        }, 100);
    });

    // 3. Set Variant
    $('#variant').val('{{ addslashes($entry->variant) }}').trigger('change');

    // Wait for colors
    await new Promise(resolve => {
        const check = setInterval(() => {
            if ($('#color option').length > 1) {
                clearInterval(check);
                resolve();
            }
        }, 100);
    });

    // 4. Set Color + Chassis + Accessories
    $('#color').val('{{ addslashes($entry->color) }}').trigger('change');

    // Small delay for color data to attach
    setTimeout(() => {
        updateFromColor();

        if ('{{ $entry->chasis_no }}') {
            $('#chassis').val('{{ $entry->chasis_no }}').trigger('change.select2');
        }

        updateAccessoriesAmount();
    }, 800);
}

// Call it after document ready
$(document).ready(function() {
    restoreVehicleDetails();
});
});
</script>

<script>
    // Price Gap function (global so it can be called anywhere)
    function calculatePriceGap() {
        const expected = parseFloat($('#expected_price').val()) || 0;
        const offered = parseFloat($('#offered_price').val()) || 0;
        const bonus = parseFloat($('#exchange_bonus').val()) || 0;
        $('#difference').val(Math.round(expected - offered - bonus));
    }

    $(document).ready(function() {
        $('#buyer_type').on('change', function() {
            const type = this.value;
            const isAdditional = type === 'Additional Buy';
            const isExchange   = type === 'Exchange Buy';
            const isScrappage  = type === 'Scrappage';

            const baseRequired  = ['#enum_master1', '#vehicle_details'];
            const extraMake     = ['#enum_master2', '#vehicle_details2'];  // optional for Additional Buy
            const exchangeFields = ['#registration_no', '#manufacturing_year', '#odometer_reading', '#expected_price', '#offered_price', '#exchange_bonus'];
            const scrappageFields = ['#registration_no', '#manufacturing_year'];

            // 1. Disable + un-require ALL fields first (DO NOT CLEAR VALUES)
            const allFields = baseRequired.concat(extraMake, exchangeFields, scrappageFields);
            allFields.forEach(id => {
                const $f = $(id);
                if (!$f.length) return;
                $f.prop('disabled', true).prop('required', false);
                $f.closest('.form-group').find('.required-mark').hide();
            });

            // Helper: enable as REQUIRED
            function enableRequired(ids) {
                ids.forEach(id => {
                    const $f = $(id);
                    if (!$f.length) return;
                    $f.prop('disabled', false).prop('required', true);
                    $f.closest('.form-group').find('.required-mark').show();
                    if ($f.hasClass('select2-hidden-accessible') || $f.hasClass('select2')) {
                        $f.trigger('change.select2');
                    }
                });
            }

            // Helper: enable as OPTIONAL (enabled but NOT required)
            function enableOptional(ids) {
                ids.forEach(id => {
                    const $f = $(id);
                    if (!$f.length) return;
                    $f.prop('disabled', false).prop('required', false);
                    $f.closest('.form-group').find('.required-mark').hide();
                    if ($f.hasClass('select2-hidden-accessible') || $f.hasClass('select2')) {
                        $f.trigger('change.select2');
                    }
                });
            }

            // 2. Apply correct state per type
            if (isAdditional) {
                enableRequired(baseRequired);
                enableOptional(extraMake);   // Brand Make 2 & Variant 2 → optional
            } else if (isExchange) {
                enableRequired(baseRequired.concat(exchangeFields));
                calculatePriceGap();
            } else if (isScrappage) {
                enableRequired(baseRequired.concat(scrappageFields));
            }
            // First time Buyer → everything stays disabled (already reset above)

            calculatePriceGap();
        });

        // Trigger once on load — values are preserved
        $('#buyer_type').trigger('change');

        // Re-calculate price gap immediately
        calculatePriceGap();

        // Live update when user types
        $('#expected_price, #offered_price, #exchange_bonus').on('input', calculatePriceGap);

        // ─── Referred By checkbox toggle ─────────────────────────────────────
        const $refFields = $('#ref_customer_name, #ref_mobile_no, #ref_existing_model, #ref_variant, #ref_chassis_reg_no');

        $('#referred_by_checkbox').on('change', function() {
            const isChecked = this.checked;

            if (isChecked) {
                // Show section, make fields required
                $('#referred_by_fields').show();
                $refFields.prop('required', true);
                $('#referred_by_fields .required-mark').show();
            } else {
                // Hide section, clear values, remove required
                $('#referred_by_fields').hide();
                $refFields.val('').prop('required', false);
                $('#referred_by_fields .required-mark').hide();
            }
        });
        // ─────────────────────────────────────────────────────────────────────
    });
</script>
<script>
    $(document).ready(function() {

    // Booking Mode → Online Ref No toggle
    $('#booking_mode').on('change', function() {
        const isOnline = this.value === 'Online';
        $('#refrence_no_group').toggle(isOnline);
        $('#refrence_no').prop('disabled', !isOnline).prop('required', isOnline);
        if (!isOnline) $('#refrence_no').val('');
    }).trigger('change');

    // Booking Source → DSA toggle
    $('#booking_source').on('change', function() {
        const isDSA = this.value === 'DSA';
        $('#dsa_details_group').toggle(isDSA);
        $('#dsa_details').prop('disabled', !isDSA).prop('required', isDSA);
        if (!isDSA) $('#dsa_details').val('').trigger('change');
    }).trigger('change');


    $('#fin_mode').on('change', function() {
        const isInHouse = this.value === 'In-house';

        // Toggle all three boxes
        $('#financier_box, #loan_status_box, #financier_short_name_box').toggle(isInHouse);

        // Enable/disable fields
        $('#financier, #loan_status').prop('disabled', !isInHouse).prop('required', isInHouse);

        // Toggle required marks
        $('#financier_box .required-mark, #loan_status_box .required-mark').toggle(isInHouse);

        if (!isInHouse) {
            // Clear all when not In-house
            $('#financier').val('').trigger('change');
            $('#loan_status').val('').trigger('change');
            $('#financier_short_name').val('');
        } else {
            // When switching to In-house, update short name if financier selected
            const selectedOption = $('#financier option:selected');
            if (selectedOption.length && selectedOption.val()) {
                $('#financier_short_name').val(selectedOption.data('short_name') || '');
            }
        }
    }).trigger('change');

    // Financier change → Short Name
    $('#financier').on('change', function() {
        const shortName = $(this).find(':selected').data('short_name') || '';
        $('#financier_short_name').val(shortName);
    }).trigger('change');

    // Flatpickr for delivery date
    flatpickr('#expected_del_date', {
        dateFormat: "d-M-Y",
        allowInput: true,
        onChange: function(selectedDates, dateStr, instance) {
            if (selectedDates[0]) {
                $('#hidden_expected_del_date').val(selectedDates[0].toISOString().slice(0,10));
            }
        }
    });

});
</script>
<script>
    $(document).ready(function() {

    // Uppercase inputs (exclude remarks)
    $('input[type="text"].uppercase').on('input', function() {
        const start = this.selectionStart;
        const end = this.selectionEnd;
        this.value = this.value.toUpperCase();
        this.setSelectionRange(start, end);
    });

    // Masks Apply करो
    $('#pan_no').mask('AAAAA0000A', { placeholder: 'ABCDE1234F' });
    $('#adhar_no').mask('0000-0000-0000', { placeholder: '1234-5678-9012' });
    $('#gstn').mask('00AAAAA0000A0ZS', {
        placeholder: '27ABCDE1234F1Z5',
        clearIfNotMatch: false
    });

    // Custom Validation Methods (same as add form)
    $.validator.addMethod('panFormat', function(value, element) {
        return this.optional(element) || /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/.test(value);
    }, 'Please enter a valid PAN number e.g., ABCDE1234F');

    $.validator.addMethod('udaiFormat', function(value, element) {
        return this.optional(element) || /^\d{4}-\d{4}-\d{4}$/.test(value);
    }, 'Please enter a valid Aadhar No. e.g., 1234-5678-9012');

    $.validator.addMethod('gstnFormat', function(value, element) {
        return this.optional(element) || /^\d{2}[A-Z]{5}\d{4}[A-Z]{1}\d[A-Z0-9]{1}Z[A-Z0-9]$/.test(value);
    }, 'Please enter a valid GSTIN e.g., 27ABCDE1234F1Z5');

    // Age calculation function
    function calculateAge(dobDate) {
        const today = new Date();
        let age = today.getFullYear() - dobDate.getFullYear();
        const m = today.getMonth() - dobDate.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < dobDate.getDate())) {
            age--;
        }
        return age;
    }

    // Flatpickr for DOB
    flatpickr('#customer_dob', {
        dateFormat: "d-M-Y",
        allowInput: true,
        maxDate: "today",
        onChange: function(selectedDates, dateStr, instance) {
            if (selectedDates[0]) {
                const age = calculateAge(selectedDates[0]);
                $('#customer_age').val(age);
                $('#hidden_customer_dob').val(instance.formatDate(selectedDates[0], 'Y-m-d'));

                if (age < 18) {
                    alert('Customer age cannot be below 18 years.');
                    instance.clear();
                    $('#customer_age').val('');
                    $('#hidden_customer_dob').val('');
                }
            }
        }
    });

    // Initial age set on page load
    @if($entry->c_dob)
        const initialDob = moment('{{ \Carbon\Carbon::parse($entry->c_dob)->format('d-M-Y') }}', 'DD-MMM-YYYY');
        $('#customer_age').val(calculateAge(initialDob.toDate()));
    @endif

    // Form Validation
    $('#bookingForm').validate({
        rules: {
            name: { required: true },
            care_of: { required: true },
            mobile: { required: true, digits: true, minlength: 10, maxlength: 10 },
            gender: { required: true },
            occupation: { required: true },
            pan_no: { panFormat: true },
            adhar_no: { udaiFormat: true },
            gstn: {
                required: function() { return !$('#gst_unregistered').is(':checked'); },
                gstnFormat: true
            },
            customer_dob: { required: true },
            branch: { required: true },
            location_id: { required: true },
            'details': { required: true },
            // Add other required fields as needed...
        },
        messages: {
            name: 'Please enter customer name',
            mobile: 'Please enter a valid 10-digit mobile number',
            pan_no: 'Please enter a valid PAN (e.g., ABCDE1234F)',
            adhar_no: 'Please enter a valid Aadhar (e.g., 1234-5678-9012)',
            gstn: 'Please enter a valid GSTIN (e.g., 27ABCDE1234F1Z5)',
            customer_dob: 'Please select customer date of birth',
        },
        errorElement: 'span',
        errorClass: 'text-danger',
        highlight: function(element) {
            $(element).addClass('is-invalid');
        },
        unhighlight: function(element) {
            $(element).removeClass('is-invalid');
        }
    });

});
$(document).ready(function() {

    const isFirm = '{{ $entry->b_cat }}' === 'Firm';

    if (isFirm) {
        // Remove all non-Owned By options
        $('#care_of option[value=""], #care_of option[value="1"], #care_of option[value="2"], #care_of option[value="3"], #care_of option[value="4"]').remove();

        // Ensure Owned By is present and selected
        if ($('#care_of option[value="5"]').length === 0) {
            $('#care_of').append('<option value="5" selected>Owned By</option>');
        } else {
            $('#care_of').val('5').trigger('change');
        }

        // Update label to Owner Name
        $('#careofnamelabel').html('Owner Name <span class="required-mark">*</span>');
    } else {
        // Remove Owned By option
        $('#care_of option[value="5"]').remove();

        // If it was selected, clear to default
        if ($('#care_of').val() === '5') {
            $('#care_of').val('').trigger('change');
        }

        // Update label to Care Of Name
        $('#careofnamelabel').html('Care Of Name <span class="required-mark">*</span>');
    }

    // Re-init Select2 after option manipulation
    $('#care_of').select2({
        theme: 'bootstrap-5' // Adjust if your theme is different
    });
});
</script>
@endsection