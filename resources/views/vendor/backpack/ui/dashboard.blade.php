@extends(backpack_view('blank'))

@section('content')
<div class="container-fluid">

    {{-- MY PROFILE & ACCESS CARD --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white d-flex align-items-center">
                    <i class="la la-user me-2"></i>
                    <h5 class="mb-0">My Profile & Access</h5>
                    <span class="badge bg-white text-primary ms-auto">{{ $current_user_details['user_type'] ?? 'Emp' }}</span>
                </div>
                <div class="card-body">
                    <div class="row g-4">

                        <!-- Basic Info -->
                        <!-- Basic Info -->
                        <div class="col-md-3">
                            <div class="d-flex align-items-center mb-2">
                                <img src="{{ $current_user_details['profile_image'] }}" 
                                    alt="Profile Photo" 
                                    class="rounded-circle me-3 border" 
                                    width="64" height="64" style="object-fit: cover; border-color: #0D8ABC !important;">
                                
                                <div>
                                    <h5 class="mb-0">{{ $current_user_details['name'] ?? 'N/A' }}</h5>
                                    <small class="text-muted d-block">{{ $current_user_details['username'] ?? '—' }}</small>
                                </div>
                            </div>

                            <div class="mt-2">
                                <strong>Designation:</strong> {{ $current_user_details['designation'] ?? '—' }}
                            </div>
                            <div>
                                <strong>Mile ID:</strong> {{ $current_user_details['mile_id'] ?? '—' }}
                            </div>
                        </div>

                        <!-- Primary Assignment -->
                        <div class="col-md-3">
                            <strong class="text-muted d-block mb-1">Primary</strong>
                            <div>Branch: <strong>{{ $current_user_details['primary_branch'] ?? '—' }}</strong></div>
                            <div>Location: <strong>{{ $current_user_details['primary_location'] ?? '—' }}</strong></div>
                            <div>Department: <strong>{{ $current_user_details['primary_department'] ?? '—' }}</strong></div>
                            <div>Division: <strong>{{ $current_user_details['primary_division'] ?? '—' }}</strong></div>
                            <div>Vertical: <strong>{{ $current_user_details['vertical'] ?? '—' }}</strong></div>
                            <div>Segment: <strong>{{ $current_user_details['segment'] ?? '—' }}</strong></div>
                            <div>Sub Segment: <strong>{{ $current_user_details['sub_segment'] ?? '—' }}</strong></div>
                        </div>

                        <!-- Primary Contact -->
                        <div class="col-md-3">
                            <strong class="text-muted d-block mb-1">Primary Contact</strong>
                            <div>Mobile: <strong>{{ $current_user_details['primary_mobile'] ?? '—' }}</strong></div>
                            <div>Email: <strong>{{ $current_user_details['primary_email'] ?? '—' }}</strong></div>
                            <div>Address: <strong>{{ $current_user_details['primary_address'] ?? '—' }}</strong></div>
                            <div>Banking: <strong>{{ $current_user_details['primary_banking'] ?? '—' }}</strong></div>
                        </div>

                        <!-- All Access -->
                        <div class="col-md-3">
                            <strong class="text-muted d-block mb-1">All Access</strong>
                            <div>Branches: <strong>{{ implode(', ', $current_user_details['all_branches'] ?? []) ?: '—' }}</strong></div>
                            <div>Locations: <strong>{{ implode(', ', $current_user_details['all_locations'] ?? []) ?: '—' }}</strong></div>
                            <div>Departments: <strong>{{ implode(', ', $current_user_details['all_departments'] ?? []) ?: '—' }}</strong></div>
                            <div>Divisions: <strong>{{ implode(', ', $current_user_details['all_divisions'] ?? []) ?: '—' }}</strong></div>
                            <div>Verticals: <strong>{{ implode(', ', $current_user_details['all_verticals'] ?? []) ?: '—' }}</strong></div>
                            <div>Segments: <strong>{{ implode(', ', $current_user_details['all_segments'] ?? []) ?: '—' }}</strong></div>
                            <div>Sub Segments: <strong>{{ implode(', ', $current_user_details['all_sub_segments'] ?? []) ?: '—' }}</strong></div>
                            <div>Models: <strong>{{ implode(', ', $current_user_details['all_models'] ?? []) ?: '—' }}</strong></div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Bikaner Motors Analytics</h2>
            <small class="text-muted">System performance overview</small>
        </div>
        <span class="badge bg-success px-3 py-2">Auto Refresh</span>
    </div>

    {{-- METRIC CARDS & CHARTS (unchanged) --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6"><div class="stat-card primary"><p>Total Vehicles</p><h3 id="vehicles">129</h3></div></div>
        <div class="col-xl-3 col-md-6"><div class="stat-card success"><p>Monthly Revenue</p><h3 id="sales">₹ 6.25 L</h3></div></div>
        <div class="col-xl-3 col-md-6"><div class="stat-card warning"><p>Pending Services</p><h3 id="services">17</h3></div></div>
        <div class="col-xl-3 col-md-6"><div class="stat-card danger"><p>Insurance Expiry</p><h3 id="insurance">5</h3></div></div>
    </div>

    <!-- CHARTS remain exactly as in your current blade file -->
    <div class="row g-3 mb-4">
        <div class="col-lg-8"><div class="card shadow-sm"><div class="card-header fw-semibold">Revenue Growth</div><div class="card-body"><canvas id="revenueChart" height="120"></canvas></div></div></div>
        <div class="col-lg-4"><div class="card shadow-sm"><div class="card-header fw-semibold">Vehicle Category</div><div class="card-body"><canvas id="vehicleChart"></canvas></div></div></div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6"><div class="card shadow-sm"><div class="card-header fw-semibold">Daily Service Load</div><div class="card-body"><canvas id="serviceChart"></canvas></div></div></div>
        <div class="col-lg-6"><div class="card shadow-sm"><div class="card-header fw-semibold">Sales Channel Split</div><div class="card-body"><canvas id="channelChart"></canvas></div></div></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6"><div class="card shadow-sm"><div class="card-header fw-semibold">Weekly Bookings</div><div class="card-body"><canvas id="bookingChart"></canvas></div></div></div>
        <div class="col-lg-6"><div class="card shadow-sm"><div class="card-header fw-semibold">Payment Status</div><div class="card-body"><canvas id="paymentChart"></canvas></div></div></div>
    </div>

</div>
@endsection

@push('after_styles')
<style>
    .stat-card { padding:20px; border-radius:14px; color:#fff; box-shadow:0 10px 25px rgba(0,0,0,.1); }
    .primary  { background:linear-gradient(135deg,#1e3c72,#2a5298); }
    .success  { background:linear-gradient(135deg,#11998e,#38ef7d); }
    .warning  { background:linear-gradient(135deg,#f7971e,#ffd200); }
    .danger   { background:linear-gradient(135deg,#cb2d3e,#ef473a); }
</style>
@endpush

@push('after_scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Your existing chart scripts (unchanged)
</script>
@endpush
