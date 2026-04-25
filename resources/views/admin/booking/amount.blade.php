@extends(backpack_view('blank'))

@section('title', 'Add Booking Amount - ' . ($booking->booking_no ?? $booking->id))

@section('content')
<div class="container-fluid">
    <div class="row">

        <!-- Booking Summary -->
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header text-black">
                    <h2 class="mb-0">
                        {{-- <i class="la la-info-circle me-1"></i> --}}
                        Booking Summary
                    </h2>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-3">
                            <label class="small fw-bold">Booking Date</label>
                            <input type="text" class="form-control"
                                value="{{ $booking->booking_date ? \Carbon\Carbon::parse($booking->booking_date)->format('d-M-Y') : 'N/A' }}"
                                readonly>
                        </div>
                        <div class="col-sm-3">
                            <label class="small fw-bold">Customer Name</label>
                            <input type="text" class="form-control" value="{{ $booking->name ?? 'N/A' }}" readonly>
                        </div>
                        <div class="col-sm-3">
                            <label class="small fw-bold">Branch</label>
                            <input type="text" class="form-control" value="{{ $booking->branch?->name ?? 'N/A' }}"
                                readonly>
                        </div>
                        <div class="col-sm-3">
                            <label class="small fw-bold">Location</label>
                            <input type="text" class="form-control"
                                value="{{ $booking->location ? ($booking->location->name . ' - ' . ($booking->location->abbr ?? 'N/A')) : 'N/A' }}"
                                readonly>
                        </div>
                        <div class="col-sm-4">
                            <label class="small fw-bold">Model</label>
                            <input type="text" class="form-control" value="{{ $booking->model ?? 'N/A' }}" readonly>
                        </div>
                        <div class="col-sm-4">
                            <label class="small fw-bold">Variant</label>
                            <input type="text" class="form-control" value="{{ $booking->variant ?? 'N/A' }}" readonly>
                        </div>
                        <div class="col-sm-4">
                            <label class="small fw-bold">Color</label>
                            <input type="text" class="form-control" value="{{ $booking->color ?? 'N/A' }}" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Amount Form -->
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-gradient-primary text-black">
                    <h2 class="mb-0">
                        {{-- <i class="la la-rupee-sign me-1"></i> --}}
                        Add Receipt of Received Amount
                    </h2>
                </div>

                <div class="card-body">
                    <form class="forms-sample" method="POST"
                        action="{{ route('booking.add-amount.store', $booking->id) }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="bid" value="{{ $booking->id }}">

                        <div class="row g-4">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="receipt_date" class="form-label">Receipt Date <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="receipt_date" id="receipt_date" class="form-control"
                                        placeholder="dd-MMM-yyyy" required>
                                    <input type="hidden" name="hidden_receipt_date" id="hidden_receipt_date">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="reciept_no" class="form-label">Receipt Number <span
                                            class="text-danger">*</span></label>
                                    <input type="text" id="reciept_no" name="reciept_no" class="form-control"
                                        placeholder="Enter Receipt Number" required>
                                    <div id="reciept_no_warning" class="invalid-feedback" style="display:none;">Receipt
                                        Number already exists.</div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="amount" class="form-label">Received Amount <span
                                            class="text-danger">*</span></label>
                                    <input type="number" id="amount" name="amount" step="0.01" min="0.01"
                                        class="form-control" placeholder="Enter Amount" required>
                                </div>
                            </div>

                            {{-- <div class="col-md-3">
                                <div class="form-group">
                                    <label for="fdoc" class="form-label">Upload Proof <span
                                            class="text-danger">*</span></label>
                                    <input type="file" name="amount_proof" id="fdoc" class="form-control"
                                        accept="image/jpeg,image/png,application/pdf" required onchange="previewFile()">
                                    <small class="form-text">Max 2MB • JPG, PNG, PDF only</small>

                                    <div class="mt-3 position-relative d-inline-block">
                                        <img id="frameLeft" src="" class="img-thumbnail" width="140"
                                            style="display:none;">
                                        <img id="pdfIcon" src="{{ asset('images/pdf-icon.png') }}" class="img-thumbnail"
                                            width="140" style="display:none;">
                                        <button type="button" id="clearLeft"
                                            class="btn btn-danger btn-sm rounded-circle position-absolute top-0 end-0 translate-middle"
                                            style="display:none; width:28px; height:28px; line-height:1; font-size:18px; padding:0;"
                                            onclick="discardImageLeft()">×</button>
                                    </div>
                                </div>
                            </div> --}}
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="fdoc" class="form-label">
                                        Upload Proof <span class="text-danger">*</span>
                                    </label>
                                    <input type="file" name="amount_proof" id="fdoc" class="form-control"
                                        accept="image/jpeg,image/png,application/pdf" required
                                        onchange="handleProofAttachment(this)">
                                    <small class="form-text text-muted">Max 2MB • JPG, PNG, PDF only</small>

                                    <!-- Chip Preview Area (same as show-invoiced) -->
                                    <div id="proofPreview" class="mt-3"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-5">
                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-success btn-lg px-5" id="submitBtn">
                                    <i class="la la-save me-2"></i> Add Amount
                                </button>
                                <a href="{{ backpack_url('booking') }}"
                                    class="btn btn-secondary btn-lg px-5 ms-3">Cancel</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Proof Preview Modal (same as show-invoiced) -->
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
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('after_scripts')
<!-- Flatpickr -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Flatpickr (receipt date)
        flatpickr("#receipt_date", {
            dateFormat: "d-M-Y",
            maxDate: "today",
            allowInput: false,
            onChange: function(selectedDates) {
                if (selectedDates[0]) {
                    const d = selectedDates[0];
                    const iso = d.getFullYear() + '-' +
                                String(d.getMonth()+1).padStart(2,'0') + '-' +
                                String(d.getDate()).padStart(2,'0');
                    document.getElementById('hidden_receipt_date').value = iso;
                }
            }
        });

        // Receipt number duplicate check (your existing code)
        const recInput = document.getElementById('reciept_no');
        const warning = document.getElementById('reciept_no_warning');
        const submitBtn = document.getElementById('submitBtn');

        if (recInput) {
            recInput.addEventListener('blur', function() {
                const val = this.value.trim();
                if (!val) return warning.style.display = 'none';

                fetch('{{ url("/admin/check-receipt") }}/' + encodeURIComponent(val))
                    .then(r => r.text())
                    .then(data => {
                        const dup = data.trim() === '1' || data.trim().toLowerCase() === 'true' || data.trim().toLowerCase() === 'exists';
                        if (dup) {
                            warning.style.display = 'block';
                            this.classList.add('is-invalid');
                            submitBtn.disabled = true;
                        } else {
                            warning.style.display = 'none';
                            this.classList.remove('is-invalid');
                            submitBtn.disabled = false;
                        }
                    });
            });

            recInput.addEventListener('input', () => {
                warning.style.display = 'none';
                recInput.classList.remove('is-invalid');
                submitBtn.disabled = false;
            });
        }
    });

    // ────────────────────────────────────────────────
    // New: Proof Attachment Handler (same as show-invoiced)
    // ────────────────────────────────────────────────
    function handleProofAttachment(input) {
        const previewDiv = document.getElementById('proofPreview');
        previewDiv.innerHTML = '';

        if (input.files && input.files[0]) {
            const file = input.files[0];

            // Optional: Size check
            if (file.size > 2 * 1024 * 1024) {
                alert('File size exceeds 2MB!');
                input.value = '';
                return;
            }

            const fileURL = URL.createObjectURL(file);

            previewDiv.innerHTML = `
                <span class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-2 px-3 py-2"
                      style="cursor:pointer;"
                      onclick="openProofModal('${fileURL}', '${file.name.replace(/'/g, "\\'")}')">
                    <i class="la la-paperclip"></i>
                    <span class="fw-medium small">${file.name}</span>
                </span>
            `;
        }
    }

    function openProofModal(url, name) {
        document.getElementById('proofModalFileName').innerText = name;
        document.getElementById('proofModalDownload').href = url;

        const previewFrame = document.getElementById('proofModalPreview');

        if (name.toLowerCase().endsWith('.pdf')) {
            previewFrame.src = url;
        } else {
            previewFrame.src = url;
        }

        $('#proofModal').modal({
            backdrop: false,
            keyboard: true
        }).modal('show');
    }
</script>
@endpush