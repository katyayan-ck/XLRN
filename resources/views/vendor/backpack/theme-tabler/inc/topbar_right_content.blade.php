<div class="d-flex align-items-center gap-3 me-3">

    {{-- ALERTS --}}
    <div class="dropdown">
        <a class="nav-link p-1 text-dark" data-bs-toggle="dropdown" href="#" role="button" title="Alerts">
            <div class="position-relative d-inline-flex align-items-center justify-content-center" 
                 style="width:38px; height:38px; background:#fff3cd; border-radius:50%;">
                <i class="la la-exclamation-triangle fs-5 text-warning"></i>
                <span class="position-absolute bottom-0 end-0 badge rounded-circle bg-danger text-white fw-bold d-flex align-items-center justify-content-center"
                      style="width:17px; height:17px; font-size:9.5px; transform:translate(30%,30%);">6</span>
            </div>
        </a>
        <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-0 rounded-3" style="width: 340px;">
            <!-- Header -->
            <div class="d-flex align-items-center justify-content-between px-3 py-2 bg-danger text-white fs-6">
                <div class="d-flex align-items-center">
                    <i class="la la-exclamation-triangle me-2"></i>
                    <strong>Alerts (6 unread)</strong>
                </div>
                <button class="btn btn-sm btn-light text-danger px-2 py-px fs-11 fw-medium mark-all-read">Mark all read</button>
            </div>
            
            <div class="dropdown-body py-1" style="max-height: 265px; overflow-y: auto;">
                <!-- Your existing 6 alert items here -->
            </div>
            
            <div class="px-3 py-2 border-top text-center">
                <a href="#" class="btn btn-sm btn-light w-100 rounded-2 fw-medium">See All Alerts</a>
            </div>
        </div>
    </div>

    {{-- NOTIFICATIONS --}}
    <div class="dropdown">
        <a class="nav-link p-1 text-dark" data-bs-toggle="dropdown" href="#" role="button" title="Notifications">
            <div class="position-relative d-inline-flex align-items-center justify-content-center" 
                 style="width:38px; height:38px; background:#e7f5ff; border-radius:50%;">
                <i class="la la-bell fs-5 text-primary"></i>
                <span class="position-absolute bottom-0 end-0 badge rounded-circle bg-warning text-white fw-bold d-flex align-items-center justify-content-center"
                      style="width:17px; height:17px; font-size:9.5px; transform:translate(30%,30%);">5</span>
            </div>
        </a>
        <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-0 rounded-3" style="width: 340px;">
            <!-- Header -->
            <div class="d-flex align-items-center justify-content-between px-3 py-2 bg-warning text-white fs-6">
                <div class="d-flex align-items-center">
                    <i class="la la-bell me-2"></i>
                    <strong>Notifications (5 unread)</strong>
                </div>
                <button class="btn btn-sm btn-light text-warning px-2 py-px fs-11 fw-medium mark-all-read">Mark all read</button>
            </div>
            
            <div class="dropdown-body py-1" style="max-height: 265px; overflow-y: auto;">
                <!-- Your existing notification items here -->
            </div>
            
            <div class="px-3 py-2 border-top text-center">
                <a href="#" class="btn btn-sm btn-light w-100 rounded-2 fw-medium">See All Notifications</a>
            </div>
        </div>
    </div>

    {{-- INBOX --}}
    <div class="dropdown">
        <a class="nav-link p-1 text-dark" data-bs-toggle="dropdown" href="#" role="button" title="Inbox">
            <div class="position-relative d-inline-flex align-items-center justify-content-center" 
                 style="width:38px; height:38px; background:#d4edda; border-radius:50%;">
                <i class="la la-envelope fs-5 text-success"></i>
                <span class="position-absolute bottom-0 end-0 badge rounded-circle bg-success text-white fw-bold d-flex align-items-center justify-content-center"
                      style="width:17px; height:17px; font-size:9.5px; transform:translate(30%,30%);">6</span>
            </div>
        </a>
        <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-0 rounded-3" style="width: 340px;">
            <!-- Header -->
            <div class="d-flex align-items-center justify-content-between px-3 py-2 bg-success text-white fs-6">
                <div class="d-flex align-items-center">
                    <i class="la la-envelope me-2"></i>
                    <strong>Inbox (6 unread)</strong>
                </div>
                <button class="btn btn-sm btn-light text-success px-2 py-px fs-11 fw-medium mark-all-read">Mark all read</button>
            </div>
            
            <div class="dropdown-body py-1" style="max-height: 265px; overflow-y: auto;">
                <!-- Your existing inbox items here -->
            </div>
            
            <div class="px-3 py-2 border-top text-center">
                <a href="#" class="btn btn-sm btn-light w-100 rounded-2 fw-medium">See All Messages</a>
            </div>
        </div>
    </div>

</div>
