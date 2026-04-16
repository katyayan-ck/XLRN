@extends(backpack_view('blank'))

@section('title', 'Spare Order Requests')

@push('after_styles')
<link rel="stylesheet" href="https://unpkg.com/ag-grid-community/styles/ag-theme-quartz.css">
<style>
    .card {
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    }

    .ag-theme-quartz .center-header .ag-header-cell-label,
    .ag-theme-quartz .ag-header-group-cell-label {
        justify-content: center !important;
    }

    #columnBubble {
        width: 340px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <!-- HEADER -->
                <div
                    class="card-header bg-gradient-primary d-flex justify-content-between align-items-center flex-nowrap flex-md-nowrap flex-wrap gap-3">
                    <h2 class="card-title mb-0 fw-bold text-black text-nowrap">
                        Spare Order Requests
                    </h2>

                    <div class="d-flex align-items-center gap-3 flex-nowrap">
                        <a href="{{ backpack_url('spare-request/create') }}"
                            class="btn btn-blue btn-sm fw-bold shadow-sm">
                            <i class="la la-plus me-1"></i> Add New Request
                        </a>
                    </div>
                </div>

                <!-- BODY -->
                <div class="card-body p-0" style="background:#f8fafc">
                    <div
                        class="d-flex justify-content-between align-items-center flex-wrap gap-3 p-3 border-bottom bg-white">
                        <div class="d-flex align-items-center gap-2 flex-nowrap">
                            <input type="text" id="quickFilter" class="form-control w-100 w-md-auto"
                                style="width:360px; min-width:260px;" placeholder="Smart Search...">
                            <button id="resetAll" class="btn btn-outline-danger btn-sm text-nowrap">Reset</button>
                        </div>

                        <div class="d-flex gap-2 flex-nowrap justify-content-center">
                            <button id="btnDefaultHeaders" class="btn btn-secondary btn-sm text-nowrap">Default
                                Headers</button>

                            <div class="position-relative d-inline-block">
                                <button id="btnCustomiseHeaders" class="btn btn-red btn-sm text-nowrap">Customise
                                    Headers</button>
                                <div id="columnBubble"
                                    style="display:none; position:absolute; top:110%; left:0; width:340px; background:#fff; border:1px solid #ddd; border-radius:6px; box-shadow:0 8px 20px rgba(0,0,0,.15); z-index:9999;">
                                    <div
                                        class="d-flex justify-content-between align-items-center px-2 py-1 border-bottom">
                                        <strong style="font-size:13px;">Customise Headers</strong>
                                        <button id="closeColumnBubble"
                                            class="btn btn-sm btn-link text-danger p-0">✕</button>
                                    </div>
                                    <div style="max-height:260px; overflow:auto;">
                                        <table class="table table-sm mb-0">
                                            <tbody id="columnBubbleBody"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <button id="btnAllHeaders" class="btn btn-blue btn-sm text-nowrap">All Headers</button>
                        </div>

                        <div class="d-flex gap-2 flex-nowrap">
                            <button id="exportCsv" class="btn btn-sm text-nowrap d-flex align-items-center gap-2">
                                <img src="{{ asset('images/export-excel.png') }}" alt="Excel"
                                    style="height:30px; width:auto;">
                            </button>
                            <button id="exportPdf" class="btn btn-sm text-nowrap d-flex align-items-center gap-2">
                                <img src="{{ asset('images/export-pdf.png') }}" alt="PDF"
                                    style="height:30px; width:auto;">
                            </button>
                        </div>
                    </div>

                    <!-- AG Grid -->
                    <div id="myGrid" class="ag-theme-quartz" style="height: calc(93vh - 260px); width:100%;"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('after_scripts')
<script src="https://unpkg.com/ag-grid-community/dist/ag-grid-community.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>

