@extends(backpack_view('blank'))
{{--
@section('header')
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2-bootstrap-theme/0.1.0-beta.10/select2-bootstrap.min.css"
    rel="stylesheet" />
@endsection --}}
@section('header')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
@endsection



@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-end">
            <div class="col-lg-6">
                <div class="page-header-title">
                    <i class="ik ik-car bg-blue"></i>
                    <h1 class="fw-bold mb-0">Add New Booking</h1>
                </div>
            </div>
            <div class="col-lg-6 text-end">
                <nav class="breadcrumb-container" aria-label="breadcrumb">
                    <ol class="breadcrumb justify-content-end">
                        <li class="breadcrumb-item">
                            <a href="{{ backpack_url('dashboard') }}">
                                <i class="ik ik-home"></i> Home
                            </a>
                        </li>
                        <li class="breadcrumb-item active">Add New Booking</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="row">
        @include(backpack_view('inc.alerts'))

        <div class="col-md-12">
            <!-- Booking Form -->
            <form id="bookingForm" class="forms-sample" method="POST" action="{{ backpack_url('booking') }}"
                enctype="multipart/form-data">
                @csrf

                <!-- Payment Details Section -->
                <div class="card p-3">
                    <div class="card-body">
                        <h2 class="mb-3">Payment Details</h2>
                        <div class="row">
                            <div class="col-sm-2">
                                <div class="form-group">
                                    <label for="customertype">Customer Type <span class="required-mark">*</span></label>
                                    <select name="customertype" id="customertype" class="form-control form-select"
                                        required>
                                        <option value="Actual" selected>Actual</option>
                                        <option value="Dummy">Dummy</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-sm-2">
                                <div class="form-group">
                                    <label for="customercat">Customer Category <span
                                            class="required-mark">*</span></label>
                                    <select name="customercat" id="customercat" class="form-control form-select"
                                        required>
                                        <option value="Individual" selected>Individual</option>
                                        <option value="CSD">CSD</option>
                                        <option value="Firm">Firm</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-sm-2">
                                <div class="form-group">
                                    <label for="bookingdate">Booking Date <span class="required-mark">*</span></label>
                                    <input type="text" name="bookingdate" id="bookingdate"
                                        class="form-control flatpickr" placeholder="dd-mmm-yyyy" required>
                                    <input type="hidden" name="hiddenbookingdate" id="hiddenbookingdate">
                                </div>
                            </div>

                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="coltype">Collection Type <span class="required-mark">*</span></label>
                                    <select name="coltype" id="coltype" class="form-control form-select" required>
                                        <option value="" disabled selected>-- Select Collection Type --</option>
                                        <option value="1">Receipt</option>
                                        <option value="2">Field Collection By Sales Team</option>
                                        <option value="3">Field Collection By DSA</option>
                                        <option value="4">Used Car Purchase</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-12 col-md-6 col-lg-3">
                                <div class="form-group">
                                    <label for="user">
                                        Collected By <span class="required-mark" style="display:none">*</span>
                                    </label>

                                    <select name="user" id="user" class="form-control select2 w-100" disabled>
                                        <option value="">Please Select...</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="bookingamount">Booking Amount <span
                                            class="required-mark">*</span></label>
                                    <input type="text" name="bookingamount" id="bookingamount" class="form-control"
                                        required>
                                </div>
                            </div>

                            <!-- Consolidated Receipt/Voucher Field -->
                            <div class="col-sm-3">
                                <div class="form-group" id="receiptvouchergroup">
                                    <label id="receiptvoucherlabel">Receipt No. <span
                                            class="required-mark">*</span></label>
                                    <input type="text" name="receiptvoucherno" id="receiptvoucherinput"
                                        class="form-control" required placeholder="12345">
                                    <div id="receiptvoucherwarning" class="text-danger" style="display: none;">Number
                                        already exists</div>
                                </div>
                            </div>

                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="receiptdate">Receipt Date <span class="required-mark">*</span></label>
                                    <input type="text" name="receiptdate" id="receiptdate"
                                        class="form-control flatpickr" placeholder="dd-mmm-yyyy" required>
                                    <input type="hidden" name="hiddenreceiptdate" id="hiddenreceiptdate">
                                </div>
                            </div>


                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="fdoc">Upload Image or PDF <span class="required-mark">*</span></label>
                                    <input type="file" name="amountproof" id="proofInput" class="form-control"
                                        accept=".pdf,.jpg,.jpeg,.png" required>

                                    <!-- Chip Preview -->
                                    <div id="proofPreview" class="mt-3"></div>
                                </div>


                            </div>
                        </div>
                    </div>
                </div>

                <div class="card p-3 mt-3">
                    <div class="card-body">
                        <h2 class="mb-3">Customer Details</h2>
                        <div class="row">
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label id="customernamelabel" for="name">Customer Name <span
                                            class="required-mark">*</span></label>
                                    <input type="text" name="name" id="name" class="form-control" required>
                                </div>
                            </div>

                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="careof">Care Of <span class="required-mark">*</span></label>
                                    <select name="careof" id="careof" class="form-control select2" required>
                                        <option value="">Please Select...</option>
                                        <option value="1">Son of</option>
                                        <option value="2">Daughter of</option>
                                        <option value="3">Married to</option>
                                        <option value="4">Guardian Name</option>
                                        <option value="5" id="ownedByOption" style="display: none;">Owned By
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label id="careofnamelabel">Care Of Name <span
                                            class="required-mark">*</span></label>
                                    <input type="text" name="careofname" id="careofname" class="form-control uppercase"
                                        required>
                                </div>
                            </div>

                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="mobile">Contact No. <span class="required-mark">*</span></label>
                                    <input type="text" name="mobile" id="mobile" class="form-control" required>
                                </div>
                            </div>

                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label for="altmobile">Alternate Contact No.</label>
                                    <input type="text" name="altmobile" id="altmobile" class="form-control">
                                </div>
                            </div>

                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label for="gender">Gender <span class="required-mark">*</span></label>
                                    <select name="gender" id="gender" class="form-control form-select" required>
                                        <option value="Male" selected>Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Transgender">Transgender</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label for="occupation">Occupation <span class="required-mark">*</span></label>
                                    <select name="occupation" id="occupation" class="form-control form-select" required>
                                        <option value="" disabled selected>-- Select Occupation --</option>
                                        <option value="Agriculture">Agriculture</option>
                                        <option value="Business">Business</option>
                                        <option value="Salaried (Govt.)">Salaried (Govt.)</option>
                                        <option value="Salaried (Pvt.)">Salaried (Pvt.)</option>
                                        <option value="Self Employed (Professional)">Self Employed (Professional)
                                        </option>
                                        <option value="Pensioner">Pensioner</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="panno">PAN Card No.</label>
                                    <input type="text" name="panno" id="panno" class="form-control">
                                </div>
                            </div>

                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="adharno">Aadhar No.</label>
                                    <input type="text" name="adharno" id="adharno" class="form-control">
                                </div>
                            </div>

                            <div class="col-sm-2">
                                <div class="form-group" id="gstn-group">
                                    <label for="gstn">GSTN <span class="required-mark"
                                            style="display: none;">*</span></label>
                                    <input type="text" name="gstn" id="gstn" class="form-control"
                                        placeholder="Enter GSTN No." disabled>
                                    <div class="form-check mt-2">
                                        <input type="checkbox" id="notrequiredgst" name="notrequiredgst"
                                            class="form-check-input" checked>
                                        <label for="notrequiredgst" class="form-check-label">GST
                                            Unregistered</label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-2">
                                <div class="form-group" id="dob-group">
                                    <label for="customerdob">Customer D.O.B. <span
                                            class="required-mark">*</span></label>
                                    <input type="text" name="customerdob" id="customerdob"
                                        class="form-control flatpickr" placeholder="dd-mmm-yyyy" required>
                                    <input type="hidden" name="hiddencustomerdob" id="hiddencustomerdob">
                                </div>
                            </div>

                            <div class="col-sm-2">
                                <div class="form-group" id="age-group">
                                    <label for="customerage">Customer Age</label>
                                    <input type="text" name="customerage" id="customerage" class="form-control"
                                        readonly>
                                </div>
                            </div>

                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label for="branch">Branch <span class="required-mark">*</span></label>
                                    <select name="branch" id="branch" class="form-control form-select" required>
                                        <option value="" disabled selected>-- Select Branch --</option>
                                        @foreach($data['branches'] ?? [] as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label for="location">Location <span class="required-mark">*</span></label>
                                    <select name="location" id="location" class="form-control form-select" required
                                        disabled>
                                        <option value="" disabled selected>-- Select Location --</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-sm-4">
                                <div class="form-group" id="othloc">
                                    <label for="locationother">Other Location <span class="required-mark"
                                            style="display: none;">*</span></label>
                                    <input type="text" name="locationother" id="locationother" class="form-control"
                                        disabled>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card p-3 mt-3">
                    <div class="card-body">
                        <h2 class="mb-3">Referred By Details</h2>
                        <div class="row">
                            <div class="col-sm-1">
                                <div class="form-group">
                                    <label><input type="checkbox" id="referredby" name="referredby"> Referred
                                        By</label>
                                </div>
                            </div>

                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="refcustomername">Customer Name <span class="required-mark"
                                            style="display: none;">*</span></label>
                                    <input type="text" name="refcustomername" id="refcustomername" class="form-control"
                                        disabled>
                                </div>
                            </div>

                            <div class="col-sm-2">
                                <div class="form-group">
                                    <label for="refmobileno">Mobile No. <span class="required-mark"
                                            style="display: none;">*</span></label>
                                    <input type="text" name="refmobileno" id="refmobileno" class="form-control"
                                        disabled>
                                </div>
                            </div>

                            <div class="col-sm-2">
                                <div class="form-group">
                                    <label for="refexistingmodel">Existing Model <span class="required-mark"
                                            style="display: none;">*</span></label>
                                    <input type="text" name="refexistingmodel" id="refexistingmodel"
                                        class="form-control" disabled>
                                </div>
                            </div>

                            <div class="col-sm-2">
                                <div class="form-group">
                                    <label for="refvariant">Variant <span class="required-mark"
                                            style="display: none;">*</span></label>
                                    <input type="text" name="refvariant" id="refvariant" class="form-control" disabled>
                                </div>
                            </div>

                            <div class="col-sm-2">
                                <div class="form-group">
                                    <label for="refchassisregno">Chassis No. / Regn. No. <span class="required-mark"
                                            style="display: none;">*</span></label>
                                    <input type="text" name="refchassisregno" id="refchassisregno" class="form-control"
                                        disabled>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card p-3 mt-3">
                    <div class="card-body">
                        <h2 class="mb-3">Purchase Type Details</h2>
                        <div class="row">
                            <div class="col-sm-2">
                                <div class="form-group">
                                    <label for="buyertype">Purchase Type <span class="required-mark">*</span></label>
                                    <select name="buyertype" id="buyertype" class="form-control form-select" required>
                                        <option value="" disabled>-- Select Purchase Type --</option>
                                        <option value="First time Buyer" selected>First time Buyer</option>
                                        <option value="Additional Buy">Additional Buy</option>
                                        <option value="Exchange Buy">Exchange Buy</option>
                                        <option value="Scrappage">Scrappage</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-sm-2">
                                <div class="form-group">
                                    <label for="enummaster1">Brand Make 1 <span class="required-mark"
                                            style="display: none;">*</span></label>
                                    <select name="enummaster1" id="enummaster1" class="form-control form-select"
                                        disabled>
                                        <option value="" disabled selected>-- Select Brand Make 1 --</option>
                                        @foreach($data['enum_master'] ?? [] as $enum)
                                        <option value="{{ $enum->id }}">{{ $enum->value }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="vehicledetails">Model Variant 1 <span class="required-mark"
                                            style="display: none;">*</span></label>
                                    <input type="text" name="vehicledetails" id="vehicledetails" class="form-control"
                                        disabled>
                                </div>
                            </div>

                            <div class="col-sm-2">
                                <div class="form-group">
                                    <label for="enummaster2">Brand Make 2</label>
                                    <select name="enummaster2" id="enummaster2" class="form-control form-select"
                                        disabled>
                                        <option value="" disabled selected>-- Select Brand Make 2 --</option>
                                        @foreach($data['enum_master'] ?? [] as $enum)
                                        <option value="{{ $enum->id }}">{{ $enum->value }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="vehicledetails2">Model Variant 2</label>
                                    <input type="text" name="vehicledetails2" id="vehicledetails2" class="form-control"
                                        disabled>
                                </div>
                            </div>

                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label for="registrationno">Vehicle Registration No. <span class="required-mark"
                                            style="display: none;">*</span></label>
                                    <input type="text" name="registrationno" id="registrationno" class="form-control"
                                        disabled>
                                </div>
                            </div>

                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label for="manufacturingyear">Vehicle Manufacturing Year <span
                                            class="required-mark" style="display: none;">*</span></label>
                                    <input type="number" name="manufacturingyear" id="manufacturingyear"
                                        class="form-control" disabled>
                                </div>
                            </div>

                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label for="odometerreading">Vehicle Odometer Reading <span class="required-mark"
                                            style="display: none;">*</span></label>
                                    <input type="text" name="odometerreading" id="odometerreading" class="form-control"
                                        disabled>
                                </div>
                            </div>

                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="expectedprice">Used Vehicle Expected Price <span class="required-mark"
                                            style="display: none;">*</span></label>
                                    <input type="number" name="expectedprice" id="expectedprice" class="form-control"
                                        disabled>
                                </div>
                            </div>

                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="offeredprice">Used Vehicle Offered Price <span class="required-mark"
                                            style="display: none;">*</span></label>
                                    <input type="number" name="offeredprice" id="offeredprice" class="form-control"
                                        disabled>
                                </div>
                            </div>

                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="exchangebonus">New Vehicle Exchange Bonus <span class="required-mark"
                                            style="display: none;">*</span></label>
                                    <input type="number" name="exchangebonus" id="exchangebonus" class="form-control"
                                        disabled>
                                </div>
                            </div>

                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="difference">Price Gap</label>
                                    <input type="text" name="difference" id="difference" class="form-control" disabled>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Vehicle Details Section -->
                <div class="card p-3 mt-3">
                    <div class="card-body">
                        <h2 class="mb-3">Vehicle Details</h2>
                        <div class="row">
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="segmentid">Segment <span class="required-mark">*</span></label>
                                    <select name="segmentid" id="segmentid" class="form-control select2" required>
                                        <option value="0">Please Select...</option>
                                        @foreach($data['segments'] ?? [] as $segment)
                                        <option value="{{ $segment->id }}">{{ $segment->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="model">Model <span class="required-mark">*</span></label>
                                    <select name="model" id="model" class="form-control select2" required disabled>
                                        <option value="0">Please Select...</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="variant">Variant <span class="required-mark">*</span></label>
                                    <select name="variant" id="variant" class="form-control select2" required disabled>
                                        <option value="0">Please Select...</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="color">Color <span class="required-mark">*</span></label>
                                    <select name="color" id="color" class="form-control select2" required disabled>
                                        <option value="0">Please Select...</option>
                                    </select>
                                    <input type="hidden" id="vhid" name="vhid">
                                </div>
                            </div>

                            <div class="col-sm-2">
                                <div class="form-group">
                                    <label for="seating">Seating<span class="required-mark">*</span></label>
                                    <input type="text" name="seating" id="seating" class="form-control" value="0"
                                        readonly>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="accessories">Select Accessories</label>
                                    <select name="accessories" id="accessories" class="form-control select2" multiple
                                        disabled></select>
                                </div>
                            </div>

                            <div class="col-sm-2">
                                <div class="form-group">
                                    <label for="apackamount">Accessories Amount</label>
                                    <input type="text" name="apackamount" id="apackamount" class="form-control"
                                        value="0" readonly>
                                </div>
                            </div>

                            <div class="col-sm-2">
                                <div class="form-group">
                                    <label for="chassis">Allotted Chassis No.</label>
                                    <select name="chassis" id="chassis" class="form-control select2" disabled>
                                        <option value="0">Please Select...</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Booking Details Section -->
                <div class="card p-3 mt-3">
                    <div class="card-body">
                        <h2 class="mb-3">Booking Type & Source</h2>
                        <div class="row">
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="bookingmode">Booking Mode <span class="required-mark">*</span></label>
                                    <select name="bookingmode" id="bookingmode" class="form-control form-select"
                                        required>
                                        <option value="Dealer" selected>Dealer</option>
                                        <option value="Online">Online</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-sm-2">
                                <div class="form-group">
                                    <label for="refrenceno">Online Book Ref No. <span class="required-mark"
                                            style="display: none;">*</span></label>
                                    <input type="text" name="refrenceno" id="refrenceno" class="form-control" disabled>
                                </div>
                            </div>

                            <div class="col-sm-2">
                                <div class="form-group">
                                    <label for="bookingsource">Booking Source <span
                                            class="required-mark">*</span></label>
                                    <select name="bookingsource" id="bookingsource" class="form-control form-select"
                                        required>
                                        <option value="Dealer" selected>Dealer Sourcing</option>
                                        <option value="DSA">DSA</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="dsadetails">Select DSA <span class="required-mark"
                                            style="display: none;">*</span></label>
                                    <select name="dsadetails" id="dsadetails" class="form-control form-select" disabled>
                                        <option value="" disabled selected>-- Select DSA --</option>
                                        @foreach($data['dsa_details'] ?? [] as $dsa)
                                        <option value="{{ $dsa->id }}">{{ $dsa->name }} - {{ $dsa->mobile }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-sm-2">
                                <div class="form-group">
                                    <label for="saleconsultant">Sales Consultant <span
                                            class="required-mark">*</span></label>
                                    <select name="saleconsultant" id="saleconsultant" class="form-control select2"
                                        required>
                                        <option value="">Please Select...</option>
                                        @foreach($data['allusers'] ?? [] as $consultant)
                                        <option value="{{ $consultant->id }}">{{ $consultant->name }} - {{
                                            $consultant->emp_code ?? '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-sm-2">
                                <div class="form-group">
                                    <label>Delivery Date Type <span class="required-mark">*</span></label>
                                    <div>
                                        <label><input type="radio" name="deliverytype" value="Expected" checked>
                                            Expected</label>&nbsp;&nbsp;&nbsp;
                                        <label><input type="radio" name="deliverytype" value="Confirmed">
                                            Confirmed</label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-2">
                                <div class="form-group">
                                    <label for="expecteddeldate">Delivery Date <span
                                            class="required-mark">*</span></label>
                                    <input type="text" name="expecteddeldate" id="expecteddeldate"
                                        class="form-control flatpickr" placeholder="dd-mmm-yyyy" required>
                                    <input type="hidden" name="hiddenexpecteddeldate" id="hiddenexpecteddeldate">
                                </div>
                            </div>

                            <div class="col-sm-2">
                                <div class="form-group">
                                    <label for="finmode">Finance Mode <span class="required-mark">*</span></label>
                                    <select name="finmode" id="finmode" class="form-control form-select" required>
                                        <option value="" disabled selected>-- Select Finance Mode --</option>
                                        <option value="In-house">In-house</option>
                                        <option value="Customer Self">Customer Self</option>
                                        <option value="Cash">Cash</option>
                                        <option value="Yet To Decide">Yet To Decide</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-sm-3">
                                <div class="form-group" id="financierbox">
                                    <label for="financier">Financier <span class="required-mark"
                                            style="display: none;">*</span></label>
                                    <select name="financier" id="financier" class="form-control select2">
                                        <option value="">Select Financier</option>
                                        @foreach($data['financiers'] ?? [] as $financier)
                                        <option value="{{ $financier->id }}"
                                            data-shortname="{{ $financier->short_name ?? '' }}">
                                            {{ $financier->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="financiershortname">Financier Short Name</label>
                                    <input type="text" name="financiershortname" id="financiershortname"
                                        class="form-control" readonly>
                                </div>
                            </div>

                            <div class="col-sm-3">
                                <div class="form-group" id="loanstatusbox">
                                    <label for="loanstatus">Loan File Status <span class="required-mark"
                                            style="display: none;">*</span></label>
                                    <select name="loanstatus" id="loanstatus" class="form-control form-select" disabled
                                        required>
                                        <option value="" disabled selected>-- Select Loan File Status --</option>
                                        <option value="Pending">Pending</option>
                                        <option value="Complete">Complete</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-9">
                                <div class="form-group">
                                    <label for="details">Remarks</label>
                                    <textarea name="details" id="details" class="form-control" rows="4"
                                        placeholder="Enter any additional remarks..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


        </div>
        <!-- Remarks Section inside same card -->




        <!-- Submit Button - Center & Better -->
        <div class="row mt-4">
            <div class="col-12 text-center">
                <button type="submit" id="submitBtn"
                    class="btn btn-success btn-lg px-5 py-3 shadow-lg fw-bold text-uppercase">
                    <i class="ik ik-plus mr-2"></i> Add Booking
                </button>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Proof Preview Modal -->
<div class="modal fade" id="proofModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="proofModalFileName"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body text-center">
                <iframe id="proofModalPreview" style="width:100%; height:500px;" frameborder="0"></iframe>
            </div>

            <div class="modal-footer">
                <a id="proofModalDownload" class="btn btn-success" download>Download</a>

                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
</form>
</div>

</div>
</div>

<!-- Error Modal -->
<div class="modal fade" id="errorModal" tabindex="-1" role="dialog" aria-labelledby="errorModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="errorModalLabel">Form Errors</h2>
                {{-- <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button> --}}
            </div>
            <div class="modal-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('after_scripts')

{{--
<link rel="stylesheet" href="{{ asset('plugins/select2/dist/css/select2.min.css') }}"> --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    /* Your existing styles + small improvement for required mark */
    .required-mark {
        color: #dc3545;
        margin-left: 2px;
    }

    /* Prevent required mark from flashing/hiding */
    label .required-mark {
        display: inline !important;
    }

    /* Numeric input styling */
    input.numeric-only {
        -moz-appearance: textfield;
    }
    input.numeric-only::-webkit-outer-spin-button,
    input.numeric-only::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
</style>
<style>
    .proof-chip {
        display: inline-flex;
        align-items: center;
        background-color: #f1f3f5;
        border: 1px solid #ced4da;
        border-radius: 50px;
        padding: 6px 14px;
        margin-right: 12px;
        font-size: 0.95rem;
        max-width: 320px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    }

    .proof-chip i {
        font-size: 1.4rem;
        margin-right: 10px;
        color: #6c757d;
    }

    .proof-chip .file-name {
        max-width: 160px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-right: 12px;
    }

    .proof-chip .btn-action {
        background: none;
        border: none;
        font-size: 1.2rem;
        padding: 0 6px;
        cursor: pointer;
        color: #6c757d;
    }

    .proof-chip .btn-download:hover {
        color: #0d6efd;
    }

    .proof-chip .btn-remove:hover {
        color: #dc3545;
    }

    .is-valid {
        border-color: #28a745 !important;
        box-shadow: 0 0 5px rgba(40, 167, 69, 0.5);
    }

    .is-invalid {
        border-color: #dc3545 !important;
        box-shadow: 0 0 5px rgba(220, 53, 69, 0.5);
    }



    .proof-chip {
        transition: all 0.2s ease;
        user-select: none;
    }

    .proof-chip:hover {
        background-color: #e9ecef;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
    }

    .proof-chip .btn-action {
        transition: color 0.2s;
    }

    #modalProofPdf,
    #modalProofImg {
        max-height: 100vh;
        object-fit: contain;
    }

    /* Select2 के अंदर का default arrow पूरी तरह हटाओ */
    .select2-container--bootstrap5 .select2-selection--single .select2-selection__arrow,
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        display: none !important;
    }

    /* Select2 के selection area को .form-select जैसा look दो + bootstrap का arrow background लगाओ */
    .select2-container--bootstrap5 .select2-selection--single,
    .select2-container .select2-selection--single {
        /* Bootstrap 5 form-select जैसा base look */
        height: calc(1.5em + 0.75rem + 2px) !important;
        /* default height match */
        padding: 0.375rem 2.25rem 0.375rem 0.75rem !important;
        /* right padding for arrow */
        font-size: 1rem;
        font-weight: 400;
        line-height: 1.5;
        color: #212529;
        background-color: #fff;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e") !important;
        background-repeat: no-repeat !important;
        background-position: right 0.75rem center !important;
        background-size: 16px 12px !important;
        /* arrow size adjust कर सकते हो */
        border: 1px solid #ced4da !important;
        border-radius: 0.375rem !important;
        transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
    }

    /* Focus / active state – bootstrap जैसा blue glow */
    .select2-container--bootstrap5.select2-container--focus .select2-selection--single,
    .select2-container.select2-container--focus .select2-selection--single {
        border-color: #86b7fe !important;
        outline: 0;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, .25) !important;
    }

    /* Hover state */
    .select2-container--bootstrap5 .select2-selection--single:hover,
    .select2-container .select2-selection--single:hover {
        border-color: #a8d0ff !important;
    }

    /* जब disabled हो तो भी arrow सही दिखे */
    .select2-container--bootstrap5 .select2-selection--single[aria-disabled=true],
    .select2-container .select2-selection--single[aria-disabled=true] {
        background-color: #e9ecef !important;
        opacity: 1;
    }

    .page-header {
        display: block;
    }
</style>

{{-- <script src="{{ asset('plugins/select2/dist/js/select2.min.js') }}"></script> --}}
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    let uploadedFile = null; // global variable to hold the file for download

    function handleProof(input) {

    const previewDiv = document.getElementById('proofPreview');
    previewDiv.innerHTML = '';

    if (input.files && input.files[0]) {

        const file = input.files[0];

        // file size validation
        if (file.size > 2 * 1024 * 1024) {
            Swal.fire({
                icon: 'warning',
                title: 'File Too Large',
                text: 'Maximum allowed file size is 2MB.'
            });

            input.value = '';
            return;
        }

        const fileURL = URL.createObjectURL(file);

        previewDiv.innerHTML = `
            <span class="btn btn-outline-primary btn-sm
                  d-inline-flex align-items-center gap-2 px-3 py-2"
                  style="cursor:pointer"
                  onclick="openProofModal('${fileURL}','${file.name.replace(/'/g,"\\'")}')">

                <i class="la la-paperclip"></i>
                <span class="fw-medium small">${file.name}</span>

            </span>
        `;
    }
}
    function openProofModal(url, name) {

    document.getElementById('proofModalFileName').innerText = name;
    document.getElementById('proofModalDownload').href = url;
    document.getElementById('proofModalPreview').src = url;

    $('#proofModal').modal('show');
}
document.getElementById('proofInput')?.addEventListener('change', function() {
    handleProof(this);
});

$('#proofModal').on('show.bs.modal', function () {
    $(this).appendTo('body');
});



(function() {
    'use strict';

    // Main function to initialize the booking form
    function initBookingForm() {
        initSelect2();
        initFlatpickr();
        initMasks();
        initValidation();
        setInitialState();
        bindEventListeners();
        initUppercaseInputs();
        initNumericOnlyFields();

        // Customer Category change → Care Of toggle
        $('#customercat').on('change', function() {
            const isFirm = this.value === 'Firm';
            $('#ownedByOption').toggle(isFirm);

            // अगर Firm नहीं तो Owned By select नहीं होना चाहिए
            if (!isFirm && $('#careof').val() === '5') {
                $('#careof').val('').trigger('change');
            }

            // Label change (सिर्फ display name)
            $('#careofnamelabel').html(isFirm ? 'Owner Name <span class="required-mark">*</span>' : 'Care Of Name <span class="required-mark">*</span>');
        }).trigger('change'); // Initial check

    }

    // Initialize Select2 plugin for enhanced select elements
    function initSelect2() {
        $('#segmentid, #model, #variant, #color, #saleconsultant, #user, #financier, #accessories, #chassis').select2();
    }

    // Initialize Flatpickr date pickers
    function initFlatpickr() {
    // 1. Customer DOB
    flatpickr('#customerdob', {
            dateFormat: 'd-M-Y',
            maxDate: 'today',
            allowInput: false,
            onChange: function(selectedDates, dateStr, instance) {
                const dob = selectedDates[0];
                if (dob) {
                    const age = calculateAge(dob);
                    $('#customerage').val(age);
                    $('#hiddencustomerdob').val(instance.formatDate(dob, 'Y-m-d'));

                    if (age < 18) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Age Restriction',
                            text: `Customer age is ${age} years, which is below 18. Please select a valid date.`,
                            confirmButtonColor: '#3085d6'
                        });
                        instance.clear();
                        $('#customerage').val('');
                        $('#hiddencustomerdob').val('');
                    }
                }
            }
        });

    // 2. Booking Date
    const bookingPicker = flatpickr('#bookingdate', {
        dateFormat: 'd-M-Y',
        maxDate: 'today',
        allowInput: false,
        onChange: function(selectedDates, dateStr, instance) {
            const bookingDate = selectedDates[0];
            $('#hiddenbookingdate').val(instance.formatDate(bookingDate, 'Y-m-d'));

            // Update delivery date minDate
            if (bookingDate && deliveryPicker) {
                deliveryPicker.set('minDate', bookingDate);
                // Agar delivery date pehle ki hai to clear kar do
                if (deliveryPicker.selectedDates[0] && deliveryPicker.selectedDates[0] < bookingDate) {
                    deliveryPicker.clear();
                    $('#hiddenexpecteddeldate').val('');
                    alert('Delivery date cannot be earlier than booking date.');
                }
            }
        }
    });

    // 3. Expected Delivery Date
    const deliveryPicker = flatpickr('#expecteddeldate', {
        dateFormat: 'd-M-Y',
        allowInput: false,
        minDate: 'today',
        onChange: function(selectedDates, dateStr, instance) {
            $('#hiddenexpecteddeldate').val(instance.formatDate(selectedDates[0], 'Y-m-d'));
        }
    });

    // 4. Receipt Date
    flatpickr('#receiptdate', {
        dateFormat: 'd-M-Y',
        maxDate: 'today',
        allowInput: false,
        onChange: function(selectedDates, dateStr, instance) {
            $('#hiddenreceiptdate').val(instance.formatDate(selectedDates[0], 'Y-m-d'));
        }
    });

    // Global scope mein daal do taaki booking date se access kar sake
    window.deliveryPicker = deliveryPicker;
}

function initNumericOnlyFields() {
        const numericFields = [
            '#manufacturingyear',
            '#odometerreading',
            '#expectedprice',
            '#offeredprice',
            '#exchangebonus'
        ];

        numericFields.forEach(selector => {
            const $field = $(selector);

            $field.addClass('numeric-only');

            // Allow only numbers (and decimal for prices)
            $field.on('input', function() {
                let val = this.value;

                if (this.id === 'manufacturingyear' || this.id === 'odometerreading') {
                    // Only integers
                    val = val.replace(/[^0-9]/g, '');
                } else {
                    // Prices → allow decimal
                    val = val.replace(/[^0-9.]/g, '');
                    // Prevent multiple decimals
                    const parts = val.split('.');
                    if (parts.length > 2) val = parts[0] + '.' + parts.slice(1).join('');
                }

                this.value = val;
            });

            // Prevent non-numeric keys (extra safety)
            $field.on('keypress', function(e) {
                if (this.id === 'manufacturingyear' || this.id === 'odometerreading') {
                    if (!/[0-9]/.test(e.key)) {
                        e.preventDefault();
                    }
                } else {
                    if (!/[0-9.]/.test(e.key)) {
                        e.preventDefault();
                    }
                }
            });
        });
    }
    // Calculate age based on date of birth
    function calculateAge(dob) {
        const today = new Date();
        let age = today.getFullYear() - dob.getFullYear();
        const monthDiff = today.getMonth() - dob.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
            age--;
        }
        return age;
    }

    // Initialize input masks
    function initMasks() {
        $('#panno').mask('AAAAA0000A', { placeholder: 'ABCDE1234F' });
        $('#adharno').mask('0000-0000-0000', { placeholder: '1234-5678-9012' });
        $('#refrenceno').mask('AAAAAAAAAA', { placeholder: 'Booking Reference No.' });
        $('#gstn').mask('00AAAAA0000A0ZS', { placeholder: '12ABCDE1234F2ZK', clearIfNotMatch: false });

        $('#receiptvoucherinput').mask('00000', { placeholder: '12345', reverse: true });
        $('#receiptvoucherinput').on('keydown keypress', function(e) {
            if (e.which === 32) e.preventDefault();
        });
        $('#receiptvoucherinput').on('paste', function(e) {
            setTimeout(() => this.value = this.value.replace(/ /g, ''), 0);
        });
    }

    // Initialize form validation
    function initValidation() {
        $.validator.addMethod('panFormat', function(value, element) {
            return this.optional(element) || /[A-Z]{5}[0-9]{4}[A-Z]{1}/.test(value);
        }, 'Please enter a valid PAN number e.g., ABCDE1234F');

        $.validator.addMethod('udaiFormat', function(value, element) {
            return this.optional(element) || /\d{4}-\d{4}-\d{4}/.test(value);
        }, 'Please enter a valid Aadhar No. e.g., 1234-5678-9012');

        $.validator.addMethod('receiptFormat', function(value, element) {
            return this.optional(element) || /\d{1,5}/.test(value);
        }, 'Please enter a valid Receipt number (1-5 digits)');

        $.validator.addMethod('gstnFormat', function(value, element) {
            return this.optional(element) || /\d{2}[A-Z]{5}\d{4}[A-Z]{1}\d[A-Z0-9]{1}Z[A-Z0-9]/.test(value);
        }, 'Please enter a valid GSTIN e.g., 08CDBPB0580N2ZK');

        const bookingForm = $('#bookingForm');
        bookingForm.validate({
            rules: {
                customertype: { required: true },
                customercat: { required: true },
                bookingdate: { required: true },
                coltype: { required: true },
                user: {
                    required: function() {
                        return $('#coltype').val() === '2' || $('#coltype').val() === '3';
                    }
                },
                gstn: {
                    gstnFormat: true,
                    required: function() {
                        return !$('#notrequiredgst').is(':checked');
                    }
                },
                bookingamount: { required: true, number: true },
                receiptno: {
                    required: function() {
                        return $('#coltype').val() === '1';
                    },
                    receiptFormat: true
                },
                receiptdate: {
                    required: function() {
                        return $('#coltype').val() === '1' || $('#coltype').val() === '4';
                    }
                },
                name: { required: true },
                careof: { required: true },
                careofname: { required: true },
                mobile: { required: true, digits: true, minlength: 10, maxlength: 10 },
                altmobile: { digits: true, minlength: 10, maxlength: 10 },
                gender: { required: true },
                occupation: { required: true },
                panno: { panFormat: true },
                adharno: { udaiFormat: true },
                customerdob: { required: true },
                branch: { required: true },
                location: {
                    required: function() {
                        return parseInt($('#location').val()) !== 0;
                    }
                },
                locationother: {
                    required: function() {
                        return parseInt($('#location').val()) === 0;
                    }
                },
                refcustomername: {
                    required: function() {
                        return $('#referredby').is(':checked');
                    }
                },
                refmobileno: {
                    required: function() {
                        return $('#referredby').is(':checked');
                    },
                    digits: true,
                    minlength: 10,
                    maxlength: 10
                },
                refexistingmodel: {
                    required: function() {
                        return $('#referredby').is(':checked');
                    }
                },
                refvariant: {
                    required: function() {
                        return $('#referredby').is(':checked');
                    }
                },
                refchassisregno: {
                    required: function() {
                        return $('#referredby').is(':checked');
                    }
                },
                buyertype: { required: true },
                enummaster1: {
                    required: function() {
                        return ['Additional Buy', 'Exchange Buy', 'Scrappage'].includes($('#buyertype').val());
                    }
                },
                vehicledetails: {
                    required: function() {
                        return ['Additional Buy', 'Exchange Buy', 'Scrappage'].includes($('#buyertype').val());
                    }
                },
                enummaster2: {
                    required: function() {
                        return false; // Never mandatory for Additional Buy (as per new requirement)
                    }
                },
                vehicledetails2: {
                    required: function() {
                        return false;
                    }
                },
                registrationno: {
                    required: function() {
                        return ['Exchange Buy', 'Scrappage'].includes($('#buyertype').val());
                    }
                },
                manufacturingyear: {
                    required: function() {
                        return ['Exchange Buy', 'Scrappage'].includes($('#buyertype').val());
                    }
                },
                odometerreading: {
                    required: function() {
                        return $('#buyertype').val() === 'Exchange Buy';
                    }
                },
                expectedprice: {
                    required: function() {
                        return $('#buyertype').val() === 'Exchange Buy';
                    }
                },
                offeredprice: {
                    required: function() {
                        return $('#buyertype').val() === 'Exchange Buy';
                    }
                },
                exchangebonus: {
                    required: function() {
                        return $('#buyertype').val() === 'Exchange Buy';
                    }
                },
                segmentid: { required: true },
                model: { required: true },
                variant: { required: true },
                color: { required: true },
                bookingmode: { required: true },
                refrenceno: {
                    required: function() {
                        return $('#bookingmode').val() === 'Online';
                    }
                },
                saleconsultant: { required: true },
                bookingsource: { required: true },
                dsadetails: {
                    required: function() {
                        return $('#bookingsource').val() === 'DSA';
                    }
                },
                deliverytype: { required: true },
                expecteddeldate: { required: true },
                finmode: { required: true },
                financier: {
                    required: function() {
                        return $('#finmode').val() === 'In-house';
                    }
                },
                loanstatus: {
                    required: function() {
                        return $('#finmode').val() === 'In-house';
                    }
                }
            },
            messages: {
                customertype: 'Please select customer type',
                customercat: 'Please select customer category',
                bookingdate: 'Please select booking date',
                coltype: 'Please select collection type',
                user: 'Please select collected by',
                bookingamount: 'Please enter a valid booking amount',
                receiptno: 'Please enter a valid receipt number',
                receiptdate: 'Please select receipt date',
                name: 'Please enter customer name',
                careof: 'Please select care of',
                careofname: 'Please enter care of name',
                mobile: 'Please enter a valid 10-digit mobile number',
                altmobile: 'Please enter a valid 10-digit alternate mobile number',
                gender: 'Please select gender',
                occupation: 'Please select occupation',
                customerdob: 'Please select customer date of birth',
                branch: 'Please select branch',
                location: 'Please select location',
                locationother: 'Please enter other location',
                refcustomername: 'Please enter referred customer name',
                refmobileno: 'Please enter a valid 10-digit mobile number',
                refexistingmodel: 'Please enter existing model',
                refvariant: 'Please enter variant',
                refchassisregno: 'Please enter chassis/registration number',
                buyertype: 'Please select purchase type',
                enummaster1: 'Please select brand/Make 1',
                vehicledetails: 'Please enter model/variant 1',
                enummaster2: 'Please select brand/Make 2',
                vehicledetails2: 'Please enter model/variant 2',
                registrationno: 'Please enter vehicle registration number',
                manufacturingyear: 'Please enter manufacturing year',
                odometerreading: 'Please enter odometer reading',
                expectedprice: 'Please enter expected price',
                offeredprice: 'Please enter offered price',
                exchangebonus: 'Please enter exchange bonus',
                segmentid: 'Please select segment',
                model: 'Please select model',
                variant: 'Please select variant',
                color: 'Please select color',
                bookingmode: 'Please select booking mode',
                refrenceno: 'Please enter reference number',
                saleconsultant: 'Please select sales consultant',
                bookingsource: 'Please select booking source',
                dsadetails: 'Please select DSA',
                deliverytype: 'Please select delivery date type',
                expecteddeldate: 'Please select delivery date',
                finmode: 'Please select finance mode',
                financier: 'Please select a financier',
                loanstatus: 'Please select loan status'
            },
            errorElement: 'span',
            errorPlacement: function(error, element) {
                error.addClass('text-danger');
                error.insertAfter(element);
            },
            highlight: function(element) {
                $(element).removeClass('is-valid').addClass('is-invalid');
            },
            unhighlight: function(element) {
                $(element).removeClass('is-invalid').addClass('is-valid');
            },
            onfocusout: function(element) {
                this.element(element);
            },
            submitHandler: function(form) {
                if ($('#bookingForm').valid()) {
                    form.submit();
                } else {
                    showErrorModal();
                    return false;
                }
            }
        });
    }

    // Initial call + change event
function setInitialState() {
    toggleCustomerFields($('#customertype').val());
    toggleCollectionFields($('#coltype').val());
    toggleFinanceFields($('#finmode').val()); // yeh call hona zaroori hai
    togglePurchaseFields($('#buyertype').val());

    initFlatpickr();
    $('#customernamelabel').html('Customer Name <span class="required-mark">*</span>');

}

$('#finmode').on('change', function() {
    toggleFinanceFields(this.value);
});

// Financier short name auto fill
$('#financier').on('change', function() {
    const shortName = $(this).find(':selected').data('shortname') || '';
    $('#financiershortname').val(shortName);
});
    // Bind event listeners
    function bindEventListeners() {
        $('#customertype').on('change', function() {
            toggleCustomerFields(this.value);
        });

        $('#notrequiredgst').on('change', function() {
            const isChecked = this.checked;
            $('#gstn').prop('disabled', isChecked).prop('required', !isChecked);
            toggleRequiredMark($('#gstn'), !isChecked);
        });

        $('#customercat').on('change', function() {
    const isFirm = this.value === 'Firm';

    if (isFirm) {
        // Care Of mein "Owned By" default select kar do
        $('#careof').html(`
            <option value="">Please Select...</option>
            <option value="5" selected>Owned By</option>
        `);
        // Care Of Name enabled aur required rakho
        $('#careofname').prop('disabled', false).prop('required', true);
        toggleRequiredMark($('#careofname'), true);
    } else {
        // Normal individual ke liye
        $('#careof').html(`
            <option value="">Please Select...</option>
            <option value="1">Son of</option>
            <option value="2">Daughter of</option>
            <option value="3">Married to</option>
            <option value="4">Guardian Name</option>
        `);
        $('#careofname').prop('disabled', false).prop('required', true);
        toggleRequiredMark($('#careofname'), true);
    }

    $('#customernamelabel').text(isFirm ? 'Firm Name' : 'Customer Name');
});

        $('#coltype').on('change', function() {
            toggleCollectionFields(this.value);
        });

        $('#referredby').on('change', function() {
            const isChecked = this.checked;
            const referralFields = [$('#refcustomername'), $('#refmobileno'), $('#refexistingmodel'), $('#refvariant'), $('#refchassisregno')];
            referralFields.forEach(field => {
                field.prop('disabled', !isChecked).prop('required', isChecked);
            });
            if (!isChecked) {
                referralFields.forEach(field => field.val(''));
            }
            toggleRequiredMark($('#refcustomername, #refmobileno, #refexistingmodel, #refvariant, #refchassisregno'), isChecked);
        });

        $('#bookingsource').on('change', function() {
            const isDSA = this.value === 'DSA';

            // Enable/disable DSA dropdown based purely on Booking Source
            $('#dsadetails')
                .prop('disabled', !isDSA)
                .prop('required', isDSA);

            if (isDSA) {
                $('#dsadetails').next('.select2-container').removeClass('select2-disabled-custom');
            } else {
                $('#dsadetails').next('.select2-container').addClass('select2-disabled-custom');
                $('#dsadetails').val('').trigger('change'); // optional: clear when switching away
            }

            toggleRequiredMark($('#dsadetails'), isDSA);

            // Also update validation rules dynamically (good practice)
            $('#bookingForm').validate().settings.rules.dsadetails.required = isDSA;
        });

        $('#finmode').on('change', function() {
            toggleFinanceFields(this.value);
        });

        $('#bookingmode').on('change', function() {
            const isOnline = this.value === 'Online';
            $('#refrenceno').prop('disabled', !isOnline).prop('required', isOnline);
            toggleRequiredMark($('#refrenceno'), isOnline);
        });

        $('#buyertype').on('change', function() {
            togglePurchaseFields(this.value);
        });

        $('#segmentid').on('change', function() {
            const segmentId = this.value;  // Numeric ID
            const segmentName = $(this).find(':selected').text();


    $.ajax({
        url: '../get-models/' + segmentId,
        method: 'GET',
        success: function(data) {
            populateSelect($('#model'), data, 'name', 'id');
            $('#model').prop('disabled', false);
            resetFields($('#variant'), $('#color'), $('#chassis'));
        },
        error: handleAjaxError('Error fetching models')
    });
});

$('#model').on('change', function() {
    const modelId = this.value;
    $.ajax({
        url: '../get-variants/' + modelId,
        method: 'GET',
        success: function(data) {
            populateSelect($('#variant'), data, 'name', 'id');
            $('#variant').prop('disabled', false);
            resetFields($('#color'), $('#chassis'));
            resetAccessories();
        },
        error: handleAjaxError('Error fetching variants')
    });
});

$('#variant').on('change', function() {
    const variantId = this.value;  // Numeric ID (confirm from populateSelect 'id')
    const modelId = $('#model').val();
    const segmentName = $('#segmentid').find(':selected').text();  // Text for condition
    const segmentNumericId = $('#segmentid').val();  // Numeric for accessories URL

    console.log('Variant changed to ID:', variantId);

    if (!variantId) return;

    $.ajax({
        url: '../get-colors/' + variantId,  // Consistent URL (adjust if needed to /admin/)
        method: 'GET',
        success: function(data) {
            console.log('Raw colors data:', data);

            let colorsArray = [];
            if (typeof data === 'object' && !Array.isArray(data)) {
                colorsArray = Object.values(data);
            } else if (Array.isArray(data)) {
                colorsArray = data;
            }

            if (colorsArray.length > 0) {
                populateSelect($('#color'), colorsArray, 'colr_name', 'colr_name', null, function(option, item) {
                    option.dataset.code = item.modelcode || item.model_code || '';
                    option.dataset.vid = item.vid || '';
                });
                $('#color').prop('disabled', false);
                $('#seating').val(colorsArray[0]?.seating || '0');
                resetFields($('#chassis'));
            } else {
                resetFields($('#color'), $('#chassis'));
                $('#color').prop('disabled', true);
            }
        },
        error: handleAjaxError('Error fetching colors')
    });

    if (segmentName && modelId && variantId) {
        $.ajax({
            url: '../get-accessories/' + segmentNumericId + '/' + modelId + '/' + variantId,
            method: 'GET',
            success: function(data) {
                populateSelect($('#accessories'), data, 'name', 'id', null, function(option, item) {
                    option.dataset.price = item.price;
                });
                $('#accessories').prop('disabled', false);
            },
            error: handleAjaxError('Error fetching accessories')
        });
    }
});


$('#color').on('change', function() {
    const selectedColor = $(this).find(':selected');
    const vhid = selectedColor.data('vid');           // VID (future mein use ho sakta hai)
    const modelCode = selectedColor.data('code');     // YEH BHEJNA HAI – jaise BH826G2S7U6WD

    console.log('Selected Color VID:', vhid);
    console.log('Model Code for Chassis Search:', modelCode);

    $('#vhid').val(vhid); // agar kahin aur use ho

    // Clear previous chassis
    resetFields($('#chassis'));
    $('#chassis').prop('disabled', true);

    if (!modelCode || modelCode.trim() === '') {
        console.warn('Model code missing! Cannot load chassis.');
        alert('Model code not available for this color.');
        return;
    }

    $.ajax({
    url: '../get-chassis-numbers/' + encodeURIComponent(modelCode.trim()),
    method: 'GET',
    success: function(data) {
        console.log('Chassis Numbers Received:', data);

        if (Array.isArray(data) && data.length > 0) {
            populateSelect($('#chassis'), data, 'chasis_no', 'id');
            $('#chassis').prop('disabled', false);
            console.log('Chassis dropdown loaded with', data.length, 'options');
        } else {
            console.warn('No chassis available for model code:', modelCode);

            $('#chassis').html('<option value="">No chassis available</option>');
            $('#chassis').prop('disabled', true);

            // SweetAlert2 इस्तेमाल करें
            Swal.fire({
                icon: 'warning',
                title: 'Chassis Not Found',
                text: 'No allotted chassis found for this model + color combination.',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
        }
    },
    error: function(xhr) {
        console.error('Chassis Load Failed:', xhr.status, xhr.responseText);

        $('#chassis').html('<option value="">Error loading chassis</option>');

        // SweetAlert2 error version
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: 'Error loading chassis numbers. Please try again or contact support.',
            footer: 'Status: ' + xhr.status,
            confirmButtonColor: '#d33',
            confirmButtonText: 'OK'
        });
    }
});
});

        $('#accessories').on('select2:select select2:unselect', function(e) {
            const price = e.params.data.element.dataset.price;
            const isAdd = e.type === 'select2:select';
            updateAccessoriesAmount(price, isAdd);
        });

        $('#expectedprice, #offeredprice, #exchangebonus').on('input', calculatePriceGap);

        $('#receiptvoucherinput').on('change.duplicate', function() {
            const input = this;
            const fieldName = 'receiptvoucherno';
            const type = $('#coltype').val() === '1' ? 'type1' : 'type4';
            attachDuplicateCheck($(input), fieldName, type);
        });

        $('#branch').on('change', function() {
            const branchId = this.value;
            if (!branchId) return;

            $.ajax({
                url: '../branchlocations/' + branchId + '/',  // Match your current route exactly
                method: 'GET',
                success: function(data) {
                    populateSelect($('#location'), data, 'name', 'id', '<option value="0">OTHER</option>');
                    $('#location').prop('disabled', false);
                },
                error: function(xhr) {
                    console.error('Error fetching locations', xhr);
                    alert('Error fetching locations. Please try again.');
                }
            });
        });

        $('#location').on('change', function() {
            const isOther = parseInt(this.value) === 0;
            $('#locationother').prop('disabled', !isOther).prop('required', isOther);
            if (!isOther) {
                $('#locationother').val('');
            }
            toggleRequiredMark($('#locationother'), isOther);
        });

        $('#financier').on('change', function() {
            const shortName = $(this).find(':selected').data('shortname');
            $('#financiershortname').val(shortName || '');
        });
    }

    // Initialize uppercase inputs
    function initUppercaseInputs() {
        $('input[type="text"]').not('.no-uppercase').not('#remarks').on('input', function() {
            const start = this.selectionStart;
            const end = this.selectionEnd;
            this.value = this.value.toUpperCase();
            this.setSelectionRange(start, end);
        });
    }

    // Toggle customer fields
    function toggleCustomerFields(type) {
        const isDummy = type === 'Dummy';
        const fields = [$('#bookingmode'), $('#bookingsource'), $('#coltype')];

        fields.forEach(field => {
            field.prop('disabled', isDummy).prop('required', !isDummy);
        });

        $('#bookingamount, #receiptno, #receiptdate').prop('disabled', isDummy).prop('required', !isDummy);
        toggleRequiredMark(fields, !isDummy);

        const receiptDatePicker = $('#receiptdate').data('flatpickr');
        if (isDummy) {
            receiptDatePicker?.disable();
            $('#bookingmode').val('Dealer');
            $('#bookingsource').val('Dealer');
            $('#coltype').val('1');
            $('#bookingamount').val('0');
            $('#receiptno').val('');
            $('#receiptdate').val('');
            $('#hiddenreceiptdate').val('');
        } else {
            receiptDatePicker?.enable();
        }
    }

    function toggleCollectionFields(type) {

        const isDummy = $('#customertype').val() === 'Dummy';
        const isFieldSales = type == '2';
        const isFieldDSA = type == '3';
        const isField = isFieldSales || isFieldDSA;
        const isUsedCar = type == '4';
        const isReceipt = type == '1';


        // User field
        $('#user').prop('disabled', !isField).prop('required', isField);
        toggleRequiredMark($('#user'), isField);

        const userSelect = $('#user');
        userSelect.html('<option value="">Please Select...</option>');

        if (isFieldSales) {
            @php
                foreach($data['allusers'] ?? [] as $user) {
                    echo "userSelect.append('<option value=\\\"" . $user->id . "\\\">" . $user->name . " - " . ($user->emp_code ?? '') . "</option>');";
                }
            @endphp
        } else if (isFieldDSA) {
            @php
                foreach($data['dsa_details'] ?? [] as $dsa) {
                    echo "userSelect.append('<option value=\\\"" . $dsa->id . "\\\">" . $dsa->name . " - " . ($dsa->mobile ?? '') . "</option>');";
                }
            @endphp
        }

        userSelect.select2();

        // Label swap
        const bookingAmtLabel = $('#bookingamount').siblings('label');
        const receiptDateLabel = $('#receiptdate').siblings('label');

        if (isUsedCar) {
            bookingAmtLabel.html('Received Amount<span class="required-mark">*</span>');
            receiptDateLabel.html('Voucher Date<span class="required-mark">*</span>');
        } else {
            bookingAmtLabel.html('Booking Amount<span class="required-mark">*</span>');
            receiptDateLabel.html('Receipt Date<span class="required-mark">*</span>');
        }

        // Dynamic Receipt/Voucher field
        const input = $('#receiptvoucherinput');
        const label = $('#receiptvoucherlabel');
        const warning = $('#receiptvoucherwarning');
        const group = $('#receiptvouchergroup');

        let inputName, inputPlaceholder, inputMask, labelText;
        let isRequired = true;

        if (isReceipt) {
            inputName = 'receiptno';
            inputPlaceholder = '12345';
            inputMask = '00000';
            labelText = 'Receipt No.';

            input.unmask().mask(inputMask, { placeholder: inputPlaceholder, reverse: true });
            attachDuplicateCheck(input, inputName, 'type1');

            input.attr('name', inputName).attr('placeholder', inputPlaceholder).prop('required', isRequired);
            label.html(labelText + '<span class="required-mark">*</span>');
            group.show();
            input.val('');
            warning.hide();
            input.removeClass('is-invalid');
        } else if (isUsedCar) {
            inputName = 'voucherno';
            inputPlaceholder = 'Enter Voucher No.';
            inputMask = null;
            labelText = 'Voucher No.';

            input.unmask();
            attachDuplicateCheck(input, inputName, 'type4');

            input.attr('name', inputName).attr('placeholder', inputPlaceholder).prop('required', isRequired);
            label.html(labelText + '<span class="required-mark">*</span>');
            group.show();
            input.val('');
            warning.hide();
            input.removeClass('is-invalid');
        } else {
            // Field Collection (2,3) ya koi aur case - receipt/voucher hide karo
            group.hide();
            input.val('').prop('required', false);
            // IMPORTANT: Don't return - DSA logic neeche chalega
        }

        const receiptDatePicker = $('#receiptdate').data('flatpickr');
        if (isReceipt || isUsedCar) {
            receiptDatePicker?.enable();
        } else {
            receiptDatePicker?.disable();
        }



        if (isFieldDSA) {
        // Force + freeze Booking Source = DSA
        $('#bookingsource')
            .val('DSA')
            .prop('disabled', true)
            .trigger('change');

        $('#bookingsource').next('.select2-container').addClass('select2-disabled-custom');

        // Mirror selected user → DSA field + freeze
        const selectedUserId = $('#user').val();
        if (selectedUserId) {
            $('#dsadetails')
                .val(selectedUserId)
                .prop('disabled', true)
                .trigger('change');

            $('#dsadetails').next('.select2-container').addClass('select2-disabled-custom');
        }

        // Keep them in sync if user changes "Collected By"
        $('#user').off('change.syncDSA').on('change.syncDSA', function() {
            $('#dsadetails')
                .val(this.value)
                .trigger('change');
            $('#dsadetails').prop('disabled', true);
            $('#dsadetails').next('.select2-container').addClass('select2-disabled-custom');
        });
    }
    else {
        // ── Not DSA field collection ───────────────────────
        $('#bookingsource')
            .prop('disabled', false)
            .trigger('change');
        $('#bookingsource').next('.select2-container').removeClass('select2-disabled-custom');

        $('#user').off('change.syncDSA');

        // Important: DSA field control moved OUT from here
        // → now controlled only by #bookingsource change
    }
    }

    // Toggle finance fields - 100% WORKING VERSION
function toggleFinanceFields(mode) {
    const isInHouse = mode === 'In-house';

    $('#financier')
        .prop('disabled', !isInHouse)
        .prop('required', isInHouse);

    $('#financiershortname')
        .prop('disabled', !isInHouse)
        .prop('required', isInHouse);



    $('#loanstatus')
        .prop('disabled', !isInHouse)
        .prop('required', isInHouse);

    toggleRequiredMark('#financier, #loanstatus', isInHouse);

    if (!isInHouse) {
        $('#financier').val('').trigger('change');
        $('#loanstatus').val('Pending');
        $('#financiershortname').val('');
    }
}

 function togglePurchaseFields(type) {
    const fields = {
        base:        [$('#enummaster1'), $('#vehicledetails')],
        extraMake:   [$('#enummaster2'), $('#vehicledetails2')],
        exchange:    [
            $('#registrationno'),
            $('#manufacturingyear'),
            $('#odometerreading'),
            $('#expectedprice'),
            $('#offeredprice'),
            $('#exchangebonus')
        ]
    };

    // ──── Helpers ────────────────────────────────────────
    function disableAll(arr) {
        arr.forEach($el => {
            $el
                .prop('disabled', true)
                .prop('required', false)
                .val('')
                .trigger('change')
                .removeClass('is-invalid is-valid')
                .siblings('span.text-danger').remove();  // clear old error messages
            toggleRequiredMark($el, false);
        });
    }

    function makeRequired(arr) {
        arr.forEach($el => {
            $el
                .prop('disabled', false)
                .prop('required', true);
            toggleRequiredMark($el, true);
        });
    }

    function makeOptional(arr) {
        arr.forEach($el => {
            $el
                .prop('disabled', false)
                .prop('required', false);
            toggleRequiredMark($el, false);
        });
    }

    // ──── 1. Reset everything first (safe starting point) ────────
    disableAll([...fields.base, ...fields.extraMake, ...fields.exchange]);

    // ──── 2. Apply correct state based on type ───────────────────
    if (type === 'First time Buyer') {
        // everything stays disabled (already done)
    }
    else if (type === 'Additional Buy') {
        makeRequired(fields.base);          // Required
        makeOptional(fields.extraMake);     // Optional (enabled, no *)
        // exchange → disabled (already)
    }
    else if (type === 'Exchange Buy') {
        makeRequired(fields.base);          // Required
        disableAll(fields.extraMake);       // Disabled (not optional)
        makeRequired(fields.exchange);      // Required
    }
    else if (type === 'Scrappage') {
        makeRequired(fields.base);          // Required
        makeRequired([$('#registrationno'), $('#manufacturingyear')]);  // Required
        // rest → disabled (already)
        disableAll(fields.extraMake);
        disableAll([
            $('#odometerreading'),
            $('#expectedprice'),
            $('#offeredprice'),
            $('#exchangebonus')
        ]);
    }

    // ──── 3. Refresh validation state only for affected fields ───
    const affectedSelectors = [
        '#enummaster1', '#vehicledetails',
        '#enummaster2', '#vehicledetails2',
        '#registrationno', '#manufacturingyear',
        '#odometerreading', '#expectedprice',
        '#offeredprice', '#exchangebonus'
    ];

    const validator = $('#bookingForm').validate();

    affectedSelectors.forEach(sel => {
        const $field = $(sel);
        if (!$field.length) return;

        if ($field.prop('disabled')) {
            // Disabled fields → clean up any leftover visual error state
            $field.removeClass('is-invalid is-valid')
                  .siblings('span.text-danger').remove();
        }
        else if ($field.data('touched')) {
            // Only validate fields user has already interacted with
            validator.element($field[0]);
        }
        // untouched enabled fields → no forced validation (clean look)
    });
}

    // Calculate price gap
    function calculatePriceGap() {
        const expected = parseFloat($('#expectedprice').val()) || 0;
        const offered = parseFloat($('#offeredprice').val()) || 0;
        const bonus = parseFloat($('#exchangebonus').val()) || 0;
        $('#difference').val(Math.round(expected - offered - bonus));
    }

    // Populate select
    function populateSelect(selector, data, textKey, valueKey, extra = null, callback = null) {
        const select = selector;
        select.html('<option value="0" selected disabled>Please Select...</option>');

        $.each(data, function(_, item) {
            const option = new Option(item[textKey], item[valueKey]);
            if (callback) callback(option, item);
            select.append(option);
        });

        if (extra) {
            select.append(extra);
        }
    }

    // Reset fields
    function resetFields(...fields) {
        fields.forEach(field => {
            field.html('<option value="0" selected disabled>Please Select...</option>').prop('disabled', true);
        });
    }

    // Reset accessories
    function resetAccessories() {
        $('#accessories').empty().prop('disabled', true);
        $('#apackamount').val('0');
    }

    // Update accessories amount
    function updateAccessoriesAmount(price, isAdd) {
        const current = parseFloat($('#apackamount').val()) || 0;
        const change = parseFloat(price) || 0;
        $('#apackamount').val(isAdd ? current + change : current - change);
    }

    function attachDuplicateCheck(input, fieldName, type) {
    input.off('change.duplicate').on('change.duplicate', function() {
        const value = this.value.trim();
        if (value) {
            $.ajax({
                url: '{{ url("/admin/check-receipt") }}/' + value,
                method: 'GET',
                success: function(data) {
                    if (data !== 0) {
                        $('#receiptvoucherwarning').show().text(fieldName.charAt(0).toUpperCase() + fieldName.slice(1).replace(/-/g, ' ') + ' already exists');
                        input.addClass('is-invalid');
                        $('#submitBtn').prop('disabled', true);
                    } else {
                        $('#receiptvoucherwarning').hide();
                        input.removeClass('is-invalid');
                        $('#submitBtn').prop('disabled', false);
                    }
                },
                error: handleAjaxError('Error checking number')
            });
        }
    });

    input.off('input.duplicate').on('input.duplicate', function() {
        resetDuplicateState();
    });
}

    // Reset duplicate state
    function resetDuplicateState() {
        $('#receiptvoucherwarning').hide();
        $('#receiptvoucherinput').removeClass('is-invalid');
        $('#submitBtn').prop('disabled', false);
    }

    // Show error modal
    function showErrorModal() {
        const errors = $('#bookingForm').validate().errorList;
        let errorHtml = '<ul>';
        $.each(errors, function(_, error) {
            errorHtml += '<li>' + error.message + '</li>';
        });
        errorHtml += '</ul>';
        $('#errorModal .modal-body').html(errorHtml);
        $('#errorModal').modal('show');
    }

    // Toggle required mark
    function toggleRequiredMark(selector, show) {
        $(selector).siblings('label').find('.required-mark').css('display', show ? 'inline' : 'none');
    }

    // Handle AJAX errors
    function handleAjaxError(message) {
        return function(xhr) {
            console.error(message, xhr);
            alert(message + '. Please try again.');
        };
    }

    // Initialize on document ready
    $(document).ready(initBookingForm);
})();
$('#bookingForm').on('submit', function(e) {
    // Re-enable fields that are disabled but should be submitted
    $('#bookingsource, #dsadetails, #user').prop('disabled', false);

    // Optional: if you want to keep UI disabled, disable again after submit (but not needed usually)
});
$(document).ready(function() {
const initialBookingSource = $('#bookingsource').val();
    $('#dsadetails').prop('disabled', initialBookingSource !== 'DSA');

    if (initialBookingSource !== 'DSA') {
        $('#dsadetails').next('.select2-container').addClass('select2-disabled-custom');
    } else {
        $('#dsadetails').next('.select2-container').removeClass('select2-disabled-custom');
    }

    // Optional: trigger change so rules & UI update cleanly
    $('#bookingsource').trigger('change');
    // 1. Vehicle Registration No. (#registrationno)
    //    - No spaces allowed
    //    - All letters → uppercase
    $('#registrationno').on('input', function() {
        let val = this.value;

        // Remove all spaces
        val = val.replace(/\s+/g, '');

        // Convert letters to uppercase (numbers/symbols remain unchanged)
        val = val.toUpperCase();

        // Put cleaned value back
        this.value = val;
    });

    // Prevent space key press completely (extra safety)
    $('#registrationno').on('keydown', function(e) {
        if (e.key === ' ' || e.keyCode === 32) {
            e.preventDefault();
        }
    });


    // 2. Chassis Regn. No. (#refchassisregno)
    //    Same rules: no spaces, uppercase letters
    $('#refchassisregno').on('input', function() {
        let val = this.value;

        // Remove all spaces
        val = val.replace(/\s+/g, '');

        // Convert to uppercase
        val = val.toUpperCase();

        this.value = val;
    });

    // Block space key
    $('#refchassisregno').on('keydown', function(e) {
        if (e.key === ' ' || e.keyCode === 32) {
            e.preventDefault();
        }
    });

});

</script>
@section('after_scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%',
            });
        });
</script>

@endsection
@endpush