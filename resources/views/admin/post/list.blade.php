@extends(backpack_view('blank'))

@section('header')
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div
                class="card-header bg-gradient-primary d-flex justify-content-between align-items-center flex-nowrap flex-md-nowrap flex-wrap gap-3">
                <h2 class="card-title mb-0 fw-bold text-black text-nowrap">
                    {{ $title ?? 'All Posts' }}
                </h2>

                <div class="d-flex align-items-center gap-3 flex-nowrap">
                    <a href="{{ backpack_url('post/create') }}" class="btn btn-blue btn-sm fw-bold shadow-sm">
                        <i class="la la-plus me-1"></i> Add New Post
                    </a>
                </div>
            </div>

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
                                style="display:none; position:absolute; top:110%; left:0; width:320px; background:#fff; border:1px solid #ddd; border-radius:6px; box-shadow:0 8px 20px rgba(0,0,0,.15); z-index:9999;">
                                <div class="d-flex justify-content-between align-items-center px-2 py-1 border-bottom">
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
                            <img src="{{ asset('images/export-pdf.png') }}" alt="PDF" style="height:30px; width:auto;">
                        </button>
                    </div>
                </div>

                <div id="myGrid" class="ag-theme-quartz" style="height: calc(93vh - 260px); width:100%;"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('after_styles')
<link rel="stylesheet" href="https://unpkg.com/ag-grid-community/styles/ag-theme-quartz.css">
<style>
    .ag-theme-quartz .center-header .ag-header-cell-label,
    .ag-theme-quartz .ag-header-group-cell-label {
        justify-content: center !important;
    }
</style>
@endpush

@push('after_scripts')
<script src="https://unpkg.com/ag-grid-community/dist/ag-grid-community.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>