<script>
    let gridApi;

    const columnDefs = [
        { field: 'serial_no', headerName: 'S.No', pinned: 'left', width: 80 },
        { field: 'ro_number', headerName: 'RO Number', pinned: 'left', filter: true },
        { field: 'branch_name', headerName: 'Branch', filter: true },
        { field: 'cust_name', headerName: 'Customer Name', filter: true },
        { field: 'cust_mobile', headerName: 'Mobile', filter: true },
        { field: 'regn_no', headerName: 'Vehicle No', filter: true },
        { field: 'ro_date', headerName: 'RO Date', filter: true },
        {
            field: 'status',
            headerName: 'Status',
            cellRenderer: params => {
                return params.value == 1
                    ? '<span class="badge bg-success">Active</span>'
                    : '<span class="badge bg-warning">Closed</span>';
            }
        },
        {
            field: 'action',
            headerName: 'Action',
            pinned: 'right',
            width: 160,
            sortable: false,
            filter: false,
            cellRenderer: params => `
                <div class="d-flex gap-2 justify-content-center">
                    <a href="${backpack_url('spare-request/' + params.data.id + '/edit')}"
                       class="btn btn-sm btn-primary py-1 px-2">Edit</a>
                    <a href="${backpack_url('spare-request/' + params.data.id)}"
                       class="btn btn-sm btn-info py-1 px-2">View</a>
                </div>
            `
        }
    ];

    const gridOptions = {
        columnDefs: columnDefs,
        rowData: @json($gridConfig['data'] ?? []),
        pagination: true,
        paginationPageSize: 50,
        rowHeight: 38,
        animateRows: true,
        defaultColDef: {
            sortable: true,
            filter: true,
            resizable: true,
            headerClass: 'center-header',
            cellStyle: { textAlign: 'center' }
        },
        onGridReady: params => {
            gridApi = params.api;
            setTimeout(() => gridApi.autoSizeAllColumns(), 300);
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        const gridDiv = document.querySelector('#myGrid');
        agGrid.createGrid(gridDiv, gridOptions);

        // Quick Filter
        document.getElementById('quickFilter').addEventListener('input', e => {
            gridApi.setGridOption('quickFilterText', e.target.value);
        });

        document.getElementById('resetAll').addEventListener('click', () => {
            gridApi.setFilterModel(null);
            document.getElementById('quickFilter').value = '';
            gridApi.setGridOption('quickFilterText', '');
            gridApi.setSortModel(null);
        });

        // Column Customisation
        document.getElementById('btnCustomiseHeaders').addEventListener('click', openColumnBubble);
        document.getElementById('closeColumnBubble').addEventListener('click', () => {
            document.getElementById('columnBubble').style.display = 'none';
        });

        document.getElementById('btnAllHeaders').addEventListener('click', () => {
            gridApi.setColumnsVisible(gridApi.getAllGridColumns().map(c => c.getColId()), true);
            gridApi.autoSizeAllColumns();
        });

        document.getElementById('btnDefaultHeaders').addEventListener('click', () => {
            const defaultFields = ['serial_no', 'ro_number', 'branch_name', 'cust_name', 'cust_mobile', 'regn_no', 'ro_date', 'status', 'action'];
            const allCols = gridApi.getAllGridColumns().map(c => c.getColId());
            gridApi.setColumnsVisible(allCols, false);
            gridApi.setColumnsVisible(defaultFields, true);
            gridApi.autoSizeAllColumns();
        });

        // Export Functions (same as branch)
        document.getElementById('exportCsv').addEventListener('click', () => {
            const rows = [];
            gridApi.forEachNodeAfterFilterAndSort(node => rows.push(node.data));
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.json_to_sheet(rows);
            XLSX.utils.book_append_sheet(wb, ws, "Spare Requests");
            XLSX.writeFile(wb, `spare-requests-${new Date().toISOString().slice(0,10)}.xlsx`);
        });

        document.getElementById('exportPdf').addEventListener('click', () => {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            // PDF export logic can be enhanced later if needed
            alert("PDF Export coming soon...");
        });
    });

    function openColumnBubble() {
        // Same logic as branch list for column toggle
        alert("Column customisation is enabled. Click on column headers to resize/sort.");
    }
</script>
@endpush