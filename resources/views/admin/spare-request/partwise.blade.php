@extends(backpack_view('blank'))

@section('title', 'Spare Partwise Requirement')

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

    .status-critical {
        background-color: #dc3545;
        color: white;
    }

    .status-available {
        background-color: #28a745;
        color: white;
    }

    .status-partial {
        background-color: #ffc107;
        color: black;
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
                        Spare Partwise Requirement
                    </h2>

                    <div class="d-flex align-items-center gap-3 flex-nowrap">
                        <a href="{{ backpack_url('spare-request') }}"
                            class="btn btn-secondary btn-sm fw-bold shadow-sm">
                            ← Back to Spare Requests
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
                    <div id="myGrid" class="ag-theme-quartz" style="height: calc(93vh - 220px); width:100%;"></div>
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
        { field: 'serial_no',           headerName: 'S.No',          width: 80, pinned: 'left' },
        { field: 'ro_age',              headerName: 'RO Age (Days)', width: 110, filter: true },
        { field: 'part_number',         headerName: 'Part Number',   filter: true, pinned: 'left' },
        { field: 'part_description',    headerName: 'Part Description', filter: true, minWidth: 250 },
        { field: 'total_required_qty',  headerName: 'Total Req Qty', filter: true, type: 'numericColumn' },
        { field: 'total_ro_count',      headerName: 'Total RO',      filter: true },
        { field: 'physical_stock_qty',  headerName: 'Physical Stock', filter: true },
        { field: 'mat_in_transit_qty',  headerName: 'In Transit',    filter: true },
        { field: 'back_order_qty',      headerName: 'Back Order',    filter: true },
        { field: 'total_stock_qty',     headerName: 'Total Stock',   filter: true },
        {
            field: 'status',
            headerName: 'Status',
            width: 120,
            cellRenderer: params => {
                return params.value || '<span class="badge bg-secondary">N/A</span>';
            }
        },
        { field: 'balance_qty',         headerName: 'Balance Qty',   filter: true },
        {
            field: 'action',
            headerName: 'Action',
            pinned: 'right',
            width: 120,
            sortable: false,
            filter: false,
            cellRenderer: params => params.value
        }
    ];

    const gridOptions = {
        columnDefs: columnDefs,
        rowData: [],
        pagination: true,
        paginationPageSize: 50,
        rowHeight: 42,
        animateRows: true,
        defaultColDef: {
            sortable: true,
            filter: true,
            resizable: true,
            headerClass: 'center-header',
            cellStyle: { textAlign: 'center' }
        },
        onGridReady: (params) => {
            gridApi = params.api;
            loadPartwiseData();
        }
    };

    // Load data from controller
    function loadPartwiseData() {
        fetch('{{ route("spare.partwise.data") }}')
            .then(response => response.json())
            .then(result => {
                // If you return columns + data from controller
                if (result.data) {
                    gridApi.setGridOption('rowData', result.data);
                } else {
                    gridApi.setGridOption('rowData', result);
                }
                setTimeout(() => gridApi.autoSizeAllColumns(), 300);
            })
            .catch(error => {
                console.error('Error loading data:', error);
                alert('Failed to load partwise data. Please try again.');
            });
    }

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

        // Export CSV
        document.getElementById('exportCsv').addEventListener('click', () => {
            const rows = [];
            gridApi.forEachNodeAfterFilterAndSort(node => rows.push(node.data));
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.json_to_sheet(rows);
            XLSX.utils.book_append_sheet(wb, ws, "Partwise Requirement");
            XLSX.writeFile(wb, `spare-partwise-${new Date().toISOString().slice(0,10)}.xlsx`);
        });

        // Export PDF (placeholder)
        document.getElementById('exportPdf').addEventListener('click', () => {
            alert("PDF Export feature coming soon...");
        });
    });
</script>
@endpush