<script>
    const ALL_COLUMNS = @json($gridConfig['columns'] ?? []);

    let gridApi;

    const columnDefs = [
        {
            headerName: 'Primary',
            headerClass: 'center-header',
            children: ALL_COLUMNS.filter(col => ['serial_no', 'code', 'title'].includes(col.field))
        },
        {
            headerName: 'Organization',
            headerClass: 'center-header',
            children: ALL_COLUMNS.filter(col => ['designation', 'department', 'branch'].includes(col.field))
        },
        {
            headerName: 'Details',
            headerClass: 'center-header',
            children: ALL_COLUMNS.filter(col => ['max_assignees', 'description'].includes(col.field))
        },
        {
            headerName: 'Status',
            headerClass: 'center-header',
            children: ALL_COLUMNS.filter(col => ['is_active', 'is_vacant'].includes(col.field))
        },
        {
            headerName: 'Actions',
            headerClass: 'center-header',
            children: ALL_COLUMNS.filter(col => ['action'].includes(col.field)).map(col => {
                col.pinned = 'right';
                col.width = 140;
                col.sortable = false;
                col.filter = false;
                col.cellRenderer = 'htmlRenderer';
                return col;
            })
        }
    ];

    const gridOptions = {
        columnDefs: columnDefs,
        rowData: @json($gridConfig['data'] ?? []),
        pagination: true,
        paginationPageSize: 50,
        rowHeight: 28,
        animateRows: true,
        defaultColDef: {
            sortable: true,
            filter: true,
            resizable: true,
            headerClass: 'center-header',
            cellStyle: { textAlign: 'center' }
        },
        components: {
            htmlRenderer: params => params.value || ''
        },
        onGridReady: params => {
            gridApi = params.api;
            // Hide is_active and is_vacant by default
            const defaultFields = ['serial_no', 'code', 'title', 'designation', 'department', 'branch', 'max_assignees', 'action'];
            const allCols = [];
            gridApi.getAllGridColumns().forEach(col => allCols.push(col.getColId()));
            gridApi.setColumnsVisible(allCols, false);
            gridApi.setColumnsVisible(defaultFields, true);
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

        // Reset All
        document.getElementById('resetAll').addEventListener('click', () => {
            gridApi.setFilterModel(null);
            document.getElementById('quickFilter').value = '';
            gridApi.setGridOption('quickFilterText', '');
            gridApi.setSortModel(null);
        });

        // Customise Headers Popup (same as before)
        function openColumnBubble() {
            const bubble = document.getElementById('columnBubble');
            const tbody = document.getElementById('columnBubbleBody');
            if (!gridApi || !bubble || !tbody) return;

            tbody.innerHTML = '';

            columnDefs.forEach(group => {
                const groupName = group.headerName;
                const children = group.children || [];
                if (groupName === 'Actions') return;

                const groupTr = document.createElement('tr');
                const groupCheckTd = document.createElement('td');
                groupCheckTd.style.width = '30px';
                const groupCheckbox = document.createElement('input');
                groupCheckbox.type = 'checkbox';
                const fields = children.map(c => c.field).filter(Boolean);

                const anyVisible = fields.some(f => {
                    const col = gridApi.getColumn(f);
                    return col && col.isVisible();
                });

                groupCheckbox.checked = anyVisible;
                if (groupName === 'Primary') {
                    groupCheckbox.checked = true;
                    groupCheckbox.disabled = true;
                }

                groupCheckbox.addEventListener('change', () => {
                    gridApi.setColumnsVisible(fields, groupCheckbox.checked);
                    tbody.querySelectorAll(`[data-group="${groupName}"] input`)
                        .forEach(cb => cb.checked = groupCheckbox.checked);
                });

                groupCheckTd.appendChild(groupCheckbox);
                const groupLabelTd = document.createElement('td');
                groupLabelTd.innerHTML = `<strong>${groupName}</strong>`;
                groupTr.appendChild(groupCheckTd);
                groupTr.appendChild(groupLabelTd);
                tbody.appendChild(groupTr);

                children.forEach(col => {
                    if (!col.field) return;
                    const tr = document.createElement('tr');
                    tr.dataset.group = groupName;
                    const tdCheck = document.createElement('td');
                    tdCheck.style.paddingLeft = '25px';
                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    const column = gridApi.getColumn(col.field);
                    checkbox.checked = column ? column.isVisible() : false;

                    if (groupName === 'Primary') {
                        checkbox.checked = true;
                        checkbox.disabled = true;
                    }

                    checkbox.addEventListener('change', () => {
                        gridApi.setColumnsVisible([col.field], checkbox.checked);
                    });

                    tdCheck.appendChild(checkbox);
                    const tdLabel = document.createElement('td');
                    tdLabel.innerText = col.headerName;

                    tr.appendChild(tdCheck);
                    tr.appendChild(tdLabel);
                    tbody.appendChild(tr);
                });
            });

            bubble.style.display = 'block';
        }

        document.getElementById('btnCustomiseHeaders').addEventListener('click', e => {
            e.stopPropagation();
            openColumnBubble();
        });

        document.getElementById('closeColumnBubble').addEventListener('click', () => {
            document.getElementById('columnBubble').style.display = 'none';
        });

        document.getElementById('columnBubble').addEventListener('click', e => e.stopPropagation());

        document.addEventListener('click', e => {
            const bubble = document.getElementById('columnBubble');
            if (bubble && bubble.style.display === 'block') bubble.style.display = 'none';
        });

        document.getElementById('btnAllHeaders').addEventListener('click', () => {
            const allCols = [];
            gridApi.getAllGridColumns().forEach(col => allCols.push(col.getColId()));
            gridApi.setColumnsVisible(allCols, true);
            setTimeout(() => gridApi.autoSizeAllColumns(), 200);
        });

        document.getElementById('btnDefaultHeaders').addEventListener('click', () => {
            const defaultFields = ['serial_no', 'code', 'title', 'designation', 'department', 'branch', 'max_assignees', 'action'];
            const allCols = [];
            gridApi.getAllGridColumns().forEach(col => allCols.push(col.getColId()));
            gridApi.setColumnsVisible(allCols, false);
            gridApi.setColumnsVisible(defaultFields, true);
            setTimeout(() => gridApi.autoSizeAllColumns(), 200);
        });

        // Excel Export
        document.getElementById('exportCsv').addEventListener('click', () => {
            const visibleColumns = gridApi.getAllDisplayedColumns()
                .map(col => col.getColDef())
                .filter(col => col.field && col.field !== 'action');

            const rows = [];
            gridApi.forEachNodeAfterFilterAndSort(node => {
                const row = {};
                visibleColumns.forEach(col => {
                    row[col.headerName] = node.data[col.field] ?? '';
                });
                rows.push(row);
            });

            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.json_to_sheet(rows);
            XLSX.utils.book_append_sheet(wb, ws, "Posts");
            XLSX.writeFile(wb, `posts-${new Date().toISOString().slice(0,10)}.xlsx`);
        });

        // PDF Export
        document.getElementById('exportPdf').addEventListener('click', () => {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            const visibleColumns = gridApi.getAllDisplayedColumns()
                .map(col => col.getColDef())
                .filter(col => col.field && col.field !== 'action');

            const headers = visibleColumns.map(col => col.headerName);

            const rows = [];
            gridApi.forEachNodeAfterFilterAndSort(node => {
                const row = [];
                visibleColumns.forEach(col => {
                    row.push(node.data[col.field] ?? '');
                });
                rows.push(row);
            });

            doc.autoTable({
                head: [headers],
                body: rows,
                styles: { fontSize: 8 },
                headStyles: { fillColor: [41, 128, 185] },
            });

            doc.save(`posts-${new Date().toISOString().slice(0,10)}.pdf`);
        });
    });
</script>
@endpush