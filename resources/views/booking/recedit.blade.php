@extends(backpack_view('blank'))

@section('title', 'Edit Receipt')

@section('header')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endsection

@section('content')
<div class="container-fluid">
    @include(backpack_view('inc.alerts'))

    {{-- <h2 class="mb-4">Edit Receipt</h2> --}}

    <div class="card">
        <div class="card-header">
            <h2>Receipt Details</h2>
        </div>

        <div class="card-body">
            <form action="{{ route('receipt.update', ['bookingId' => $booking_id, 'receiptId' => $receipt_id]) }}"
                method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row g-3">

                    <!-- Receipt / Voucher No. -->
                    <div class="col-sm-4">
                        <label for="reciept_no">Receipt No. <span class="text-danger">*</span></label>
                        <input type="text" name="reciept" id="reciept_no" class="form-control"
                            value="{{ old('reciept', $entry->reciept) }}" required>
                        <div id="reciept_no_warning" class="text-danger mt-1" style="display:none;">
                            Receipt No already exists
                        </div>
                        <input type="hidden" name="booking_id" value="{{ $entry->bid }}">
                        <input type="hidden" name="receipt_id" value="{{ $entry->id }}">
                    </div>

                    <!-- Date -->
                    <div class="col-sm-4">
                        <label for="date_picker">Date <span class="text-danger">*</span></label>
                        <input type="text" name="date" id="date_picker" class="form-control flatpickr"
                            value="{{ old('date', \Carbon\Carbon::parse($entry->date)->format('d-M-Y')) }}" required>
                    </div>

                    <!-- Amount -->
                    <div class="col-sm-4">
                        <label for="amount">Amount <span class="text-danger">*</span></label>
                        <input type="number" name="amount" id="amount" class="form-control"
                            value="{{ old('amount', $entry->amount) }}" step="0.01" required>
                    </div>

                    <!-- Proof Upload + Preview -->
                    {{-- <div class="col-sm-6">
                        <label>Current Proof</label>
                        <div style="margin-top:8px;">
                            @if($entry->getFirstMediaUrl('amount-proof'))
                            @php
                            $media = $entry->getFirstMedia('amount-proof');
                            $isPdf = str_contains($media->mime_type ?? '', 'pdf');
                            @endphp
                            <div class="d-inline-block position-relative">
                                @if($isPdf)
                                <img src="{{ asset('images/pdf-icon.png') }}" width="100" alt="PDF">
                                @else
                                <img src="{{ $entry->getFirstMediaUrl('amount-proof') }}" class="img-thumbnail"
                                    width="140" alt="Proof">
                                @endif
                            </div>
                            @else
                            <span class="text-muted">No proof uploaded</span>
                            @endif
                        </div>
                    </div> --}}
                    <!-- Current Proof -->
                    <div class="col-sm-6 mt-3">
                        <label class="form-label fw-bold">Current Proof</label>

                        @php
                        $media = $entry->getFirstMedia('amount-proof');
                        $hasProof = $media !== null;
                        $fileUrl = $hasProof ? $media->getUrl() : '';
                        $fileName = $hasProof ? $media->file_name : 'No proof uploaded';
                        $isPdf = $hasProof && str_contains($media->mime_type ?? '', 'pdf');
                        @endphp

                        @if($hasProof)
                        <div class="mt-2">
                            <span
                                class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-2 px-3 py-2"
                                style="cursor:pointer;"
                                onclick="openPaymentProof('{{ $fileUrl }}', '{{ addslashes($fileName) }}')">
                                <i class="la la-paperclip"></i>
                                <span class="fw-medium small text-truncate" style="max-width: 220px;">
                                    {{ Str::limit($fileName, 35) }}
                                </span>
                            </span>
                        </div>
                        @else
                        <span class="badge bg-secondary-subtle text-secondary px-3 py-2 mt-2">
                            No proof uploaded
                        </span>
                        @endif
                    </div>

                    <div class="col-sm-4">

                        <label class="form-label fw-bold">
                            Replace Proof (optional)
                        </label>

                        <input class="form-control" type="file" name="amount_proof" id="amount_proof"
                            accept=".jpg,.jpeg,.png,.pdf" onchange="handleReceiptProof(this)">

                        <small class="text-muted">
                            JPG, PNG, PDF (Max 2MB)
                        </small>

                        <div id="amount_proof_chip" class="mt-2"></div>

                    </div>

                    <!-- Buttons -->
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary px-4">Update Receipt</button>
                        <!-- Delete Receipt Button with SweetAlert -->
                        <button type="submit" name="action" value="delete" class="btn btn-danger px-4 ms-2"
                            id="deleteReceiptBtn">
                            Delete Receipt
                        </button>
                        {{-- <a href="{{ backpack_url('booking/' . $booking_id) }}"
                            class="btn btn-secondary px-4 ms-2">Cancel</a> --}}
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="proofPreviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="proofPreviewModalLabel"></h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body text-center">

                <img id="modalProofImg" style="max-width:100%; max-height:70vh; display:none;">

                <iframe id="modalProofPdf" style="width:100%; height:500px; display:none;" frameborder="0"></iframe>

            </div>

            <div class="modal-footer">

                <a id="modalDownloadLink" class="btn btn-success" download>
                    Download
                </a>

                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Close
                </button>

            </div>

        </div>
    </div>
