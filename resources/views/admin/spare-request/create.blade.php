@extends(backpack_view('blank'))

@section('title', 'Add New Spare Order Request')

@push('after_styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<style>
    .card {
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    }

    .required-mark {
        color: red;
    }

    .dropdown-menu {
        max-height: 300px;
        overflow-y: auto;
        width: 100%;
        z-index: 9999;
    }

    .is-invalid {
        border-color: #dc3545 !important;
        box-shadow: 0 0 5px rgba(220, 53, 69, 0.5);
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header text-black">
                    <h2 class="mb-0">Add New Spare Order Request</h2>
                </div>
                <div class="card-body">

                    <form method="POST" action="{{ backpack_url('spare-request') }}">
                        @csrf

                        <h5 class="mb-3">Order Information</h5>
                        <div class="row">

                            <!-- Service Branch -->
                            <div class="col-sm-3 form-group">
                                <label>Service Branch <span class="required-mark">*</span></label>
                                <select name="srv_brnch_id" class="form-control form-select" required>
                                    <option value="">Select Branch</option>
                                    @foreach($data['branch'] ?? [] as $branch)
                                    <option value="{{ $branch['id'] }}">{{ $branch['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Service Category -->
                            <div class="col-sm-3 form-group">
                                <label>Service Category <span class="required-mark">*</span></label>
                                <select name="srv_vh_cat_id" class="form-control form-select" required>
                                    <option value="">Select Category</option>
                                    <option value="19704">Vehicle in Workshop</option>
                                    <option value="19709">Vehicle with Customer</option>
                                    <option value="19711">Counter Sale</option>
                                </select>
                            </div>

                            <!-- Workshop Type -->
                            <div class="col-sm-3 form-group">
                                <label>Workshop Type <span class="required-mark">*</span></label>
                                <select name="workshop_type_id" class="form-control form-select" required>
                                    <option value="">Select Type</option>
                                    <option value="19705">Bodyshop</option>
                                    <option value="19706">Workshop</option>
                                </select>
                            </div>

                            <!-- Model -->
                            <div class="col-sm-3 form-group">
                                <label>Model <span class="required-mark">*</span></label>
                                <select name="model" id="model" class="form-control form-select" required>
                                    <option value="">Select Model</option>
                                    @foreach($data['models'] ?? [] as $model)
                                    <option value="{{ $model['name'] }}">{{ $model['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Variant -->
                            <div class="col-sm-3 form-group">
                                <label>Variant <span class="required-mark">*</span></label>
                                <select name="variant" id="variant" class="form-control form-select" required>
                                    <option value="">Select Variant</option>
                                </select>
                            </div>

                            <!-- Customer Name -->
                            <div class="col-sm-3 form-group">
                                <label>Customer Name <span class="required-mark">*</span></label>
                                <input type="text" name="cust_name" class="form-control" required>
                            </div>

                            <!-- Mobile -->
                            <div class="col-sm-3 form-group">
                                <label>Mobile Number <span class="required-mark">*</span></label>
                                <input type="text" name="cust_mobile" class="form-control" required>
                            </div>

                            <!-- Vehicle/Chassis No -->
                            <div class="col-sm-3 form-group">
                                <label>Vehicle / Chassis No <span class="required-mark">*</span></label>
                                <input type="text" name="regn_no" class="form-control" required>
                            </div>

                            <!-- Person ID -->
                            <div class="col-sm-3 form-group">
                                <label>Person ID</label>
                                <input type="number" name="person_id" class="form-control">
                            </div>

                            <!-- RO Number -->
                            <div class="col-sm-3 form-group">
                                <label>R.O. Number <span class="required-mark">*</span></label>
                                <input type="text" name="ro_number" id="ro_no" class="form-control" required>
                                <div id="ro_no_warning" class="text-danger" style="display:none;">RO Number already
                                    exists</div>
                            </div>

                            <!-- RO Date -->
                            <div class="col-sm-3 form-group">
                                <label>R.O. Date <span class="required-mark">*</span></label>
                                <input type="text" name="ro_date" id="ro_date" class="form-control flatpickr" required>
                            </div>

                            <!-- Billed RO -->
                            <div class="col-sm-3 form-group">
                                <label>Billed RO</label>
                                <input type="number" name="billed_ro" class="form-control">
                            </div>

                            <!-- Billed RO Date -->
                            <div class="col-sm-3 form-group">
                                <label>Billed RO Date</label>
                                <input type="text" name="billed_ro_date" id="billed_ro_date"
                                    class="form-control flatpickr">
                            </div>

                            <!-- Status -->
                            <div class="col-sm-3 form-group">
                                <label>Status</label>
                                <select name="status" class="form-control form-select">
                                    <option value="1" selected>Active</option>
                                    <option value="0">Closed</option>
                                </select>
                            </div>

                            <!-- Remarks -->
                            <div class="col-sm-12 form-group">
                                <label>Remarks</label>
                                <textarea name="remark" class="form-control" rows="2"></textarea>
                            </div>
                        </div>

                        <!-- ==================== SPARE PARTS SECTION ==================== -->
                        <h5 class="mb-3 mt-4">Spare Parts Information</h5>
                        <button type="button" class="btn btn-success mb-3" id="add-part-row">
                            <i class="fa fa-plus"></i> Add Parts
                        </button>

                        <div id="parts-container"></div>

                        <div class="row mt-4">
                            <div class="col-sm-4"></div>
                            <div class="col-sm-4">
                                <button type="submit" class="btn btn-success btn-block">Save Spare Request</button>
                            </div>
                            <div class="col-sm-4"></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('after_scripts')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {

    flatpickr("#ro_date, #billed_ro_date", { dateFormat: "Y-m-d", allowInput: true });
    $('.select2').select2({ placeholder: "Select...", allowClear: true });

    // RO Number Check
    $('#ro_no').on('blur', function() {
        let roNo = $(this).val().trim();
        if (roNo) {
            $.get("{{ url('admin/check-ro-number') }}/" + encodeURIComponent(roNo), function(data) {
                if (data == 1) $('#ro_no_warning').show().addClass('is-invalid');
                else $('#ro_no_warning').hide().removeClass('is-invalid');
            });
        }
    });

    // Model → Variant
    $('#model').on('change', function() {
        let model = $(this).val();
        if(model){
            $.get("{{ url('admin/get-variants') }}/" + encodeURIComponent(model), function(data){
                $('#variant').empty().append('<option value="">Select Variant</option>');
                $.each(data, function(key, v){ $('#variant').append(`<option value="${v.name}">${v.name}</option>`); });
            });
        }
    });

    // Add Part Row
    $('#add-part-row').on('click', function() {
        let partRow = `
        <div class="row part-row mt-3">
            <div class="col-sm-3 form-group">
                <label>Part Number <span class="required-mark">*</span></label>
                <input type="text" name="part_no[]" class="form-control part-no-input" placeholder="Enter Part Number" required autocomplete="off">
                <ul class="dropdown-menu"></ul>
                <input type="hidden" name="part_id[]" class="part-id-input">
            </div>
            <div class="col-sm-4 form-group">
                <label>Part Description <span class="required-mark">*</span></label>
                <input type="text" name="part_name[]" class="form-control part-name-input" placeholder="Enter Description" required autocomplete="off">
                <ul class="dropdown-menu"></ul>
            </div>
            <div class="col-sm-2 form-group">
                <label>Order Type <span class="required-mark">*</span></label>
                <select name="order_type[]" class="form-control form-select order-type-select" required>
                    <option value="">Select</option>
                    <option value="Customer Paid">Customer Paid</option>
                    <option value="Warranty">Warranty</option>
                    <option value="Goodwill">Goodwill</option>
                    <option value="FOC">FOC</option>
                </select>
            </div>
            <div class="col-sm-2 form-group">
                <label>Required Qty <span class="required-mark">*</span></label>
                <input type="number" name="req_quan[]" class="form-control" required>
            </div>
            <div class="col-sm-1 form-group align-self-center pt-4">
                <button type="button" class="btn btn-danger remove-part-row"><i class="fa fa-trash"></i></button>
            </div>
        </div>`;

        $('#parts-container').append(partRow);
    });

    // Remove Row
    $(document).on('click', '.remove-part-row', function() {
        $(this).closest('.part-row').remove();
    });

    // Part Autocomplete
    $(document).on('input', '.part-no-input, .part-name-input', function() {
        let query = $(this).val();
        let dropdown = $(this).siblings('.dropdown-menu');
        let fieldType = $(this).hasClass('part-no-input') ? 'part_no' : 'name';

        if (query.length >= 2) {
            $.ajax({
                url: "{{ url('admin/fetch-parts') }}",
                method: "GET",
                data: { query: query, type: fieldType },
                success: function(data) {
                    let items = data.length ? data.map(part =>
                        `<li class="dropdown-item" data-id="${part.id}" data-name="${part.name}" data-part="${part.part_no}">
                            ${part.part_no} - ${part.name}
                         </li>`).join('') :
                        '<li class="dropdown-item disabled">No matches found</li>';
                    dropdown.html(items).show();
                }
            });
        } else {
            dropdown.hide();
        }
    });

    // ==================== DUPLICATE VALIDATION ====================
    function checkDuplicate(row) {
        let partNo = row.find('.part-no-input').val().trim().toUpperCase();
        let orderType = row.find('.order-type-select').val();

        if (!partNo || !orderType) return false;

        let isDuplicate = false;
        $('.part-row').each(function() {
            let existingPartNo = $(this).find('.part-no-input').val().trim().toUpperCase();
            let existingOrderType = $(this).find('.order-type-select').val();

            if (existingPartNo === partNo && existingOrderType === orderType && $(this)[0] !== row[0]) {
                isDuplicate = true;
                return false;
            }
        });

        return isDuplicate;
    }

    // Check on Part Selection
    $(document).on('click', '.dropdown-item', function() {
        let selectedPartNo = $(this).data('part');
        let partName = $(this).data('name');
        let partId = $(this).data('id');
        let inputField = $(this).closest('.dropdown-menu').siblings('input');
        let currentRow = inputField.closest('.part-row');

        // Fill first
        if (inputField.hasClass('part-no-input')) {
            inputField.val(selectedPartNo);
            inputField.closest('.form-group').next().find('.part-name-input').val(partName);
            inputField.siblings('.part-id-input').val(partId);
        } else {
            inputField.val(partName);
            inputField.closest('.form-group').prev().find('.part-no-input').val(selectedPartNo);
            inputField.closest('.form-group').prev().find('.part-id-input').val(partId);
        }

        // Check duplicate after filling
        if (checkDuplicate(currentRow)) {
            Swal.fire({
                icon: 'error',
                title: 'Duplicate Entry',
                text: `This Part with selected Order Type is already added!`,
                confirmButtonColor: '#d33'
            });
            // Optionally clear the row or just warn
        }

        $(this).parent('.dropdown-menu').hide();
    });

    // Also check when Order Type is changed
    $(document).on('change', '.order-type-select', function() {
        let currentRow = $(this).closest('.part-row');
        if (checkDuplicate(currentRow)) {
            Swal.fire({
                icon: 'error',
                title: 'Duplicate Entry Detected',
                text: 'This combination of Part Number and Order Type already exists!',
                confirmButtonColor: '#d33'
            });
        }
    });

    // Hide dropdown
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.part-no-input, .part-name-input, .dropdown-menu').length) {
            $('.dropdown-menu').hide();
        }
    });
});
</script>
@endpush