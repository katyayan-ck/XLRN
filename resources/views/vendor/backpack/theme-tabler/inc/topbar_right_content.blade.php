{{-- This file is used to store topbar (right) items --}}

<div class="d-flex align-items-center gap-3 me-3">
    {{-- ALERT ICON --}}
    <div class="dropdown">
        <a class="nav-link position-relative text-black p-0" data-bs-toggle="dropdown" href="#" role="button">
            <i class="la la-exclamation-triangle fs-2"></i>
            <span class="position-absolute badge rounded-pill"
                style="background-color: red; padding: 2px 4px; font-size: 10px; right: 10px; top: 7px; transform: revert; border-radius: 50px;">
                3
                <span class="visually-hidden">unread alerts</span>
            </span>
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0 dropdown-mobile-style" style="width: 350px;">
            <div class="dropdown-header bg-danger text-white">Alerts (3 Unread)</div>
            <div class="dropdown-body" style="max-height: 300px; overflow-y: auto;">
                <a href="#" class="dropdown-item d-flex justify-content-between align-items-center py-3 border-bottom">
                    <div>
                        <i class="la la-warning text-danger me-2"></i>
                        <strong class="text-wrap">System maintenance scheduled it is not good</strong><br>
                        <small class="text-muted">2 mins ago</small>
                    </div>
                    <button class="btn btn-sm btn-outline-primary mark-as-read" title="Mark as Read">
                        Read
                    </button>
                </a>
                <a href="#" class="dropdown-item d-flex justify-content-between align-items-center py-3 border-bottom">
                    <div>
                        <i class="la la-warning text-danger me-2"></i>
                        <strong class="text-wrap">High CPU usage detected, it is for testing and production</strong><br>
                        <small class="text-muted">1 hour ago</small>
                    </div>
                    <button class="btn btn-sm btn-outline-primary mark-as-read" title="Mark as Read">
                        Read
                    </button>
                </a>
                <a href="#" class="dropdown-item d-flex justify-content-between align-items-center py-3">
                    <div>
                        <i class="la la-warning text-danger me-2"></i>
                        <strong class="text-wrap">Security update available</strong><br>
                        <small class="text-muted">3 hours ago</small>
                    </div>
                    <button class="btn btn-sm btn-outline-primary mark-as-read" title="Mark as Read">
                        Read
                    </button>
                </a>
            </div>
            <a href="#" class="dropdown-footer text-center py-2">See All Alerts</a>
        </div>
    </div>

    {{-- NOTIFICATION ICON --}}
    <div class="dropdown">
        <a class="nav-link position-relative text-black p-0" data-bs-toggle="dropdown" href="#" role="button">
            <i class="la la-bell fs-2"></i>
            <span class="position-absolute badge rounded-pill"
                style="background-color: red; padding: 2px 4px; font-size: 10px; right: 10px; top: 7px; transform: revert; border-radius: 50px;">
                3
                <span class="visually-hidden">unread notifications</span>
            </span>
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0 dropdown-mobile-style" style="width: 350px;">
            <div class="dropdown-header bg-warning text-white">Notifications (3 Unread)</div>
            <div class="dropdown-body" style="max-height: 300px; overflow-y: auto;">
                <a href="#" class="dropdown-item d-flex justify-content-between align-items-center py-3 border-bottom">
                    <div>
                        <i class="la la-info-circle text-warning me-2"></i>
                        <strong class="text-wrap">New user registered</strong><br>
                        <small class="text-muted">5 mins ago</small>
                    </div>
                    <button class="btn btn-sm btn-outline-primary mark-as-read" title="Mark as Read">
                        Read
                    </button>
                </a>
                <a href="#" class="dropdown-item d-flex justify-content-between align-items-center py-3 border-bottom">
                    <div>
                        <i class="la la-clock text-warning me-2"></i>
                        <strong class="text-wrap">Task deadline approaching</strong><br>
                        <small class="text-muted">45 mins ago</small>
                    </div>
                    <button class="btn btn-sm btn-outline-primary mark-as-read" title="Mark as Read">
                        Read
                    </button>
                </a>
                <a href="#" class="dropdown-item d-flex justify-content-between align-items-center py-3">
                    <div>
                        <i class="la la-check-circle text-success me-2"></i>
                        <strong class="text-wrap">System update completed</strong><br>
                        <small class="text-muted">2 hours ago</small>
                    </div>
                    <button class="btn btn-sm btn-outline-primary mark-as-read" title="Mark as Read">
                        Read
                    </button>
                </a>
            </div>
            <a href="#" class="dropdown-footer text-center py-2">See All Notifications</a>
        </div>
    </div>

    {{-- INBOX ICON --}}
    <div class="dropdown">
        <a class="nav-link position-relative text-black p-0" data-bs-toggle="dropdown" href="#" role="button">
            <i class="la la-envelope fs-2"></i>
            <span class="position-absolute badge rounded-pill"
                style="background-color: red; padding: 2px 4px; font-size: 10px; right: 7px; top: 7px; transform: revert; border-radius: 50px;">
                3
                <span class="visually-hidden">unread messages</span>
            </span>
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0 dropdown-mobile-style" style="width: 350px;">
            <div class="dropdown-header bg-success text-white">Inbox (3 Unread)</div>
            <div class="dropdown-body" style="max-height: 300px; overflow-y: auto;">
                <a href="#" class="dropdown-item d-flex justify-content-between align-items-center py-3 border-bottom">
                    <div>
                        <i class="la la-envelope-open text-success me-2"></i>
                        <strong class="text-wrap">Welcome to the team!</strong><br>
                        <small class="text-muted">10 mins ago</small>
                    </div>
                    <button class="btn btn-sm btn-outline-primary mark-as-read" title="Mark as Read">
                        Read
                    </button>
                </a>
                <a href="#" class="dropdown-item d-flex justify-content-between align-items-center py-3 border-bottom">
                    <div>
                        <i class="la la-calendar text-info me-2"></i>
                        <strong class="text-wrap">Meeting reminder</strong><br>
                        <small class="text-muted">1 hour ago</small>
                    </div>
                    <button class="btn btn-sm btn-outline-primary mark-as-read" title="Mark as Read">
                        Read
                    </button>
                </a>
                <a href="#" class="dropdown-item d-flex justify-content-between align-items-center py-3">
                    <div>
                        <i class="la la-file-alt text-primary me-2"></i>
                        <strong class="text-wrap">Project update</strong><br>
                        <small class="text-muted">4 hours ago</small>
                    </div>
                    <button class="btn btn-sm btn-outline-primary mark-as-read" title="Mark as Read">
                        Read
                    </button>
                </a>
            </div>
            <a href="#" class="dropdown-footer text-center py-2">See All Messages</a>
        </div>
    </div>
</div>

{{-- Mobile पर dropdown छोटा + center में --}}
<style>
    @media (max-width: 991.98px) {
        .dropdown-mobile-style {
            width: 90vw !important;
            max-width: 340px !important;
            left: 50% !important;
            right: auto !important;
            transform: translateX(-50%) !important;
            margin-top: 12px !important;
            box-sizing: border-box;
        }
    }

    /* Text wrap for long titles */
    .text-wrap {
        word-break: break-word;
        overflow-wrap: break-word;
        white-space: normal;
    }
</style>