</div>
@endsection

@push('after_scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // SweetAlert for Delete Receipt Button

    // Unified Proof Preview Function (Image + PDF)
function openPaymentProof(url, fileName = 'Proof File') {
    const modal = document.getElementById('proofPreviewModal');
    if (!modal) return;

    document.getElementById('proofPreviewModalLabel').innerText = fileName;

    const img = document.getElementById('modalProofImg');
    const pdf = document.getElementById('modalProofPdf');

    img.style.display = 'none';
    pdf.style.display = 'none';

    if (url.toLowerCase().endsWith('.pdf')) {
        pdf.src = url;
        pdf.style.display = 'block';
    } else {
        img.src = url;
        img.style.display = 'block';
    }

    document.getElementById('modalDownloadLink').href = url;
    document.getElementById('modalDownloadLink').download = fileName;

    $('#proofPreviewModal').modal('show');
}
        document.getElementById('deleteReceiptBtn')?.addEventListener('click', function(e) {
            e.preventDefault();   // form submit रोकें

            Swal.fire({
                title: 'Are you sure?',
                text: "You want to delete this receipt? This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Delete It!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Form submit कर दो
                    const form = this.closest('form');
                    if (form) {
                        form.submit();
                    }
                }
            });
        });
    function handleReceiptProof(input)
{
    if (!input.files?.[0]) return;

    const file = input.files[0];

    if (file.size > 2 * 1024 * 1024)
    {
        Swal.fire({
            icon:'error',
            title:'Invalid File',
            text:'File must be less than 2MB!'
        });

        input.value='';
        return;
    }

    const url = URL.createObjectURL(file);
    const container = document.getElementById('amount_proof_chip');

    container.innerHTML = `
        <span class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-2 px-3 py-2"
              style="cursor:pointer;"
              onclick="openProofPreview('${url}','${file.type==='application/pdf'?'pdf':'image'}','${file.name}')">

            <i class="la la-paperclip"></i>
            <span class="fw-medium small">${file.name}</span>

        </span>
    `;
}

function openProofPreview(url,type,fileName)
{
    document.getElementById('proofPreviewModalLabel').innerText = fileName;

    const img = document.getElementById('modalProofImg');
    const pdf = document.getElementById('modalProofPdf');

    img.style.display='none';
    pdf.style.display='none';

    if(type==='image')
    {
        img.src = url;
        img.style.display='block';
    }
    else
    {
        pdf.src = url;
        pdf.style.display='block';
    }

    document.getElementById('modalDownloadLink').href = url;

    $('#proofPreviewModal').modal('show');
}
    $(document).ready(function() {

    // Flatpickr
    flatpickr("#date_picker", {
        dateFormat: "d-M-Y",
        maxDate: "today",
        allowInput: true
    });

    $('#proofPreviewModal').on('show.bs.modal', function () {
    $(this).appendTo('body');
});






    // Preview new file
    function previewFile(event) {
        const file = event.target.files[0];
        if (!file) return;

        const preview = document.getElementById('imagePreview');
        const pdfIcon = document.getElementById('pdfIcon');
        const container = document.getElementById('previewContainer');

        container.style.display = 'block';

        if (file.type.startsWith('image/')) {
            preview.src = URL.createObjectURL(file);
            preview.style.display = 'block';
            pdfIcon.style.display = 'none';
        } else if (file.type === 'application/pdf') {
            preview.style.display = 'none';
            pdfIcon.style.display = 'block';
        }
    }

    // Receipt number duplicate check – same as add.blade.php
    const originalReceipt = $('#reciept_no').val();

    $('#reciept_no').on('change', function() {
        const val = $(this).val().trim();

        if (!val || val === originalReceipt) {
            $('#reciept_no_warning').hide();
            $(this).removeClass('is-invalid');
            return;
        }

        $.ajax({
            url: '{{ url("/admin/check-receipt") }}/' + encodeURIComponent(val),
            method: 'GET',
            success: function(data) {
                if (data !== 0) {
                    $('#reciept_no_warning').show();
                    $('#reciept_no').addClass('is-invalid');
                } else {
                    $('#reciept_no_warning').hide();
                    $('#reciept_no').removeClass('is-invalid');
                }
            },
            error: function() {
                alert('Error checking receipt number. Please try again.');
            }
        });
    });

    // Optional: real-time input cleanup
    $('#reciept_no').on('input', function() {
        $('#reciept_no_warning').hide();
        $(this).removeClass('is-invalid');
    });
});
</script>
@endpush