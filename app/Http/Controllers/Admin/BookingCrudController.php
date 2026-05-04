<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use App\Http\Requests\BookingRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\Admin\Cache;
use DataTables;
use Illuminate\Validation\Rule;
use App\Http\Requests\MyBookingRequest; // We'll create this next
use App\Services\BookingService;             // New service class

use Illuminate\Http\JsonResponse;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Module\Booking\Booking;
use App\Models\Bookingamount;
use App\Models\XVehicleMaster; // Import the model
use App\Models\Stock; // Import the Stock model
use App\Models\Branches; // Import the Branch model
use App\Models\Xessories;
use App\Models\Xl_Refunds;
use App\Models\Xl_DSA_Master;
use App\Models\X_Branch;
use App\Models\X_Location;
use App\Models\X_Vh_Stock;
use App\Models\X_Vh_Order;
use App\Models\EnumMaster;
use App\Models\PinCodes;
use App\Models\XExchange;
use App\Models\XFinance;
use App\Models\XlInsurer;
use Illuminate\Support\Facades\Log;

use App\Models\XlRto;

use App\Models\XlDelivery;

use App\Models\XlInsurance;

use App\Models\Module\Finance\XlFinancier;
use App\Models\XlRtoRules;

use App\Helpers\CommonHelper;
use App\Helpers\XCommonHelper;
use App\Helpers\XpricingHelper;

use App\Helpers\ChatHelper;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class BookingCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
{
    // FIXED: Correct namespace
    CRUD::setModel(\App\Models\Module\Booking\Booking::class);
    
    CRUD::setRoute(config('backpack.base.route_prefix') . '/booking');
    CRUD::setEntityNameStrings('booking', 'bookings');
}


    /**
     * Centralized method to prepare complete booking data for show/edit/invoice views
     *
     * @param int $id Booking ID
     * @param string $viewName Blade view name suffix (default: 'view')
     * @return \Illuminate\View\View
     */
    private function getFullBookingData(int $id, string $viewName = 'view'): \Illuminate\View\View
    {
        Log::info('getFullBookingData STARTED', ['booking_id' => $id, 'view' => $viewName]);
        $booking = Booking::findOrFail($id);
        $booking->segment_name = 'N/A';
        if ($booking->segment_id) {
            $segment = \App\Models\EnumMaster::where('id', $booking->segment_id)
                ->value('value');  // sirf name chahiye to value() best

            $booking->segment_name = $segment ?? 'N/A (ID not found)';
        }
        $data = [
            'booking'           => $booking,
            'uid'               => Auth::id(),
            'dsaname'           => 'N/A',
            'comm'              => ChatHelper::get_communication(3, $id),
            'receiptLogs'       => Bookingamount::where('bid', $id)
                ->select('id', 'date', 'reciept', 'amount')
                ->orderBy('date', 'desc')
                ->get(),
            'total_amount'      => 0,
            'finance'           => XFinance::where('bid', $id)->first(),
            'delivery'          => XlDelivery::where('bid', $id)->first(),
            'insurance'         => XlInsurer::where('bid', $id)->first(),
            'rto'               => XlRto::where('bid', $id)->first(),
            'refund'            => null,
            'deduction'         => 0,
            'acc_proof'         => '',
            'aadhar'            => '',
            'pan'               => '',
            'pay_proof'         => '',
            'amount'            => $booking->booking_amount ?? 0,
            'accessories'       => 'N/A',
            'bchasis'           => 'Not Available',
            'chassis'           => [],
            'collector_name'    => 'N/A',
            'make1'             => 'N/A',
            'make2'             => 'N/A',
            'branch'            => 'N/A',
            'fbranch'           => 'N/A',
            'location'          => 'N/A',
            'flocation'         => 'N/A',

            'segments'          => XpricingHelper::getSegments() ?? [],
            'models'            => XpricingHelper::getModelsX() ?? [],
            'variants'          => XpricingHelper::getVehiclesX() ?? [],
            'colors'            => XpricingHelper::getColorX($booking->variant ?? null) ?? [],
            'saleconsultants'   => XpricingHelper::selectfsc() ?? [],
            'allusers'          => XpricingHelper::selectUsers() ?? [],
            'financiers'        => [],
            'insurances'        => [],
            'rto_rules'         => [],
            'dsa_details'       => [],
            'branches'          => CommonHelper::getBranches() ?? [],
            'locations'         => [],
            'accessories_dropdown' => [],
            'enum_master'       => [],
            'oem_ids'           => array_filter(explode(',', $booking->exist_oem ?? '')),

            'trade_used_map' => [
                '1' => 'BKN AD User 1 (RJ0730024TC)',
                '2' => 'BKN AD User 2 (RJ0730024TC)',
                '3' => 'BKN AD User 3 (RJ0730024TC)',
                '4' => 'SUJ AD (RJ44C0012TC)',
                '5' => 'BKN LMM L5 (RJ07C0056TC)',
                '6' => 'BKN LMM L3 (RJ07TC0322)',
            ],
            'sale_type_map' => [
                '1' => 'Within State',
                '2' => 'Outside State',
            ],
            'permit_map' => [
                '1' => 'Private - U/C (4 Wheeler)',
                '2' => 'Private - BH (4 Wheeler)',
                '3' => 'Private - EV (4 Wheeler)',
                '4' => 'Goods - G (4 Wheeler)',
                '5' => 'Goods - G 3 Ton+ (4 Wheeler)',
                '6' => 'Goods - G (3 Wheeler)',
                '7' => 'Goods - G EV (3 Wheeler)',
                '8' => 'Taxi - T (4 Wheeler)',
                '9' => 'Passenger - P (3 Wheeler)',
                '10' => 'Passenger - P EV (3 Wheeler)',
                '11' => 'Ambulance (Misc.)',
            ],
            'body_type_map' => [
                '1' => 'Complete',
                '2' => 'CBC',
            ],
            'reg_no_type_map' => [
                '1' => 'Regular',
                '2' => 'BH',
                '3' => 'Special',
            ],
            'registration_type_map' => [
                '1' => 'Type 1',
                '2' => 'Type 2',
                '3' => 'Type 3',
            ],
        ];

        // Total amount
        $data['total_amount'] = $data['receiptLogs']->sum('amount');

        // Branch & Location
        $data['branch']   = X_Branch::find($booking->branch_code)?->name ?? 'N/A';
        $data['fbranch']  = $data['branch'];
        $data['location'] = $booking->location_code
            ? (X_Location::find($booking->location_code)?->name ?? 'N/A')
            : ($booking->location_other ?? 'N/A');
        $data['flocation'] = $data['location'];
        $segmentsFromHelper = XpricingHelper::getSegments() ?? [];

        // Accessories
        $accIds = array_filter(array_map('trim', explode(',', $booking->accessories ?? '')));
        $accNames = [];
        foreach ($accIds as $accId) {
            if ($accessory = Xessories::find($accId)) {
                $accNames[] = $accessory->item;
            }
        }
        $data['accessories'] = $accNames ? implode(', ', $accNames) : 'N/A';

        // Chassis
        $data['chassis'] = Stock::where('model_code', 'code')->select('chasis_no', 'id')->get()->toArray();
        $cr = Stock::find($booking->chasis_no);
        if ($cr) {
            $data['bchasis'] = $cr->chasis_no;
            $data['chassis'] = Stock::where('model_code', $cr->model_code)->select('chasis_no', 'id')->get()->toArray();
        }

        // Collector name
        $data['collector_name'] = match ((int)$booking->col_type) {
            1 => 'N/A',
            2 => User::find($booking->col_by)?->name . ' - (' . (User::find($booking->col_by)?->emp_code ?? '') . ')' ?? 'N/A',
            3 => Xl_Dsa_Master::find($booking->col_by)?->name . ' - ' . Xl_Dsa_Master::find($booking->col_by)?->mobile ?? 'N/A',
            default => 'N/A',
        };

        // DSA
        $drec = Xl_Dsa_Master::find($booking->dsa_id);
        $data['dsaname'] = $drec ? $drec->name . ' - ' . $drec->mobile : 'N/A';

        // Makes
        $data['make1'] = CommonHelper::enumValueById($booking->exist_oem1) ?? 'N/A';
        $data['make2'] = CommonHelper::enumValueById($booking->exist_oem2) ?? 'N/A';

        // ────────────────────────────────────────────────
        // STATIC DATA – CACHE HATAA DIYA
        // ────────────────────────────────────────────────
        $data['financiers'] = XlFinancier::select('id', 'name', 'short_name')
            ->get()
            ->toArray() ?? [];

        $data['insurances'] = XlInsurance::select('id', 'name', 'short_name')
            ->get()
            ->toArray() ?? [];

        $data['rto_rules'] = XlRtoRules::select(
            'sale_type',
            'permit',
            'body_type',
            'reg_no_type',
            'trc_number',
            'trc_pay',
            'trc_copy',
            'app_no',
            'tax_pay',
            'veh_reg',
            'tax_copy'
        )->get()->toArray() ?? [];

        $data['dsa_details'] = Xl_Dsa_Master::all()
            ->map(fn($dsa) => [
                'id'       => $dsa->id,
                'name'     => $dsa->name,
                'mobile'   => $dsa->mobile,
                'email'    => $dsa->email,
                'location' => $dsa->dlocation,
            ])->toArray() ?? [];

        // Locations for edit/create
        $locations = XCommonHelper::getLocations($booking->branch_code) ?? [];
        usort($locations, fn($a, $b) => strcmp(
            ($a['name'] ?? '') . ' - ' . ($a['code'] ?? ''),
            ($b['name'] ?? '') . ' - ' . ($b['code'] ?? '')
        ));
        $data['locations'] = $locations;

        $data['accessories_dropdown'] = XpricingHelper::getAccessories(
            CommonHelper::enumValueById($booking->segment_id ?? 0) ?? '',
            $booking->model ?? '',
            $booking->variant ?? ''
        ) ?? [];

        $data['enum_master'] = EnumMaster::where('master_id', 94)
            ->select('id', 'value')
            ->get() ?? [];


        $refund = Xl_Refunds::where('entity_type', 'booking')
            ->where('entity_id', $id)
            ->latest('id')
            ->first();

        if ($refund) {

            $data['amount']    = $booking->booking_amount ?? 0;
            $data['deduction'] = $data['amount'] - ($refund->amount ?? 0);

            $data['refund'] = [
                'remaining_amount'   => $refund->amount ?? 0,
                'bank_name'          => $refund->bank_name ?? 'N/A',
                'branch_name'        => $refund->branch_name ?? 'N/A',
                'account_type'       => $refund->account_type ?? 'N/A',
                'account_number'     => $refund->account_number ?? 'N/A',
                'holder_name'        => $refund->holder_name ?? 'N/A',
                'ifsc_code'          => $refund->ifsc_code ?? 'N/A',
                'details'            => $refund->details ?? 'N/A',
                'req_date'   => $refund->req_date ? \Carbon\Carbon::parse($refund->req_date)->format('d-M-Y') : 'N/A',
                'ref_date'   => $refund->ref_date ? \Carbon\Carbon::parse($refund->ref_date)->format('d-M-Y') : 'N/A',
                'mode'               => $refund->mode ?? 'N/A',
                'transaction_details' => $refund->transaction_details ?? 'N/A',
                'remark'             => $refund->remark ?? 'N/A',
            ];

            // Fetch proofs using exact collection names from your DB
            $data['acc_proof'] = $refund->getFirstMediaUrl('acc-proof')   ?: '';
            $data['aadhar']    = $refund->getFirstMediaUrl('aadhar')      ?: '';
            $data['pan']       = $refund->getFirstMediaUrl('pan')         ?: '';
            // If you also have refund proof (pay-proof)
            $data['pay_proof'] = $refund->getFirstMediaUrl('pay-proof')   ?: '';
        }

        $comm    = $data['comm'];
        //dd($refundDetails);
        return view("booking.{$viewName}", $data + get_defined_vars());
    }

    private function getBaseQuery(array $options = [])
    {
        $query = Booking::withoutGlobalScope(SoftDeletingScope::class)
            ->from('xlr8_booking_master as bookings')
            ->select([
                'bookings.id',
                'bookings.b_type',
                'bookings.b_cat',
                'bookings.b_mode',
                'bookings.col_type',
                'bookings.col_by',
                'bookings.sap_no',
                'bookings.dms_no',
                'bookings.b_source',
                'bookings.dsa_id',
                'bookings.online_bk_ref_no',
                'bookings.booking_date',
                'bookings.receipt_no',
                'bookings.receipt_date',
                'bookings.booking_amount',
                'bookings.branch_code',
                'bookings.location_code',
                'bookings.location_other',
                'bookings.c_dob',
                'bookings.gender',
                'bookings.occ',
                'bookings.buyer_type',
                'bookings.exist_oem1',  // Added from Exchange/Insurance/Rto
                'bookings.exist_oem2',  // If exists in model, added
                'bookings.vh1_detail',  // Added
                'bookings.vh2_detail',  // Added
                'bookings.registration_no',  // Added
                'bookings.make_year',  // Added for vehicle mfg year
                'bookings.odo_reading',  // Added
                'bookings.expected_price',  // Added
                'bookings.offered_price',  // Added
                'bookings.exchange_bonus',  // Added
                'bookings.segment_id',
                'bookings.model',
                'bookings.variant',
                'bookings.color',
                'bookings.vh_id',
                'bookings.registration_no',
                'bookings.seating',
                'bookings.person_id',
                'bookings.name',
                'bookings.care_of',
                'bookings.care_of_type',
                'bookings.mobile',
                'bookings.alt_mobile',
                'bookings.pan_no',
                'bookings.adhar_no',
                'bookings.gstn',
                'bookings.dms_otf',
                'bookings.order',
                'bookings.otf_date',
                'bookings.dms_so',
                'bookings.cpd',
                'bookings.chasis_no',
                'bookings.r_name',
                'bookings.r_mobile',
                'bookings.r_model',
                'bookings.r_variant',
                'bookings.r_chassis',
                'bookings.del_type',
                'bookings.del_date',
                'bookings.fin_mode',
                'bookings.financier',
                'bookings.loan_status',
                'bookings.accessories',
                'bookings.consultant',
                'bookings.inv_no',
                'bookings.inv_date',
                'bookings.dealer_inv_no',
                'bookings.dealer_inv_date',
                'bookings.cancel_date',
                'bookings.refund_request_date',
                'bookings.refund_date',
                'bookings.refund_rejection_date',
                'bookings.dealer_status',
                'bookings.details',
                'bookings.pending',
                'bookings.pending_remark',
                'bookings.retail',
                'bookings.payout',
                'bookings.status',
                'bookings.created_at',
                'bookings.created_by',
                'bookings.updated_at',
                'bookings.updated_by',



            ]);
        $query->leftJoin('xlr8_booking_refund as ref', function ($join) {
            $join->on('bookings.id', '=', DB::raw('CAST(ref.entity_id AS UNSIGNED)'))
                ->where('ref.entity_type', 'booking');
        })->addSelect([
            'ref.amount as refund_amount',
            'ref.status as refund_status',
            'ref.req_date as refund_req_date',
            'ref.ref_date as refund_ref_date',
            // agar aur fields chahiye to add kar sakte ho
        ]);
        // ==================== UNCONDITIONAL JOINS + FIELDS (as requested) ====================
        $query->leftJoin('xlr8_booking_insurer as ins', 'bookings.id', '=', 'ins.bid')
            ->leftJoin('xlr8_booking_rto as rto', 'bookings.id', '=', 'rto.bid')
            ->leftJoin('xlr8_booking_finance as f', 'bookings.id', '=', 'f.bid');

        $query->addSelect([
            // Insurance Fields
            'ins.source as insurance_source',
            'ins.insurer as insurance_insurer_id',           // renamed for readability
            // 'ins.insurer_short_name as insurance_short_name',   // ← uncomment & change column name if you have this field
            'ins.pol_no as policy_no',
            'ins.pol_date as policy_date',
            'ins.pol_type as policy_type',

            // RTO Fields
            'rto.sale_type as sale_type',
            'rto.permit as permit',
            'rto.body_type as body_type',
            'rto.rgn_type as registration_type',
            'rto.rgn_no_type as registration_no_type',
            'rto.trc_no as trc_number',
            'rto.trc_payment_no as trc_payment_bank_ref_no',
            'rto.app_no as application_no',
            'rto.tax_payment_bank_ref_no as tax_payment_bank_ref_no',
            'rto.vh_rgn_no as vehicle_registration_no',

            // Finance Fields
            'f.instrument_type as instrument_type',
            'f.loan_amount as loan_amount_dealer_entry',
            'f.margin as margin_money',
            'f.file_charge as file_charge',
            'f.fin_loan_amount as net_payment_amount',
            'f.payout_category as payout_category',
            'f.instrument_ref_no as do_number',
            'f.loan_amount',
            'f.expected_payout_pct',
            'f.fin_loan_amount',
            'f.gst_included',
            'f.inv1_prov_gst',
            'f.inv2_prov_gst',
            'f.consideration_no_gst',
            'f.difference',
            // most common field used as "Net Payment"
        ]);


        //dd($query->get()->pluck('b_source', 'id')->toArray());
        return $query->orderBy('bookings.id', 'DESC');
    }

    private function mapBookingForGrid($booking)
    {
        $consultant = User::find($booking->consultant);
        $consultantName = $consultant?->name ?? 'N/A';

        $collectedBy = $booking->col_by ? User::find($booking->col_by) : null;
        $collectedByName = $collectedBy?->name ?? 'N/A';

        $branchName = $booking->branch?->name ?? 'N/A';
        $locationName = $booking->location?->name ?? ($booking->location_other ?? 'N/A');

        $statusBadge = $this->getStatusBadge($booking->status ?? 8);

        $bookingNo = $booking->id;

        $invoiceDate = $booking->inv_date ? Carbon::parse($booking->inv_date)->format('d-M-Y')
            : ($booking->dealer_inv_date ? Carbon::parse($booking->dealer_inv_date)->format('d-M-Y') : 'N/A');

        $invoiceNo = $booking->inv_no ?? $booking->dealer_inv_no ?? 'N/A';

        $dsaName = $booking->dsa_id ? (Xl_DSA_Master::find($booking->dsa_id)?->name ?? 'N/A') : 'N/A';

        $daysOld = $booking->booking_date
            ? Carbon::parse($booking->booking_date)->diffInDays(now())
            : Carbon::parse($booking->created_at)->diffInDays(now());
        $financierName = 'N/A';

        $financierRecord = $booking->financier
            ? XlFinancier::find($booking->financier)
            : null;
        $refundRecord = \App\Models\Xl_Refunds::where('entity_id', $booking->id)
            ->where('entity_type', 'booking')
            ->latest('created_at')
            ->first();

        $refundAmount = $refundRecord ? (float) $refundRecord->amount : 0;
        $liveCount = Booking::where('model', $booking->model)
            ->where('variant', $booking->variant)
            ->where('color', $booking->color)
            ->whereIn('status', [1, 8])
            ->count();



        // Stock Count: same vehicle ke stock mein kitne units hain
        // (assuming X_Vh_Stock table mein vh_id se link hai, aur available stock count karna hai)
        $stockCount = Stock::where('vh_id', $booking->vh_id)
            ->where('status', 'available')  // ya jo bhi tumhara stock status field hai
            ->count();
        // ==================== INSURANCE MAPPING ====================
        $insurance_source = match ((int)($booking->insurance_source ?? 0)) {
            1 => 'By Dealer (OEM Portal)',
            2 => 'By Dealer (Agency)',
            3 => 'By Owner (Self)',
            default => 'N/A'
        };

        $insurance_company    = 'N/A';
        $insurance_short_name = 'N/A';

        if (!empty($booking->insurance_insurer_id)) {
            $insurer = \App\Models\XlInsurance::find($booking->insurance_insurer_id)
                ?? \App\Models\XlInsurance::find($booking->insurance_insurer_id);

            if ($insurer) {
                $insurance_company    = $insurer->name ?? 'N/A';
                $insurance_short_name = $insurer->short_name ?? 'N/A';
            }
        }

        // Policy Fields - CORRECTED
        $policy_no   = $booking->policy_no ?? 'N/A';
        $policy_date = $booking->policy_date
            ? Carbon::parse($booking->policy_date)->format('d-M-Y')
            : 'N/A';

        $policy_type = match ((int)($booking->policy_type ?? 0)) {
            1 => 'Normal',
            2 => 'Nil Dep',
            3 => 'Nil Dep + Cons.',
            4 => 'Nil Dep + Cons. + Extra Add-On',
            default => 'N/A'
        };
        // ==================== RTO FIELDS MAPPING ====================

        $rto_sale_type = match ((int)($booking->sale_type ?? 0)) {
            1 => 'Within State',
            2 => 'Outside State',
            default => 'N/A'
        };

        $rto_permit = match ((int)($booking->permit ?? 0)) {
            1  => 'Private - U/C (4 Wheeler)',
            2  => 'Private - BH (4 Wheeler)',
            3  => 'Private - EV (4 Wheeler)',
            4  => 'Goods - G (4 Wheeler)',
            5  => 'Goods - G 3 Ton+ (4 Wheeler)',
            6  => 'Goods - G (3 Wheeler)',
            7  => 'Goods - G EV (3 Wheeler)',
            8  => 'Taxi - T (4 Wheeler)',
            9  => 'Passenger - P (3 Wheeler)',
            10 => 'Passenger - P EV (3 Wheeler)',
            11 => 'Ambulance (Misc.)',
            default => 'N/A'
        };

        $rto_body_type = match ((int)($booking->body_type ?? 0)) {
            1 => 'Complete',
            2 => 'CBC',
            default => 'N/A'
        };
        // ==================== ADDITIONAL RTO FIELDS MAPPING ====================

        // Registration Type
        $registration_type = match ((int)($booking->registration_type ?? 0)) {
            1 => 'TRC Only',
            2 => 'Tax Only',
            3 => 'TRC + Tax',
            default => 'N/A'
        };

        // Registration No. Type
        $registration_no_type = match ((int)($booking->registration_no_type ?? 0)) {
            1 => 'Regular',
            2 => 'BH',
            3 => 'Special',
            default => 'N/A'
        };

        // TRC Number
        $trc_number = $booking->trc_number ?? 'N/A';

        // TRC Payment Bank Ref No.
        $trc_payment_bank_ref_no = $booking->trc_payment_bank_ref_no ?? 'N/A';

        // Application No.
        $application_no = $booking->application_no ?? 'N/A';

        // Tax Payment Bank Ref No.
        $tax_payment_bank_ref_no = $booking->tax_payment_bank_ref_no ?? 'N/A';

        // Vehicle Registration No.
        $vehicle_registration_no = $booking->vehicle_registration_no ?? 'N/A';
        // ==================== FINANCE FIELDS MAPPING ====================
        $instrument_type = match ((int)($booking->instrument_type ?? 0)) {
            1 => 'Financier Payment',
            2 => 'Delivery Order',
            3 => 'Sanction Letter',
            4 => 'Mail Communication',
            5 => 'Whatsapp Communication',
            default => 'N/A'
        };

        $loan_amount_dealer_entry = (float) ($booking->loan_amount_dealer_entry ?? 0);
        $margin_money             = (float) ($booking->margin_money ?? 0);
        $file_charge              = (float) ($booking->file_charge ?? 0);

        // Net Payment Amount = Loan Amount + Margin Money - File Charge
        // Same logic as your calculatePayment() function in blade
        $net_payment_amount       = $loan_amount_dealer_entry + $margin_money - $file_charge;
        $payoutCategory = 'N/A';

        if (!empty($booking->payout_category)) {
            $payoutCategory = match ((int)$booking->payout_category) {
                1 => 'Payout',
                2 => 'No Payout',
                4 => 'Cash',
                default => 'N/A'
            };
        }
        $donumber = $booking->do_number ?? 'N/A';

        // ==================== PAYOUT FIELDS MAPPING (Raw from DB) ====================
        $loan_amount                  = (float) ($booking->loan_amount ?? 0);
        $expected_payout_pct          = (float) ($booking->expected_payout_pct ?? 0);     // kept as 1.5 (for display)
        $fin_loan_amount              = (float) ($booking->fin_loan_amount ?? 0);
        $gst_included                 = (float) ($booking->gst_included ?? 0);
        $inv1_prov_gst                = (float) ($booking->inv1_prov_gst ?? 0);
        $inv2_prov_gst                = (float) ($booking->inv2_prov_gst ?? 0);
        $consideration_no_gst         = (float) ($booking->consideration_no_gst ?? 0);
        $difference                   = (float) ($booking->difference ?? 0);   // raw DB value (not used in calc)

        // ==================== CALCULATED FIELDS - CORRECTED ====================
        $GST_RATE = 0.18;

        // Convert percentage to decimal for calculations
        $expected_payout_pct_decimal = $expected_payout_pct / 100;

        // Expected Payout % without GST (correct)
        $expected_payout_pct_without_gst = ($gst_included > 0)
            ? $expected_payout_pct_decimal / (1 + $GST_RATE * $gst_included)
            : $expected_payout_pct_decimal;

        // Expected Payout Amount without GST
        $expected_payout_amount_without_gst = $loan_amount * $expected_payout_pct_without_gst;

        // Suggested Invoice Amount
        $sugg_inv_amt = $expected_payout_amount_without_gst * (1 + $GST_RATE);

        // Total Provisioning (with GST)
        $total_prov_with_gst = $inv1_prov_gst + $inv2_prov_gst;

        // Total Provisioning without GST
        $total_prov_without_gst = ($total_prov_with_gst > 0)
            ? $total_prov_with_gst / (1 + $GST_RATE)
            : 0;

        // Provisioning % (without GST) → NOW MULTIPLIED BY 100
        $prov_prc_without_gst = ($loan_amount > 0)
            ? ($total_prov_without_gst / $loan_amount) * 100
            : 0;

        // Difference (without GST)
        $diff_without_gst = $total_prov_without_gst - $expected_payout_amount_without_gst + $consideration_no_gst;


        // ==================== FORMATTED VALUES FOR GRID (Clean Display) ====================
        $expected_payout_pct_formatted = number_format($expected_payout_pct, 4) . '%';

        $expected_payout_pct_without_gst_formatted = number_format($expected_payout_pct_without_gst * 100, 4) . '%';

        $prov_prc_without_gst_formatted = number_format($prov_prc_without_gst, 4) . '%';

        $diff_without_gst_formatted = '₹ ' . number_format($diff_without_gst, 2, '.', ',');




        // dd([
        //     'insurance_insurer_id' => $booking->insurance_insurer_id,
        //     'insurance_source'     => $booking->insurance_source,
        //     'insurer_found'        => $insurer->name ?? 'NOT FOUND'
        // ]);
        //dd($booking->toArray());
        // ────────────────────────────────────────────────
        // Final mapped object – sab kuch yahan daal do
        // ────────────────────────────────────────────────
        return (object) [
            'id'                    => $booking->id,
            'serial_no'             => null, // listing mein add hoga
            'booking_no'            => $bookingNo,
            // ─── Dates (sabse pehle) ───────────────────────────────
            'created_at'              => Carbon::parse($booking->created_at)->format('d-M-Y'),
            'booking_date'            => $booking->booking_date ? Carbon::parse($booking->booking_date)->format('d-M-Y') : 'N/A',
            'cancel_date'             => $booking->cancel_date ? Carbon::parse($booking->cancel_date)->format('d-M-Y') : 'N/A',
            'refund_request_date'     => $booking->refund_request_date ? Carbon::parse($booking->refund_request_date)->format('d-M-Y') : 'N/A',
            'refund_date'             => $booking->refund_date ? Carbon::parse($booking->refund_date)->format('d-M-Y') : 'N/A',
            'refund_rejection_date'   => $booking->refund_rejection_date ? Carbon::parse($booking->refund_rejection_date)->format('d-M-Y') : 'N/A',
            'receipt_date'            => $booking->receipt_date ? Carbon::parse($booking->receipt_date)->format('d-M-Y') : 'N/A',
            'invoice_date'            => $invoiceDate,
            'cpd'                     => $booking->cpd ? Carbon::parse($booking->cpd)->format('d-M-Y') : 'N/A',
            'del_date'                => $booking->del_date ? Carbon::parse($booking->del_date)->format('d-M-Y') : 'N/A',
            'otf_date'                => $booking->otf_date ? Carbon::parse($booking->otf_date)->format('d-M-Y') : 'N/A',
            'inv_date'                => $booking->inv_date ? Carbon::parse($booking->inv_date)->format('d-M-Y') : 'N/A',

            // ─── Customer Details ──────────────────────────────────

            'name'                    => $booking->name ?? 'N/A',
            'col_by'                    => $booking->col_by ?? 'N/A',
            'care_of'                 => $booking->care_of ?? 'N/A',
            'care_of_type'                 => $booking->care_of_type ?? 'N/A',
            'customer_age'            => $booking->c_dob
                ? $this->calculateAgeFromDob($booking->c_dob)
                : 'N/A',
            'mobile'                  => $booking->mobile ?? 'N/A',
            'alt_mobile'                  => $booking->alt_mobile ?? 'N/A',
            'gender'        => $booking->gender ?? 'N/A',
            'occ'       => $booking->occ ?? 'N/A',
            'c_dob'     => $booking->c_dob ?? 'N/A',

            'pan_no'                  => $booking->pan_no ?? 'N/A',
            'adhar_no' => !empty(trim($booking->adhar_no ?? '')) && strlen(trim($booking->adhar_no ?? '')) > 3
                ? trim($booking->adhar_no)
                : 'N/A',
            // GSTN – same logic (blank, null, ya 0 → N/A)
            'gstn'         => !empty($booking->gstn) && $booking->gstn !== '0' && $booking->gstn !== 0
                ? $booking->gstn
                : 'N/A',
            // Segment – lookup from EnumMaster ya direct value
            'segment' => $booking->segment_id
                ? (EnumMaster::find($booking->segment_id)?->value ?? 'N/A')
                : 'N/A',
            'model'                 => $booking->model ?? 'N/A',
            'variant'               => $booking->variant ?? 'N/A',
            'color'                 => $booking->color ?? 'N/A',
            'booking_amount'        => $booking->booking_amount,
            'seating'               => $booking->seating,

            'consultant'            => $consultantName,
            'branch_name'           => $branchName,
            'location_name'         => $locationName,
            'days_count'            => (int) round($daysOld),
            'b_type'                => $booking->b_type ?? 'N/A',
            'buyer_type'                => $booking->buyer_type ?? 'N/A',
            'b_cat'                => $booking->b_cat ?? 'N/A',
            'b_mode'                => $booking->b_mode ?? 'N/A',
            'b_source'              => $booking->b_source ?? 'N/A',
            'exist_oem1'              => CommonHelper::enumValueById($booking->exist_oem1) ?? 'N/A',
            'exist_oem2'              => CommonHelper::enumValueById($booking->exist_oem2) ?? 'N/A',
            'vh1_detail'              => $booking->vh1_detail ?? 'N/A',
            'vh2_detail'              => $booking->vh2_detail ?? 'N/A',
            'col_type'              => match ((int)$booking->col_type) {
                1 => 'Receipt',
                2 => 'Field (Sales)',
                3 => 'Field (DSA)',
                default => 'Unknown'
            },
            'registration_no'   => $booking->registration_no ?? 'N/A',
            'vehicle_reg_no'    => $booking->registration_no ?? 'N/A',
            'make_year'    => $booking->make_year ?? 'N/A',
            'odo_reading'    => $booking->odo_reading ?? 'N/A',
            'exchange_purchase_type' => $exchange_purchase_type ?? 'N/A',
            'expected_price'    => $booking->expected_price ?? 'N/A',
            'offered_price'    => $booking->offered_price ?? 'N/A',
            'exchange_bonus'    => $booking->exchange_bonus ?? 'N/A',
            'price_gap'         => ($booking->expected_price ?? 0)
                - (($booking->offered_price ?? 0) + ($booking->exchange_bonus ?? 0)),
            'col_by'                => $collectedByName,
            'dsa_name'              => $dsaName,
            'r_name'              => $booking->r_name ?? 'N/A',
            'r_mobile'              => $booking->r_mobile ?? 'N/A',
            'r_model'               => $booking->r_model ?? 'N/A',
            'r_variant'             => $booking->r_variant ?? 'N/A',
            'r_chassis'             => $booking->r_chassis ?? 'N/A',
            'fin_mode'              => $booking->fin_mode ?? 'N/A',
            'financier'             => $financierName,
            'financier_short_name'  => $financierRecord ? ($financierRecord->short_name ?? 'N/A') : 'N/A',
            'loan_status'           => $booking->loan_status ?? 'N/A',
            'insurance_source'      => $insurance_source,
            'insurance_company'     => $insurance_company,
            'insurance_short_name'  => $insurance_short_name,
            'policy_no'             => $policy_no,
            'policy_date'           => $policy_date,
            'policy_type'           => $policy_type,
            'rto_sale_type'         => $rto_sale_type,
            'rto_permit'            => $rto_permit,
            'rto_body_type'         => $rto_body_type,
            'registration_type'         => $registration_type,
            'registration_no_type'      => $registration_no_type,
            'trc_number'                => $trc_number,
            'trc_payment_bank_ref_no'   => $trc_payment_bank_ref_no,
            'application_no'            => $application_no,
            'tax_payment_bank_ref_no'   => $tax_payment_bank_ref_no,
            'vehicle_registration_no'   => $vehicle_registration_no,
            'instrument_type'         => $instrument_type,
            'loan_amount_dealer_entry' => $loan_amount_dealer_entry,
            'margin_money'            => $margin_money,
            'file_charge'             => $file_charge,
            'net_payment_amount'      => $net_payment_amount,
            'sap_no'                => $booking->sap_no ?? 'N/A',
            'used_vehicle_exp_price' => $used_vehicle_exp_price ?? 'N/A',
            'dealer_inv_no'                => $booking->dealer_inv_no ?? 'N/A',
            'inv_no'                => $booking->inv_no ?? 'N/A',
            'dealer_inv_date'                => $booking->dealer_inv_date ?? 'N/A',
            'dms_no'                => $booking->dms_no ?? 'N/A',
            'dms_otf'               => $booking->dms_otf ?? 'N/A',
            'dms_so'                => $booking->dms_so ?? 'N/A',
            'online_bk_ref_no'      => $booking->online_bk_ref_no ?? 'N/A',
            'receipt_no'            => $booking->receipt_no ?? 'N/A',
            'receipt_date'          => $booking->receipt_date ? Carbon::parse($booking->receipt_date)->format('d-M-Y') : 'N/A',
            'chasis_no'             => $booking->chasis_no ?? 'N/A',
            'del_type'              => $booking->del_type ?? 'N/A',
            'invoice_no'            => $invoiceNo,
            'refund_amount'         => $refundAmount,
            'payout_category'       => $payoutCategory,
            'do_number'             => $donumber,

            'loan_amount_dealer'                => $loan_amount,                    // Loan Amount (Dealer Entry)

            'expected_payout_pct'               => $expected_payout_pct_formatted,
            'expected_payout_pct_without_gst'   => $expected_payout_pct_without_gst_formatted,
            'expected_payout_amount_without_gst' => $expected_payout_amount_without_gst,
            'sugg_inv_amt'                      => $sugg_inv_amt,

            'loan_amount_fin_payout_sheet'      => $fin_loan_amount,

            'total_prov_with_gst'               => $total_prov_with_gst,
            'prov_prc_without_gst'              => $prov_prc_without_gst_formatted,
            'diff_without_gst'                  => $diff_without_gst_formatted,

            // listing mein add hoga
            // Extra fields jo kabhi chahiye to yahan daal dena
            'pan_no'                => $booking->pan_no ?? 'N/A',
            'adhar_no'              => $booking->adhar_no ?? 'N/A',
            'care_of'               => $booking->care_of ?? 'N/A',
            'livecount'             => $liveCount ?? 'N/A',
            'stockcount'            => $stockCount ?? 'N/A',
            'action'                => '',
        ];
    }

    private function getAgGridColumns(array $extraColumns = []): array
    {
        $columns = [


            ['headerName' => 'S.No.',       'field' => 'serial_no',     'width' => 80,  'sortable' => false, 'filter' => false],
            ['headerName' => 'XB No.',      'field' => 'booking_no',     'width' => 140,  'sortable' => true],
            ['headerName' => 'Entry Date',         'field' => 'created_at',            'width' => 110, 'type' => 'date'],
            ['headerName' => 'Booking Date',       'field' => 'booking_date',          'width' => 120, 'type' => 'date'],
            ['headerName' => 'Booking Age',       'field' => 'days_count',     'width' => 100, 'type' => 'number', 'cellClass' => 'text-right'],
            ['headerName' => 'Invoice No.',       'field' => 'inv_no',          'width' => 120],
            ['headerName' => 'Invoice Date',       'field' => 'inv_date',          'width' => 120, 'type' => 'date'],
            ['headerName' => 'Dealer Invoice No.',       'field' => 'dealer_inv_no',          'width' => 120],
            ['headerName' => 'Dealer Invoice Date',       'field' => 'dealer_inv_date',          'width' => 120, 'type' => 'date'],
            ['headerName' => 'Cancellation Date',        'field' => 'cancel_date',           'width' => 110, 'type' => 'date'],
            ['headerName' => 'Refund Request Date',    'field' => 'refund_request_date',   'width' => 130, 'type' => 'date'],
            ['headerName' => 'Refunded Date',      'field' => 'refund_date',           'width' => 120, 'type' => 'date'],
            ['headerName' => 'Refund Reject Date', 'field' => 'refund_rejection_date', 'width' => 140, 'type' => 'date'],
            ['headerName' => 'Customer Type',   'field' => 'b_type',         'width' => 110],
            ['headerName' => 'Customer Category',  'field' => 'b_cat',         'width' => 180, 'filter' => true],
            ['headerName' => 'Collection Type', 'field' => 'col_type',       'width' => 150],
            ['headerName' => 'Collection By', 'field' => 'col_by',       'width' => 150],
            ['headerName' => 'Booking Amount',         'field' => 'booking_amount', 'width' => 120, 'type' => 'number'],
            ['headerName' => 'Amount to Refund', 'field' => 'refund_amount', 'width' => 140, 'type' => 'number', 'cellClass' => 'text-right'],
            ['headerName' => 'Receipt No.',       'field' => 'receipt_no',          'width' => 120],
            ['headerName' => 'Receipt Date',       'field' => 'receipt_date',          'width' => 120, 'type' => 'date'],
            ['headerName' => 'Customer Name',  'field' => 'name',         'width' => 180, 'filter' => true],
            ['headerName' => 'Care Of Type',        'field' => 'care_of_type',      'width' => 140],
            ['headerName' => 'Care Of',        'field' => 'care_of',      'width' => 140],
            ['headerName' => 'Mobile No.',         'field' => 'mobile',       'width' => 120],
            ['headerName' => 'Alternate Mobile No.',         'field' => 'alt_mobile',       'width' => 120],
            ['headerName' => 'Gender',  'field' => 'gender',         'width' => 180, 'filter' => true],
            ['headerName' => 'Occupation',        'field' => 'occ',      'width' => 140],
            ['headerName' => 'PAN No.',         'field' => 'pan_no',       'width' => 110],
            ['headerName' => 'Aadhaar No.',     'field' => 'adhar_no',     'width' => 130],
            ['headerName' => 'GSTIN',           'field' => 'gstn',         'width' => 120],
            ['headerName' => 'Customer D.O.B',       'field' => 'c_dob',          'width' => 120, 'type' => 'date'],
            ['headerName' => 'Customer Age',      'field' => 'customer_age',  'width' => 110, 'cellClass' => 'text-center'],

            ['headerName' => 'Branch',         'field' => 'branch_name',    'width' => 140, 'filter' => true],
            ['headerName' => 'Location',       'field' => 'location_name',  'width' => 160, 'filter' => true],
            ['headerName' => 'Segment',          'field' => 'segment',          'width' => 140],
            ['headerName' => 'Model',          'field' => 'model',          'width' => 140],
            ['headerName' => 'Variant',        'field' => 'variant',        'width' => 150],
            ['headerName' => 'Color',          'field' => 'color',          'width' => 100],
            ['headerName' => 'Seating',        'field' => 'seating',      'width' => 130],
            ['headerName' => 'Chassis No.',        'field' => 'chasis_no',      'width' => 130],
            ['headerName' => 'Booking Status',    'field' => 'status',    'width' => 130],
            ['headerName' => 'Booking Mode',        'field' => 'b_mode',      'width' => 140],
            ['headerName' => 'Online Book Ref No.',     'field' => 'online_bk_ref_no', 'width' => 130],
            ['headerName' => 'Booking Source',  'field' => 'b_source',         'width' => 180, 'filter' => true],
            ['headerName' => 'DSA Name',  'field' => 'dsa_name',         'width' => 180, 'filter' => true],

            ['headerName' => 'Sales Consultant',     'field' => 'consultant',     'width' => 140],
            ['headerName' => 'Delivery Date Type',     'field' => 'del_type',     'width' => 140],
            ['headerName' => 'Delivery Date',      'field' => 'del_date',              'width' => 120, 'type' => 'date'],
            ['headerName' => 'Finance Mode',   'field' => 'fin_mode',         'width' => 140],
            ['headerName' => 'Financier',          'field' => 'financier',        'width' => 180, 'filter' => true],
            ['headerName' => 'Financier Short',    'field' => 'financier_short_name', 'width' => 150],
            ['headerName' => 'Loan Status',        'field' => 'loan_status',      'width' => 140, 'cellClass' => 'text-center'],
            ['headerName' => 'Purchase Type',   'field' => 'buyer_type',         'width' => 140],
            ['headerName' => 'Brand Make 1',        'field' => 'exist_oem1',      'width' => 130],
            ['headerName' => 'Model Variant 1',        'field' => 'vh1_detail',      'width' => 130],
            ['headerName' => 'Brand Make 2',        'field' => 'exist_oem2',      'width' => 130],
            ['headerName' => 'Model Variant 2',        'field' => 'vh2_detail',      'width' => 130],
            ['headerName' => 'Vehicle Registration No.',        'field' => 'registration_no',      'width' => 130],
            ['headerName' => 'Vehicle Manufacturing Year',        'field' => 'make_year',      'width' => 130],
            ['headerName' => 'Vehicle Odometer Reading',        'field' => 'odo_reading',      'width' => 130],
            ['headerName' => 'Used Vehicle Expected Price',       'field' => 'expected_price',     'width' => 100, 'type' => 'number', 'cellClass' => 'text-right'],
            ['headerName' => 'Used Vehicle Offered Price',       'field' => 'offered_price',     'width' => 100, 'type' => 'number', 'cellClass' => 'text-right'],
            ['headerName' => 'Used Vehicle Exchange Bonus',       'field' => 'exchange_bonus',     'width' => 100, 'type' => 'number', 'cellClass' => 'text-right'],
            [
                'headerName' => 'Price Gap',
                'field'      => 'price_gap',
                'width'      => 140,
                'type'       => 'numericColumn',
                'cellClass'  => 'text-right fw-bold',
                'valueFormatter' => "params.value != null ? '₹ ' + Math.round(params.value).toLocaleString('en-IN') : 'N/A'",
            ],

            ['headerName' => 'Customer Name',                'field' => 'r_name',                   'width' => 100, 'type' => 'date'],
            ['headerName' => 'Referred Mobile',     'field' => 'r_mobile',          'width' => 130],
            ['headerName' => 'Referred Model',      'field' => 'r_model',           'width' => 140],
            ['headerName' => 'Referred Variant',    'field' => 'r_variant',         'width' => 150],
            ['headerName' => 'Referred Chassis',    'field' => 'r_chassis',         'width' => 140],

            ['headerName' => 'DMS Booking No.',         'field' => 'dms_no',         'width' => 110],
            ['headerName' => 'DMS OTF No.',        'field' => 'dms_otf',        'width' => 110],
            ['headerName' => 'DMS OTF Date',      'field' => 'otf_date',              'width' => 120, 'type' => 'date'],
            ['headerName' => 'DMS SO No.',         'field' => 'dms_so',         'width' => 110],

            ['headerName' => 'Live Order',     'field' => 'livecount',   'width' => 130, 'type' => 'number'],
            ['headerName' => 'Stock In Hand',    'field' => 'stockcount',  'width' => 130, 'type' => 'number'],

            ['headerName' => 'Insurance Source',   'field' => 'insurance_source',  'width' => 160, 'filter' => true],
            ['headerName' => 'Insurance Company',  'field' => 'insurance_company', 'width' => 180, 'filter' => true],
            ['headerName' => 'Insurance Short Name',    'field' => 'insurance_short_name', 'width' => 140, 'filter' => true],

            ['headerName' => 'Policy No.',           'field' => 'policy_no',         'width' => 160, 'filter' => true],
            ['headerName' => 'Policy Date',          'field' => 'policy_date',       'width' => 130, 'type' => 'date'],
            ['headerName' => 'Policy Type',          'field' => 'policy_type',       'width' => 180, 'filter' => true],
            ['headerName' => 'Sale Type',        'field' => 'rto_sale_type',     'width' => 160, 'filter' => true],
            ['headerName' => 'RTO Permit',           'field' => 'rto_permit',        'width' => 220, 'filter' => true],
            ['headerName' => 'RTO Body Type',        'field' => 'rto_body_type',     'width' => 160, 'filter' => true],
            // ==================== RTO FIELDS (CORRECTED) ====================
            ['headerName' => 'Registration Type',          'field' => 'registration_type',     'width' => 140],
            ['headerName' => 'Registration No. Type',      'field' => 'registration_no_type',  'width' => 160],
            ['headerName' => 'TRC Number',                 'field' => 'trc_number',            'width' => 140],
            ['headerName' => 'TRC Payment Bank Ref No.',   'field' => 'trc_payment_bank_ref_no', 'width' => 180],
            ['headerName' => 'Application No.',            'field' => 'application_no',        'width' => 140],
            ['headerName' => 'Tax Payment Bank Ref No.',   'field' => 'tax_payment_bank_ref_no', 'width' => 180],
            ['headerName' => 'Vehicle Registration No.',   'field' => 'vehicle_registration_no', 'width' => 160],
            ['headerName' => 'Instrument Type',            'field' => 'instrument_type',         'width' => 180, 'filter' => true],
            ['headerName' => 'Margin Money',               'field' => 'margin_money',            'width' => 140, 'type' => 'number', 'cellClass' => 'text-right'],
            ['headerName' => 'File Charge',                'field' => 'file_charge',             'width' => 130, 'type' => 'number', 'cellClass' => 'text-right'],
            [
                'headerName' => 'Net Payment Amount',
                'field'      => 'net_payment_amount',
                'width'      => 170,
                'type'       => 'number',
                'cellClass'  => 'text-right fw-bold',
                'valueFormatter' => "params.value != null ? '₹ ' + parseFloat(params.value).toLocaleString('en-IN', {minimumFractionDigits: 2}) : 'N/A'",
            ],
            ['headerName' => 'CPD',                'field' => 'cpd',                   'width' => 100, 'type' => 'date'],

            ['headerName' => 'Customer Type',  'field' => 'customer_type',         'width' => 180, 'filter' => true],


            ['headerName' => 'Care Of Name',        'field' => 'care_of_name',      'width' => 140],

            // Vehicle


            // Amount & Finance
            ['headerName' => 'Loan Amount',         'field' => 'booking_amount', 'width' => 120, 'type' => 'number'],

            ['headerName' => 'Payout Category',    'field' => 'payout_category',    'width' => 130],

            // People & Source
            ['headerName' => 'Collected By',   'field' => 'col_by',         'width' => 140],



            // DMS / Refs
            ['headerName' => 'SAP Booking No.',         'field' => 'sap_no',         'width' => 110],
            // Yeh do lines update kar do
            ['headerName' => 'Do Number',     'field' => 'do_number', 'width' => 130],
            ['headerName' => 'Loan Amount (Dealer Entry)', 'field' => 'loan_amount_dealer', 'width' => 170, 'type' => 'number', 'cellClass' => 'text-right'],
            ['headerName' => 'Expected Payout %',     'field' => 'expected_payout_pct', 'width' => 130],
            ['headerName' => 'Expected Payout % without GST',     'field' => 'expected_payout_pct_without_gst', 'width' => 130],
            ['headerName' => 'Expected Payout Amount without GST',     'field' => 'expected_payout_amount_without_gst', 'width' => 130],
            ['headerName' => 'Suggested Invoice Amount',     'field' => 'sugg_inv_amt', 'width' => 130],
            ['headerName' => 'Loan Amount(Fin Payout Sheet)',     'field' => 'loan_amount_fin_payout_sheet', 'width' => 130],
            ['headerName' => 'Total Provisioning (with GST)',     'field' => 'total_prov_with_gst', 'width' => 130],
            ['headerName' => 'Provisioning % (without GST)',     'field' => 'prov_prc_without_gst', 'width' => 130],
            ['headerName' => 'Difference (without GST)',     'field' => 'diff_without_gst', 'width' => 130],






            // Action – right pinned
        ];

        return array_merge($columns, $extraColumns);
    }

   
    private function getStatusBadge($status)
    {
        return match ((int)$status) {
            1 => '<span class="badge badge-success">Live</span>',
            2 => 'Invoiced',
            3 => '<span class="badge badge-danger">Cancelled</span>',
            4 => '<span class="badge badge-warning">Refund Queued</span>',
            5 => '<span class="badge badge-info">Refunded</span>',
            6 => '<span class="badge badge-warning text-dark">On Hold</span>',
            7 => '<span class="badge badge-dark">Refund Rejected</span>',
            8 => 'Pending',
            default => '<span class="badge badge-light">Unknown</span>',
        };
    }
    public function showInvoiced($id)
    {
        $this->crud->hasAccessOrFail('show');

        $entry = $this->crud->getEntry($id);
        if ((int)$entry->status !== 2) {
            abort(404, 'Yeh booking invoiced nahi hai.');
        }

        return $this->getFullBookingData($id, 'show-invoiced');
    }

    protected function setupListOperation()
    {
        $this->crud->setListView('booking.list');

        // We will handle everything in the index() method instead
        // This method can stay almost empty now
    }
    // All Live Bookings (main page)

    public function index()
    {
        $this->crud->hasAccessOrFail('list');

        $this->data['crud'] = $this->crud;
        $this->data['title'] = 'All Live Bookings';

        // Base query
        $query = $this->getBaseQuery();

        // Live bookings only
        $query->whereIn('bookings.status', [1, 8]);
        // $query->where('bookings.b_type', 'Active'); // optional

        $query->orderBy('bookings.id', 'desc');

        // This line MUST exist – this is what creates $paginatedBookings
        $paginatedBookings = $query->paginate(50);

        // Mapping with serial number & action
        $gridData = $paginatedBookings->map(function ($booking, $index) use ($paginatedBookings) {
            $mapped = $this->mapBookingForGrid($booking);

            // Serial number – using the paginated collection
            $mapped->serial_no = ($paginatedBookings->currentPage() - 1) * $paginatedBookings->perPage() + $index + 1;
            // Action buttons
            $editUrl   = backpack_url("booking/{$booking->id}/edit");
            $showUrl   = backpack_url("booking/{$booking->id}/show");

            $amountUrl = backpack_url("booking/{$booking->id}/add-amount");

            if (in_array($booking->col_type, [2, 3])) {
                $totalPaid = \App\Models\Bookingamount::where('bid', $booking->id)
                    ->sum('amount') ?? 0;

                if ($booking->booking_amount > $totalPaid) {
                    $amountUrl = backpack_url("booking/{$booking->id}/pending-edit");
                }
            }

            $mapped->action = '
                                <div class="d-flex gap-2">
                                    <a href="' . $showUrl . '"
                                       class="btn btn-sm btn-primary py-1 px-2"
                                       title="View">
                                        View
                                    </a>

                                    <a href="' . $amountUrl . '"
                                       class="btn btn-sm btn-success py-1 px-2"
                                       title="Add Amount">
                                        Add ₹
                                    </a>
                                    <a href="' . $editUrl . '"
                                       class="btn btn-sm btn-info py-1 px-2"
                                       title="Edit">
                                        Edit
                                    </a>

                                </div>
                                ';
            // btn btn-sm btn-danger

            return $mapped;
        })->values();

        // dd([
        //     'total_rows'     => $gridData->count(),
        //     'current_page'   => $paginatedBookings->currentPage(),
        //     'per_page'       => $paginatedBookings->perPage(),
        //     'first_record'   => $gridData->first(),           // pehla row ka pura object
        //     'sample_3_rows'  => $gridData->take(3)->toArray(), // pehle 3 rows array mein
        //     'all_fields_of_first' => array_keys((array) $gridData->first() ?? []), // saare column names
        // ]);
        // Columns
        $columns = $this->getAgGridColumns();
        //dd($this->getAgGridColumns());
        // Add action column if not already in getAgGridColumns()
        $columns[] = [
            'headerName'    => 'Action',
            'field'         => 'action',
            'width'         => 170,
            'sortable'      => false,
            'filter'        => false,
            'cellRenderer'  => 'htmlRenderer',
            'pinned'        => 'right',
            'cellClass'     => 'text-center',
        ];

        // Pass to view
        $this->data['gridConfig'] = [
            'columns' => $columns,
            'data'    => $gridData,
        ];
        // dd([
        //     'columns'     => $columns,                   // ← yeh headings ka exact order dega
        //     'first_data'  => $gridData->first(),
        //     'data_fields' => array_keys((array)$gridData->first()),
        // ]);


        return view('booking.list', $this->data);
    }

    /**
     * Calculate age from DOB
     */
    private function calculateAgeFromDob($dob)
    {
        if (!$dob) return 'N/A';

        try {
            $birthDate = Carbon::parse($dob);
            $age = $birthDate->diffInYears(Carbon::now());
            return (int) $age;
            // return $age . ' Years';
        } catch (\Exception $e) {
            return 'N/A';
        }
    }

    public function hold()
    {
        $this->crud->hasAccessOrFail('list');

        $this->crud->setListView('admin.booking.list'); // same view

        $this->data['crud'] = $this->crud;
        $this->data['title'] = 'On-Hold Bookings';

        // ────────────────────────────────────────────────
        // Query – On-Hold bookings ke liye
        // ────────────────────────────────────────────────
        $query = $this->getBaseQuery();  // agar getBaseQuery() mein koi common joins hain to fayda milega

        // On-Hold filter
        $query->where('bookings.status', 6);

        // Sorting
        $query->orderBy('bookings.id', 'desc');

        // Pagination – same 50 per page
        $paginatedBookings = $query->paginate(50);

        // ────────────────────────────────────────────────
        // Mapping + serial_no + action buttons (index jaisa hi)
        // ────────────────────────────────────────────────
        $gridData = $paginatedBookings->map(function ($booking, $index) use ($paginatedBookings) {
            $mapped = $this->mapBookingForGrid($booking);  // ya mapBookingForList() agar alag function hai

            // Serial number with pagination
            $mapped->serial_no = ($paginatedBookings->currentPage() - 1) * $paginatedBookings->perPage() + $index + 1;

            // Action buttons – same as index
            // $editUrl   = backpack_url("booking/{$booking->id}/edit");
            $showUrl   = backpack_url("booking/{$booking->id}/show");
            // $amountUrl = backpack_url("booking/{$booking->id}/add-amount"); // comment if not needed

            $mapped->action = '
        <div class="btn-group btn-group-sm" role="group" aria-label="Actions">
            <a href="' . $showUrl . '"
               class="btn btn-sm btn-primary py-1 px-2" title="View Details">
                View
            </a>
        </div>';

            return $mapped;
        })->values();

        // ────────────────────────────────────────────────
        // Columns – same reusable function
        // ────────────────────────────────────────────────
        $columns = $this->getAgGridColumns();

        // Action column add (sirf agar getAgGridColumns() mein nahi hai to)
        // Agar pehle se hai to yeh line comment kar dena
        $columns[] = [
            'headerName'    => 'Action',
            'field'         => 'action',
            'width'         => 160,
            'minWidth'      => 140,
            'sortable'      => false,
            'filter'        => false,
            'resizable'     => false,
            'cellRenderer'  => 'htmlRenderer',
            'pinned'        => 'right',
            'cellClass'     => 'text-center p-0',
            'suppressSizeToFit' => true,
        ];

        // ────────────────────────────────────────────────
        // View ko data pass
        // ────────────────────────────────────────────────
        $this->data['gridConfig'] = [
            'columns' => $columns,
            'data'    => $gridData,
        ];

        // Optional pagination info
        $this->data['pagination'] = [
            'total'       => $paginatedBookings->total(),
            'perPage'     => $paginatedBookings->perPage(),
            'currentPage' => $paginatedBookings->currentPage(),
            'lastPage'    => $paginatedBookings->lastPage(),
        ];

        return view('booking.list', $this->data);
    }

    public function invoiced()
    {
        $this->crud->hasAccessOrFail('list');

        $this->crud->setListView('admin.booking.list'); // same view

        $this->data['crud'] = $this->crud;
        $this->data['title'] = 'Invoiced Bookings';

        // ────────────────────────────────────────────────
        // Query – Invoiced bookings ke liye (status = 2)
        // ────────────────────────────────────────────────
        $query = $this->getBaseQuery();

        // Invoiced filter
        $query->where('bookings.status', 2);

        // Sorting – latest pehle
        $query->orderBy('bookings.id', 'desc');

        // Pagination
        $paginatedBookings = $query->paginate(50);

        // ────────────────────────────────────────────────
        // Mapping + serial_no + same action buttons
        // ────────────────────────────────────────────────
        $gridData = $paginatedBookings->map(function ($booking, $index) use ($paginatedBookings) {
            $mapped = $this->mapBookingForGrid($booking);  // ya mapBookingForList() agar alag function hai

            // Serial number with pagination support
            $mapped->serial_no = ($paginatedBookings->currentPage() - 1) * $paginatedBookings->perPage() + $index + 1;

            // Action buttons – same as index & hold
            // $editUrl   = backpack_url("booking/{$booking->id}/edit");
            $showUrl   = backpack_url("booking/$booking->id/invoiced-show");
            // $amountUrl = backpack_url("booking/{$booking->id}/add-amount"); // agar route nahi hai to comment kar dena

            $mapped->action = '
        <div class="btn-group btn-group-sm" role="group" aria-label="Actions">

            <a href="' . $showUrl . '"
               class="btn btn-sm btn-primary py-1 px-2" title="View Details">
                View
            </a>
        </div>';

            return $mapped;
        })->values();

        // ────────────────────────────────────────────────
        // Columns – same reusable function
        // ────────────────────────────────────────────────
        $columns = $this->getAgGridColumns();

        // Action column add karo (sirf agar getAgGridColumns() mein nahi hai to)
        // Agar pehle se hai to yeh line comment kar dena
        $columns[] = [
            'headerName'    => 'Action',
            'field'         => 'action',
            'width'         => 160,
            'minWidth'      => 140,
            'sortable'      => false,
            'filter'        => false,
            'resizable'     => false,
            'cellRenderer'  => 'htmlRenderer',
            'pinned'        => 'right',
            'cellClass'     => 'text-center p-0',
            'suppressSizeToFit' => true,
        ];

        // ────────────────────────────────────────────────
        // View ko data pass
        // ────────────────────────────────────────────────
        $this->data['gridConfig'] = [
            'columns' => $columns,
            'data'    => $gridData,
        ];

        // Optional pagination info (agar blade mein use kar rahe ho)
        $this->data['pagination'] = [
            'total'       => $paginatedBookings->total(),
            'perPage'     => $paginatedBookings->perPage(),
            'currentPage' => $paginatedBookings->currentPage(),
            'lastPage'    => $paginatedBookings->lastPage(),
        ];

        return view('booking.list', $this->data);
    }

    public function cancelled()
    {
        $this->crud->hasAccessOrFail('list');

        $this->crud->setListView('admin.booking.list'); // same view

        $this->data['crud'] = $this->crud;
        $this->data['title'] = 'Cancelled Bookings';

        // ────────────────────────────────────────────────
        // Query – Cancelled bookings ke liye (status = 3)
        // ────────────────────────────────────────────────
        $query = $this->getBaseQuery();

        // Cancelled filter
        $query->where('bookings.status', 3);

        // Sorting – latest pehle
        $query->orderBy('bookings.id', 'desc');

        // Pagination
        $paginatedBookings = $query->paginate(50);

        // ────────────────────────────────────────────────
        // Mapping + serial_no + same action buttons
        // ────────────────────────────────────────────────
        $gridData = $paginatedBookings->map(function ($booking, $index) use ($paginatedBookings) {
            $mapped = $this->mapBookingForGrid($booking);  // ya mapBookingForList() agar alag function hai

            // Serial number with pagination
            $mapped->serial_no = ($paginatedBookings->currentPage() - 1) * $paginatedBookings->perPage() + $index + 1;

            // Action buttons – same as index, hold, invoiced
            // $editUrl   = backpack_url("booking/{$booking->id}/edit");
            $showUrl   = backpack_url("booking/{$booking->id}/show");
            // $amountUrl = backpack_url("booking/{$booking->id}/add-amount"); // agar route nahi hai to comment kar dena

            $mapped->action = '
        <div class="btn-group btn-group-sm" role="group" aria-label="Actions">
            <a href="' . $showUrl . '"
               class="btn btn-sm btn-primary py-1 px-2" title="View Details">
                View
            </a>
        </div>';

            return $mapped;
        })->values();

        // ────────────────────────────────────────────────
        // Columns – same reusable function
        // ────────────────────────────────────────────────
        $columns = $this->getAgGridColumns();

        // Action column add karo (sirf agar getAgGridColumns() mein nahi hai to)
        // Agar pehle se hai to yeh line comment kar dena
        $columns[] = [
            'headerName'    => 'Action',
            'field'         => 'action',
            'width'         => 160,
            'minWidth'      => 140,
            'sortable'      => false,
            'filter'        => false,
            'resizable'     => false,
            'cellRenderer'  => 'htmlRenderer',
            'pinned'        => 'right',
            'cellClass'     => 'text-center p-0',
            'suppressSizeToFit' => true,
        ];

        // ────────────────────────────────────────────────
        // View ko data pass
        // ────────────────────────────────────────────────
        $this->data['gridConfig'] = [
            'columns' => $columns,
            'data'    => $gridData,
        ];

        // Optional pagination info
        $this->data['pagination'] = [
            'total'       => $paginatedBookings->total(),
            'perPage'     => $paginatedBookings->perPage(),
            'currentPage' => $paginatedBookings->currentPage(),
            'lastPage'    => $paginatedBookings->lastPage(),
        ];

        return view('booking.list', $this->data);
    }





    protected function setupCreateOperation()
    {
        CRUD::setValidation(BookingRequest::class);
        $this->crud->setCreateView('booking.add');

        $data = [];

        // Yeh sab object banaye — arrow syntax ke liye
        $data['branches']       = collect(CommonHelper::getBranches())->map(fn($b) => (object) $b);
        // $data['allusers']       = collect(XpricingHelper::selectUsers())->map(fn($u) => (object) $u);
        $data['financiers']     = collect(\App\Models\Module\Finance\XlFinancier::select('id', 'name', 'short_name')->get()->toArray())->map(fn($f) => (object) $f);
        $data['saleconsultants'] = collect(XpricingHelper::selectfsc())->map(fn($s) => (object) $s);

        // Segments — sirf ek baar, object bana ke
        // $data['segments']       = collect(XpricingHelper::getSegments())->map(fn($s) => (object) $s);

        // // Yeh initially empty rahenge (AJAX se fill honge)
        // $data['models']         = [];
        // $data['variants']       = [];
        // $data['colors']         = [];

        $data['locations']      = [];
        $data['person_id']      = backpack_auth()->id();

        // DSA Details — object bana do
        $data['dsa_details'] = \App\Models\Xl_DSA_Master::all()->map(function ($dsa) {
            return (object) [
                'id'       => $dsa->id,
                'name'     => $dsa->name,
                'mobile'   => $dsa->mobile,
                'email'    => $dsa->email,
                'location' => $dsa->dlocation,
            ];
        });

        // Enum Master
        $data['enum_master'] = \App\Models\EnumMaster::where('master_id', 94)
            ->where('status', 1)
            ->orderBy('value')
            ->get()
            ->map(fn($em) => (object) ['id' => $em->id, 'value' => $em->value]);

        // Final pass
        $this->data['data'] = $data;
    }


    public function store(Request $request)
    {
        // Debug ke liye (baad mein hata dena)
        // dd($request->all());

        $pending = 0;
        $pendingFields = [];

        // ====== VALIDATION (purane save() jaisi hi, lekin blade field names se) ======
        $validator = Validator::make($request->all(), [
            'customertype' => 'required|string|max:255',
            'user' => 'nullable',
            'hiddenbookingdate' => 'nullable|date',  // booking_date → hiddenbookingdate
            'refrenceno' => 'nullable|string|max:255',  // refrence_no → refrenceno
            'dsadetails' => 'nullable|string|max:255',  // dsa_details → dsadetails
            'branch' => 'required|integer',
            'location' => 'required|integer',
            'segmentid' => 'required|integer',  // segment_id → segmentid
            'model' => 'required|string|max:255',
            'variant' => 'required|string|max:255',
            'color' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'careof' => 'nullable|string|max:255',  // care_of → careof
            'careofname' => 'nullable|string|max:255',  // care_of_name → careofname
            'mobile' => 'required|string|max:15',
            'altmobile' => 'nullable|string|max:15',  // alt_mobile → altmobile
            'panno' => 'nullable|string|max:10',  // pan_no → panno
            'adharno' => 'nullable|string|max:15',  // adhar_no → adharno
            'dmsotf' => 'nullable|string|max:255',  // dms_otf → dmsotf (assuming blade mein yeh hai)
            'dmss o' => 'nullable|string|max:255',  // dms_so → dmss o (fix if needed)
            'chassis' => 'nullable|string|max:255',
            'deliverytype' => 'required|string|max:255',  // delivery_type → deliverytype
            'hiddenexpecteddeldate' => 'nullable|date',  // expected_del_date → hiddenexpecteddeldate
            'finmode' => 'required|string|max:255',  // fin_mode → finmode
            'financier' => 'nullable|string|max:255',
            'loanstatus' => 'nullable|string|max:255',  // loan_status → loanstatus
            'accessories' => 'nullable|array',
            'accessories.*' => 'integer',
            'saleconsultant' => 'required',
            'apackamount' => 'required',  // apack_amount → apackamount
            'seating' => 'nullable|integer',
            'details' => 'nullable|string',
            'referredby' => 'nullable|string|max:255',  // referred_by → referredby
            'refcustomername' => 'nullable|string|max:255',  // ref_customer_name → refcustomername
            'refmobileno' => 'nullable|string|max:15',  // ref_mobile_no → refmobileno
            'refexistingmodel' => 'nullable|string|max:255',  // ref_existing_model → refexistingmodel
            'refvariant' => 'nullable|string|max:255',
            'refchassisregno' => 'nullable|string|max:255',  // ref_chassis_reg_no → refchassisregno
        ]);

        // Dummy ke liye basic validation pass, Actual ke liye extra
        if ($request->customertype != "Dummy") {
            if ($validator->fails()) {
                return redirect()->back()->withInput()->with('error', $validator->messages()->first());
            } else {
                $validator = Validator::make($request->all(), [
                    'bookingsource' => 'required|string|max:255',  // booking_source → bookingsource
                    'hiddenbookingdate' => 'required|date',
                    'bookingamount' => 'required|numeric',  // booking_amount → bookingamount
                    'bookingmode' => 'required|string|max:255',  // booking_mode → bookingmode
                    'coltype' => 'required',  // col_type → coltype
                ]);
                if ($validator->fails()) {
                    return redirect()->back()->withInput()->with('error', $validator->messages()->first());
                }
            }
        }

        // Receipt/Col Type 1 validation
        if ($request->coltype === 1) {
            $validator = Validator::make($request->all(), [
                'receiptno' => 'required|string|max:255',  // receipt_no → receiptno
                'hiddenreceiptdate' => 'required|date',  // receipt_date → hiddenreceiptdate
            ]);
            if ($validator->fails()) {
                return redirect()->back()->withInput()->with('error', $validator->messages()->first());
            }
        }

        // ====== PENDING FIELDS LOGIC (same as save()) ======
        if (is_null($request->input('receiptno'))) {
            $pending++;
            $pendingFields[] = 'Receipt number needs to be updated';
        }
        if (is_null($request->input('hiddenreceiptdate'))) {
            $pending++;
            $pendingFields[] = 'Receipt date needs to be updated';
        }
        if ($request->input('bookingmode') === 'Online') {
            if (is_null($request->input('refrenceno')) || $request->input('refrenceno') === '') {
                $pending++;
                $pendingFields[] = 'Online booking reference number needs to be updated';
            }
        }
        if (is_null($request->input('panno'))) {
            $pending++;
            $pendingFields[] = 'PAN number needs to be updated';
        }
        if (is_null($request->input('adharno'))) {
            $pending++;
            $pendingFields[] = 'Aadhar number needs to be updated';
        }
        if (is_null($request->input('dmsno'))) {  // dms_no → dmsno (assuming blade mein)
            $pending++;
            $pendingFields[] = 'Sales force number needs to be updated';
        }
        if (is_null($request->input('dmsotf'))) {
            $pending++;
            $pendingFields[] = 'DMS OTF needs to be updated';
        }
        if (is_null($request->input('hiddenotfdate'))) {  // hidden_otf_date → hiddenotfdate
            $pending++;
            $pendingFields[] = 'DMS OTF Date needs to be updated';
        }
        if ($request->makeorder == 1) {  // make_order → makeorder
            $pending++;
            $pendingFields[] = 'DMS SO number needs to be updated';
        }

        // ====== BOOKING SAVE (same logic, blade field names) ======
        $customerTypeInput = $request->input('customertype');
        $adhar_no_normalized = preg_replace('/[^0-9]/', '', $request->input('adharno', ''));
        $customerType = ($customerTypeInput === 'Actual' || $customerTypeInput === 'Active') ? 'Active' : $customerTypeInput;

        $booking = new Booking();
        $booking->b_type = $customerType;
        $booking->b_cat = $request->input('customercat');  // customer_cat → customercat
        $booking->b_mode = $request->input('bookingmode');  // booking_mode → bookingmode
        $booking->cpd = $request->input('hiddencpd');  // hidden_cpd → hiddencpd (if exists)
        $booking->col_type = $request->input('coltype') ?? 1;  // col_type → coltype
        $booking->col_by = $request->input('user');
        $booking->b_source = $request->input('bookingsource');  // booking_source → bookingsource
        $booking->dsa_id = $request->input('dsadetails');  // dsa_details → dsadetails
        $booking->online_bk_ref_no = $request->input('refrenceno');  // online_bk_ref_no → refrenceno
        $booking->booking_date = $request->input('hiddenbookingdate');  // booking_date → hiddenbookingdate
        $booking->receipt_no = $request->input('receiptno');  // receipt_no → receiptno
        $booking->receipt_date = $request->input('hiddenreceiptdate');  // receipt_date → hiddenreceiptdate
        $booking->booking_amount = $request->input('bookingamount');  // booking_amount → bookingamount
        $booking->branch_code = $request->input('branch');
        $booking->location_code = $request->input('location');
        $booking->location_other = $request->input('locationother');  // location_other → locationother
        $booking->c_dob = $request->input('hiddencustomerdob');  // c_dob → hiddencustomerdob
        $booking->segment_id = $request->input('segmentid');  // segment_id → segmentid
        $booking->model = $request->input('model');
        $booking->variant = $request->input('variant');
        $booking->color = $request->input('color');
        $booking->vh_id = $request->input('vhid');  // vh_id → vhid
        $booking->order = $request->input('makeorder');  // order → makeorder (assuming)
        $booking->seating = $request->input('seating');
        $booking->person_id = backpack_auth()->id();
        $booking->name = $request->input('name');
        $booking->care_of_type = $request->input('careof');  // care_of_type → careof
        $booking->care_of = $request->input('careofname');  // care_of → careofname
        $booking->mobile = $request->input('mobile');
        $booking->alt_mobile = $request->input('altmobile');  // alt_mobile → altmobile
        $booking->gender = $request->input('gender');
        $booking->occ = $request->input('occupation');  // occ → occupation
        $booking->buyer_type = $request->input('buyertype');  // buyer_type → buyertype
        $booking->exist_oem1 = $request->input('enummaster1');  // enummaster1 → enummaster1 (no _)
        $booking->exist_oem2 = $request->input('enummaster2');
        $booking->vh1_detail = $request->input('vehicledetails');  // vh1_detail → vehicledetails
        $booking->vh2_detail = $request->input('vehicledetails2');
        $booking->registration_no = $request->input('registrationno');  // registration_no → registrationno
        $booking->make_year = $request->input('manufacturingyear');  // manufacturing_year → manufacturingyear
        $booking->odo_reading = $request->input('odometerreading');  // odo_reading → odometerreading
        $booking->expected_price = $request->input('expectedprice');  // expected_price → expectedprice
        $booking->offered_price = $request->input('offeredprice');  // offered_price → offeredprice
        $booking->exchange_bonus = $request->input('exchangebonus');  // exchange_bonus → exchangebonus
        $booking->pan_no = $request->input('panno');  // pan_no → panno
        $booking->adhar_no = $adhar_no_normalized;
        $booking->gstn = $request->input('gstn');
        $booking->dms_otf = $request->input('dmsotf');  // dms_otf → dmsotf
        $booking->dms_so = $request->input('dmss o');  // dms_so → dmss o (fix blade if needed)
        $booking->dms_no = $request->input('dmsno');  // dms_no → dmsno
        $booking->otf_date = $request->input('hiddenotfdate');  // otf_date → hiddenotfdate
        $booking->mapped = 0;
        $booking->chasis_no = $request->input('chassis');  // chasis_no → chassis
        $booking->del_type = $request->input('deliverytype');  // del_type → deliverytype
        $booking->del_date = $request->input('hiddenexpecteddeldate');  // del_date → hiddenexpecteddeldate
        $booking->fin_mode = $request->input('finmode');  // fin_mode → finmode
        $booking->financier = $request->input('financier');
        $booking->loan_status = $request->input('loanstatus');  // loan_status → loanstatus

        // Accessories handling
        if (!empty($request->accessories)) {
            $booking->accessories = implode(',', $request->input('accessories'));
        }

        $booking->apack_amount = $request->input('apackamount');  // apack_amount → apackamount
        $booking->consultant = $request->input('saleconsultant');
        $booking->refferd = $request->input('referredby');  // refferd → referredby
        $booking->r_name = $request->input('refcustomername');  // r_name → refcustomername
        $booking->r_mobile = $request->input('refmobileno');  // r_mobile → refmobileno
        $booking->r_model = $request->input('refexistingmodel');  // r_model → refexistingmodel
        $booking->r_variant = $request->input('refvariant');
        $booking->r_chassis = $request->input('refchassisregno');  // r_chassis → refchassisregno

        $booking->pending = $pending;
        $booking->pending_remark = implode(' , ', $pendingFields);

        if ($pending > 0) {
            $booking->status = 8;  // Pending
        }

        // Dummy customer override
        if ($customerType === 'Dummy') {
            $booking->b_mode = 'Dealer';
            $booking->b_source = 'Dealer';
        }

        $booking->save();

        // ──── 8. File upload ───────────────────────────────────────────────────────────
        $uploadedFilePath = null;

        if ($request->hasFile('amountproof') && $request->file('amountproof')->isValid()) {
            try {
                $file = $request->file('amountproof');

                // Use Laravel's store() method — more reliable than move()
                $storedName = $file->store('temp', 'public');  // stores in storage/app/public/temp/

                // Get full public path
                $uploadedFilePath = public_path('storage/' . $storedName);

                if (!file_exists($uploadedFilePath)) {
                    throw new \Exception("Stored file not found: " . $uploadedFilePath);
                }

                // Optional: make a copy if you really need fn2 (ChatHelper)
                $extension = $file->extension();
                $fn2 = 'tf_ap2_' . date('Ymd_His') . '_' . uniqid() . '.' . $extension;
                $copyPath = public_path('uploads/temp/' . $fn2);

                // Ensure copy folder exists
                $copyDir = dirname($copyPath);
                if (!file_exists($copyDir)) {
                    mkdir($copyDir, 0755, true);
                }

                if (copy($uploadedFilePath, $copyPath)) {
                    Log::info('File copied successfully for ChatHelper', ['copy_path' => $copyPath]);
                } else {
                    Log::warning('File copy failed — ChatHelper may skip file', ['source' => $uploadedFilePath]);
                }

                Log::info('Amount proof stored successfully', [
                    'stored_path' => $uploadedFilePath,
                    'original_name' => $file->getClientOriginalName(),
                    'size' => filesize($uploadedFilePath),
                    'mime' => mime_content_type($uploadedFilePath)
                ]);
            } catch (\Exception $e) {
                Log::error('File upload/store failed', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'original_name' => $file->getClientOriginalName() ?? 'unknown'
                ]);
                $uploadedFilePath = null; // prevent downstream use
            }
        } else if ($request->has('amountproof')) {
            Log::warning('amountproof present but not valid upload', [
                'has_file' => $request->hasFile('amountproof'),
                'error_code' => $request->file('amountproof')?->getError() ?? 'N/A'
            ]);
        }
        // ====== BOOKINGAMOUNT ENTRY (col_type 1/4) ======
        $number = $request->input('receiptno') ?? $request->input('voucherno');  // voucherno if exists
        if (in_array($booking->col_type, [1, 4]) && $booking->booking_amount > 0 && $number) {
            $payment = new Bookingamount();
            $payment->bid = $booking->id;
            $payment->date = $request->input('hiddenreceiptdate') ?? now();
            $payment->amount = $booking->booking_amount;
            $payment->reciept = $number;
            $payment->voucher = ($booking->col_type == 4) ? 1 : 0;
            $payment->save();

            if ($uploadedFilePath && file_exists($uploadedFilePath)) {
                $payment->addMedia($uploadedFilePath)->toMediaCollection('amount-proof');
                Log::info('Media attached to Bookingamount', ['path' => $uploadedFilePath]);
            } else {
                Log::warning('No valid file to attach to media collection');
            }
        }

        // ====== XEXCHANGE ENTRY (if buyer_type == Exchange Buy) ======
        if ($request->has('buyertype') && $request->input('buyertype') === 'Exchange Buy') {
            $exchange = new XExchange();
            $exchange->bid = $booking->id;
            $exchange->vh_id = $booking->vh_id;
            $exchange->verification_status = 1; // Unverified
            $exchange->case_status = 1; // In-Process
            $exchange->purchase_type = $request->input('buyertype');  // buyer_type → buyertype
            $exchange->save();
        }

        // ====== XFINANCE ENTRY (if fin_mode == In-house) ======
        if ($request->has('finmode') && $request->input('finmode') === 'In-house') {
            $finance = new XFinance();
            $finance->bid = $booking->id;
            $finance->vh_id = $booking->vh_id;
            $finance->verification_status = 1; // Unverified
            $finance->case_status = 1; // In-Process
            $finance->save();
        }


        // ====== CHATHELPER (same as save()) ======
        // ChatHelper block
        try {
            ChatHelper::add_communication(3, "Booking Created", "Booking created successfully", $booking->id);
            $commid = ChatHelper::get_commid(3, $booking->id, "Booking Created");

            $chatFilePath = $copyPath ?? $uploadedFilePath ?? null; // prefer copy, fallback to stored

            if ($chatFilePath && file_exists($chatFilePath)) {
                ChatHelper::add_followup($commid, $request->input('details'), "Booking Created", $chatFilePath, 1);
                Log::info('ChatHelper followup with file added');
            } else {
                ChatHelper::add_followup($commid, $request->input('details'), "Booking Created", null, 1);
                Log::info('ChatHelper followup added (no file)');
            }
        } catch (\Exception $e) {
            Log::error('ChatHelper failed', ['error' => $e->getMessage()]);
        }

        // Backpack redirect (index route pe jayega)
        return redirect(backpack_url('booking'))->with('success', 'Booking added successfully!');
    }



    protected function setupUpdateOperation()
    {
        // Same validation as create (reuse BookingRequest if needed)
        CRUD::setValidation(BookingRequest::class);

        // Custom edit view
        $this->crud->setEditView('booking.edit');

        // === ID se booking fetch karo ===
        $id = $this->crud->getCurrentEntryId() ?? $this->crud->getRequest()->id;
        $entry = $this->crud->getEntry($id);

        // === Pura data taiyar karo — bilkul old getFullBookingData() jaisa ===
        $data = [];

        // Basic helpers (same as create)
        $data['branches'] = collect(CommonHelper::getBranches())->map(fn($b) => (object)$b);
        $data['allusers'] = collect(XpricingHelper::selectUsers())->map(fn($u) => (object)$u);
        $data['saleconsultants'] = collect(XpricingHelper::selectfsc())->map(fn($s) => (object)$s);
        $data['financiers'] = collect(\App\Models\XlFinancier::select('id', 'name', 'short_name')->get()->toArray())
            ->map(fn($f) => (object)$f);

        $data['segments'] = collect(XpricingHelper::getSegments() ?? [])->map(function ($s) {
            $segId = $s['id'] ?? null;
            $segName = $s['name'] ?? CommonHelper::enumValueById($segId); // Extra safety

            // Final fallback if still empty
            if (empty($segName)) {
                $segName = 'Segment ID ' . $segId;
            }

            return [
                'id'    => $segId,
                'value' => $segName
            ];
        })->filter()->values();
        // dd($data['segments']);


        $data['models'] = [];
        if ($entry->segment_id) {
            $data['models'] = XpricingHelper::getModelsX($entry->segment_id) ?? [];
        }

        // Pre-load variants for current model
        $data['variants'] = [];
        if ($entry->model) {
            $data['variants'] = XpricingHelper::getVehiclesX($entry->model) ?? [];
        }

        // Pre-load colors for current variant (with data-code and data-vid)
        $data['colors'] = [];
        if ($entry->variant) {
            $data['colors'] = XpricingHelper::getColorX($entry->variant) ?? [];
        }

        // DSA Details
        $data['dsa_details'] = \App\Models\Xl_DSA_Master::all()->map(function ($dsa) {
            return (object)[
                'id'       => $dsa->id,
                'name'     => $dsa->name,
                'mobile'   => $dsa->mobile,
                'email'    => $dsa->email,
                'location' => $dsa->dlocation,
            ];
        });

        // Enum Master for OEM makes
        $data['enum_master'] = \App\Models\EnumMaster::where('master_id', 94)
            ->where('status', 1)
            ->orderBy('value')
            ->get()
            ->map(fn($em) => (object)['id' => $em->id, 'value' => $em->value]);

        // === Edit-specific data (jo dropdowns ko pre-populate karne ke liye chahiye) ===
        $data['locations'] = [];
        if ($entry->branch_code) {
            $locations = XCommonHelper::getLocations($entry->branch_code) ?? [];
            usort($locations, fn($a, $b) => strcmp(($a['name'] ?? '') . ' - ' . ($a['code'] ?? ''), ($b['name'] ?? '') . ' - ' . ($b['code'] ?? '')));
            $data['locations'] = $locations;
        }

        // Accessories dropdown (current segment/model/variant ke basis pe)
        $segmentName = CommonHelper::enumValueById($entry->segment_id ?? 0) ?? '';
        $data['accessories_dropdown'] = XpricingHelper::getAccessories(
            $segmentName,
            $entry->model ?? '',
            $entry->variant ?? ''
        ) ?? [];

        // Chassis dropdown ke liye (agar current chassis hai to uska model_code use karo)
        $data['chassis_list'] = [];
        if ($entry->chasis_no) {
            $stock = \App\Models\Stock::find($entry->chasis_no);
            if ($stock && $stock->model_code) {
                $data['chassis_list'] = \App\Models\Stock::where('model_code', $stock->model_code)
                    ->select('chasis_no', 'id')
                    ->get()
                    ->toArray();
            }
        }

        // Collector name display ke liye (old logic)
        $data['collector_name'] = '—';
        if ($entry->col_type == 2) {
            $user = $data['allusers']->firstWhere('id', $entry->col_by);
            $data['collector_name'] = $user ? $user->name . ' - (' . $user->emp_code . ')' : '—';
        } elseif ($entry->col_type == 3) {
            $dsa = $data['dsa_details']->firstWhere('id', $entry->col_by);
            $data['collector_name'] = $dsa ? $dsa->name . ' - ' . $dsa->mobile : '—';
        }

        // dd([
        //     "Enum Master Count"          => $data['enum_master']->count(),
        //     "Sample first 5 records"     => $data['enum_master']->take(5)->toArray(),
        //     "Does 19747 exist?"          => $data['enum_master']->contains('id', 19747),
        //     "Does 19723 exist?"          => $data['enum_master']->contains('id', 19723),
        //     "Name of ID 19747 (if found)" => $data['enum_master']->firstWhere('id', 19747)?->value ?? 'NOT FOUND',
        //     "Name of ID 19723 (if found)" => $data['enum_master']->firstWhere('id', 19723)?->value ?? 'NOT FOUND',
        //     "Raw exist_oem1"             => $entry->exist_oem1,
        //     "Raw exist_oem2"             => $entry->exist_oem2
        // ]);

        // Final assign
        $this->data['entry'] = $entry;
        $this->data['data'] = $data;
        //dd($this->data);
        $this->crud->set('data', $data);
    }

    public function addAmountForm($id)
    {
        $booking = Booking::findOrFail($id);

        // Optional: pass already added receipts for display/history
        $receipts = Bookingamount::where('bid', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('booking.amount', compact('booking', 'receipts'));
    }

    public function addAmount(Request $request, $id)
    {
        try {
            $booking = Booking::findOrFail($id);

            // Optional safety check
            if ($request->filled('bid') && $request->bid != $id) {
                throw new \Exception('Booking ID mismatch.');
            }

            $validator = Validator::make($request->all(), [
                'hidden_receipt_date' => 'required|date_format:Y-m-d',
                'reciept_no' => [
                    'required',
                    'string',
                    'max:255',

                ],
                'amount' => 'required|numeric|min:0.01',
                'amount_proof' => 'required|file|mimes:jpeg,png,jpg,pdf|max:2048',
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors($validator)
                    ->with('error', $validator->errors()->first());
            }

            return DB::transaction(function () use ($request, $booking) {

                $tempDir = public_path('Uploads/temp/');
                if (!File::exists($tempDir)) {
                    File::makeDirectory($tempDir, 0755, true);
                }

                $file     = $request->file('amount_proof');
                $ext      = $file->extension();
                $fileName = 'tf_ap_' . date('d-m-Y_His') . '.' . $ext;

                // Move file temporarily
                $file->move($tempDir, $fileName);

                // Create receipt record
                $amountRecord = new Bookingamount();
                $amountRecord->bid     = $booking->id;
                $amountRecord->date    = $request->hidden_receipt_date;
                $amountRecord->amount  = $request->amount;
                $amountRecord->reciept = $request->reciept_no;
                $amountRecord->save();

                // Attach file using Spatie Media Library (assuming you're using it)
                $amountRecord->addMedia($tempDir . $fileName)
                    ->toMediaCollection('amount-proof');

                // ────────────────────────────────────────────────
                // Your business logic below – kept almost as-is
                // ────────────────────────────────────────────────

                $remarks = [];

                $oldReceipt = $booking->receipt_no;
                $oldDate    = $booking->receipt_date;
                $newAmount  = $request->amount;
                $newReceipt = $request->reciept_no;
                $newDate    = $request->hidden_receipt_date;

                if ($oldReceipt !== $newReceipt) {
                    $remarks[] = "Receipt No. changed from " . ($oldReceipt ?? 'N/A') . " to $newReceipt";
                }
                if ($oldDate !== $newDate) {
                    $remarks[] = "Receipt Date changed from " . ($oldDate ?? 'N/A') . " to $newDate";
                }
                if ($newAmount > 0) {
                    $remarks[] = "Amount received: $newAmount";
                }

                // Dummy → Active conversion
                $wasDummy = strtolower($booking->b_type ?? '') === 'dummy';
                if ($wasDummy) {
                    $remarks[] = "Booking activated from Dummy to Active";
                }

                // Update booking totals & flags
                $oldBookingAmount = $booking->booking_amount ?? 0;
                $booking->receipt_no     = $newReceipt;
                $booking->receipt_date   = $newDate;
                $booking->booking_amount = $oldBookingAmount + $newAmount;
                $booking->b_type         = 'Active';

                $remarks[] = "Booking amount updated from $oldBookingAmount to {$booking->booking_amount}";

                // Pending fields logic (only if was dummy)
                if ($wasDummy) {
                    $pending = 0;
                    $pendingFields = [];

                    if ($booking->b_mode === 'Online' && empty($booking->online_bk_ref_no)) {
                        $pending++;
                        $pendingFields[] = 'Online booking reference number';
                    }
                    if (empty($booking->pan_no)) {
                        $pending++;
                        $pendingFields[] = 'PAN number';
                    }
                    if (empty($booking->adhar_no)) {
                        $pending++;
                        $pendingFields[] = 'Aadhar number';
                    }
                    if (empty($booking->dms_no)) {
                        $pending++;
                        $pendingFields[] = 'Sales force number';
                    }
                    if (empty($booking->dms_otf)) {
                        $pending++;
                        $pendingFields[] = 'DMS OTF';
                    }
                    if (empty($booking->otf_date)) {
                        $pending++;
                        $pendingFields[] = 'DMS OTF Date';
                    }
                    if (empty($booking->dms_so)) {
                        $pending++;
                        $pendingFields[] = 'DMS SO number';
                    }

                    $booking->pending        = $pending;
                    $booking->pending_remark = implode(', ', $pendingFields);
                    $booking->status         = $pending > 0 ? 8 : 0; // 8 = pending, 0 = complete?
                }

                $booking->save();

                // Logging / follow-up
                if (!empty($remarks)) {
                    try {
                        $commid = ChatHelper::get_commid(3, $booking->id, "Booking Created");
                        ChatHelper::add_followup(
                            $commid,
                            "Booking Amount Updated",
                            implode(", ", $remarks),
                            $tempDir . $fileName,  // might not be needed anymore
                            1
                        );
                    } catch (\Exception $e) {
                        Log::warning("ChatHelper failed: " . $e->getMessage());
                    }
                }

                // Cleanup
                if (File::exists($tempDir . $fileName)) {
                    File::delete($tempDir . $fileName);
                }

                return redirect()
                    ->route('booking.index')   // or 'bookings' if that's your route name
                    ->with('success', 'Amount & receipt added successfully!');
            });
        } catch (\Exception $e) {
            Log::error("addAmount failed: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Something went wrong. Please try again.');
        }
    }

    public function addReceipt(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'hidden_receipt_date' => 'required|date_format:Y-m-d',
            'reciept_no' => [
                'required',
                'string',
                'max:255',
                Rule::unique('xlr8_booking_amount', 'reciept')
                    ->whereNull('deleted_at')
                    ->ignore($request->input('receipt_id')), // agar edit mode mein ho to ignore
            ],
            'amount' => 'required|numeric|min:0.01',
            'amount_proof' => 'required|file|mimes:jpeg,png,jpg,pdf|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withInput()
                ->withErrors($validator)
                ->with('error', $validator->errors()->first());
        }

        try {
            return DB::transaction(function () use ($request, $booking) {

                $receiptDate = $request->input('hidden_receipt_date');
                $receiptNo   = $request->input('reciept_no');
                $amount      = (float) $request->input('amount');

                // Create receipt record
                $receipt = new Bookingamount();
                $receipt->bid     = $booking->id;
                $receipt->date    = $receiptDate;
                $receipt->amount  = $amount;
                $receipt->reciept = $receiptNo;
                $receipt->save();

                // Attach proof directly (no temp file needed)
                $receipt->addMediaFromRequest('amount_proof')
                    ->toMediaCollection('amount-proof');

                // Remarks for logging
                $remarks = [];
                if ($booking->receipt_no !== $receiptNo) {
                    $remarks[] = "Receipt No. updated to {$receiptNo}";
                }
                if ($booking->receipt_date !== $receiptDate) {
                    $remarks[] = "Receipt Date updated to {$receiptDate}";
                }
                $remarks[] = "Amount received: {$amount}";

                // Update latest receipt info on booking (if your logic requires it)
                $booking->receipt_no   = $receiptNo;
                $booking->receipt_date = $receiptDate;

                // Calculate total received
                $totalReceived = Bookingamount::where('bid', $booking->id)
                    ->whereNull('deleted_at')
                    ->sum('amount');

                // Activate if full amount received
                if ($totalReceived >= ($booking->booking_amount ?? 0) && $booking->booking_amount > 0) {
                    if (strtolower($booking->b_type) === 'dummy') {
                        $remarks[] = "Booking activated from Dummy to Active (Full amount received)";
                    }
                    $booking->b_type = 'Active';
                }

                // If still dummy → check pending KYC fields
                if (strtolower($booking->b_type) === 'dummy') {
                    $pendingFields = [];
                    if ($booking->b_mode === 'Online' && empty($booking->online_bk_ref_no)) {
                        $pendingFields[] = 'Online booking reference number';
                    }
                    if (empty($booking->pan_no))     $pendingFields[] = 'PAN number';
                    if (empty($booking->adhar_no))   $pendingFields[] = 'Aadhar number';
                    if (empty($booking->dms_no))     $pendingFields[] = 'Sales force number';
                    if (empty($booking->dms_otf))    $pendingFields[] = 'DMS OTF';
                    if (empty($booking->otf_date))   $pendingFields[] = 'DMS OTF Date';
                    if (empty($booking->dms_so))     $pendingFields[] = 'DMS SO number';

                    $booking->pending        = count($pendingFields);
                    $booking->pending_remark = implode(', ', $pendingFields);
                    $booking->status         = count($pendingFields) > 0 ? 8 : 0;
                }

                $booking->save();

                // Log changes
                if (!empty($remarks)) {
                    try {
                        $commid = ChatHelper::get_commid(3, $booking->id, "Booking Created");
                        ChatHelper::add_followup(
                            $commid,
                            "Receipt Added",
                            implode(", ", $remarks),
                            null,  // no file path needed
                            1
                        );
                    } catch (\Exception $e) {
                        Log::warning("ChatHelper failed: " . $e->getMessage());
                    }
                }

                $redirectUrl = route('booking.pending-edit', $booking->id);

                if ($request->boolean('pending_flag') || $request->has('pending_flag')) {
                    $redirectUrl .= '?pending_flag=1';
                }

                return redirect($redirectUrl)
                    ->with('success', 'Receipt added successfully!');
            });
        } catch (\Exception $e) {
            Log::error('addReceipt failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'booking_id' => $id
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to add receipt. Please try again.');
        }
    }

    public function update(Request $request, $id)
    {
        // dd($request->all()); // ← जैसा तुमने कहा था, यही रहेगा

        $booking = Booking::findOrFail($id);

        // Old values for comparison (remarks में सही old name दिखाने के लिए)
        $old_col_type = $booking->col_type;
        $old_col_by   = $booking->col_by;

        // Required data for lookups (consultants, users, DSA)
        $data['saleconsultants'] = XpricingHelper::selectfsc();
        $data['allusers']       = XpricingHelper::selectUsers();

        $dsaRecords = Xl_DSA_Master::all();
        $data['dsa_details'] = $dsaRecords->map(function ($dsa) {
            return [
                'id'       => $dsa->id,
                'name'     => $dsa->name,
                'mobile'   => $dsa->mobile,
                'email'    => $dsa->email,
                'location' => $dsa->dlocation,
            ];
        })->toArray();

        // Base validation rules (common for all bookings)
        $rules = [
            'branch'               => 'required|integer',
            'location_code'          => 'required|integer',
            'segment_id'           => 'required|integer',
            'model'                => 'required|string|max:255',
            'variant'              => 'required|string|max:255',
            'color'                => 'required|string|max:255',
            'name'                 => 'required|string|max:255',
            'care_of'              => 'nullable|string|max:255',
            'care_of_name'         => 'nullable|string|max:255',
            'mobile'               => 'required|string|max:15',
            'alt_mobile'           => 'nullable|string|max:15',
            'pan_no'               => 'nullable|string|max:10',
            'adhar_no'             => 'nullable|string|max:20',
            'dms_otf'              => 'nullable|string|max:255',
            'dms_so'               => 'nullable|string|max:255',
            'chassis'              => 'nullable|string|max:255',
            'delivery_type'        => 'required|string|in:Expected,Confirmed',
            'expected_del_date_actual' => 'nullable|date',
            'fin_mode'             => 'required|string|max:255',
            'financier'            => 'nullable|integer',
            'loan_status'          => 'nullable|string|max:255',
            'accessories'          => 'nullable|array',
            'accessories.*'        => 'integer',
            'saleconsultant'       => 'required|integer',
            'apack_amount'         => 'required|numeric',
            'seating'              => 'nullable|integer',
            'details'              => 'nullable|string',
            'referred_by'          => 'nullable|string|max:255',
            'ref_customer_name'    => 'nullable|string|max:255',
            'ref_mobile_no'        => 'nullable|string|max:15',
            'ref_existing_model'   => 'nullable|string|max:255',
            'ref_variant'          => 'nullable|string|max:255',
            'ref_chassis_reg_no'   => 'nullable|string|max:255',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Pending fields count & remarks
        $pending       = 0;
        $pendingFields = [];

        if ($request->input('booking_mode') === 'Online' && empty($request->input('refrence_no'))) {
            $pending++;
            $pendingFields[] = 'Online booking reference number needs to be updated';
        }

        if (empty($request->input('pan_no'))) {
            $pending++;
            $pendingFields[] = 'PAN number needs to be updated';
        }

        if (empty($request->input('adhar_no'))) {
            $pending++;
            $pendingFields[] = 'Aadhar number needs to be updated';
        }

        // Normalize Aadhar (only digits)
        $adhar_no_normalized = preg_replace('/[^0-9]/', '', $request->input('adhar_no', ''));

        // Remarks array
        $rem = [];

        // Helper for financier name
        $getFinancierName = function ($fid) {
            if (empty($fid)) return 'Null';
            $f = XlFinancier::find($fid);
            return $f ? $f->name : 'Unknown';
        };

        // All field comparisons & remarks
        if ($booking->b_type != $request->customer_type) {
            $rem[] = "Customer Type Changed from " . ($booking->b_type ?? 'null') . " to " . $request->customer_type;
            $booking->b_type = $request->customer_type;
        }

        if ($booking->b_mode != $request->booking_mode) {
            $rem[] = "Booking Mode Changed from " . ($booking->b_mode ?? 'null') . " to " . $request->booking_mode;
            $booking->b_mode = $request->booking_mode;
        }

        if ($old_col_type != $request->col_type) {
            $colTypeMap = [
                '1' => 'Receipt',
                '2' => 'Field Collection By Sales Team',
                '3' => 'Field Collection By DSA',
                '4' => 'Used Car Purchase'
            ];
            $oldName = $colTypeMap[$old_col_type] ?? 'null';
            $newName = $colTypeMap[$request->col_type] ?? 'null';
            $rem[] = "Collection Type Changed from {$oldName} to {$newName}";
        }

        if ($old_col_by != $request->user) {
            $oldUser = 'null';
            $newUser = 'null';

            if ($old_col_type == 2) {
                $u = collect($data['allusers'])->firstWhere('id', $old_col_by);
                $oldUser = $u ? $u['name'] . ' - (' . $u['emp_code'] . ')' : 'null';
            } elseif ($old_col_type == 3) {
                $d = collect($data['dsa_details'])->firstWhere('id', $old_col_by);
                $oldUser = $d ? $d['name'] . ' - ' . $d['mobile'] : 'null';
            }

            if ($request->col_type == 2) {
                $u = collect($data['allusers'])->firstWhere('id', $request->user);
                $newUser = $u ? $u['name'] . ' - (' . $u['emp_code'] . ')' : 'null';
            } elseif ($request->col_type == 3) {
                $d = collect($data['dsa_details'])->firstWhere('id', $request->user);
                $newUser = $d ? $d['name'] . ' - ' . $d['mobile'] : 'null';
            }

            $rem[] = "Collected By Changed from {$oldUser} to {$newUser}";
        }

        if ($booking->consultant != $request->saleconsultant) {
            $oldC = collect($data['saleconsultants'])->firstWhere('id', $booking->consultant);
            $newC = collect($data['saleconsultants'])->firstWhere('id', $request->saleconsultant);
            $rem[] = "Sale Consultant Changed from " . ($oldC['name'] ?? 'null') . " to " . ($newC['name'] ?? 'null');
            $booking->consultant = $request->saleconsultant;
        }

        if ($booking->cpd != $request->cpd_actual) {
            $rem[] = "CPD Date Changed from " . ($booking->cpd ? Carbon::parse($booking->cpd)->format('d-M-Y') : 'null') . " to " . $request->cpd_actual;
            $booking->cpd = $request->cpd_actual;
        }

        if ($booking->online_bk_ref_no  != $request->refrence_no) {
            $rem[] = "Reference No Changed from " . ($booking->online_bk_ref_no  ?? '0') . " to " . $request->refrence_no;
            $booking->online_bk_ref_no  = $request->refrence_no;
        }

        if ($booking->b_source != $request->booking_source) {
            $rem[] = "Booking Source Changed from " . ($booking->b_source ?? 'null') . " to " . $request->booking_source;
            $booking->b_source = $request->booking_source;
        }

        if ($booking->booking_date != $request->booking_date_actual) {
            $rem[] = "Booking Date Changed from " . ($booking->booking_date ? Carbon::parse($booking->booking_date)->format('d-M-Y') : 'null') . " to " . Carbon::parse($request->booking_date_actual)->format('d-M-Y');
            $booking->booking_date = $request->booking_date_actual;
        }

        if ($booking->booking_amount != $request->booking_amount) {
            $rem[] = "Booking Amount Changed from " . ($booking->booking_amount ?? '0') . " to " . $request->booking_amount;
            $booking->booking_amount = $request->booking_amount;
        }

        if ($booking->branch_code != $request->branch) {
            $oldBranch = X_Branch::find($booking->branch_code)?->name ?? 'null';
            $newBranch = X_Branch::find($request->branch)?->name ?? 'null';
            $rem[] = "Branch Changed from {$oldBranch} to {$newBranch}";
            $booking->branch_code = $request->branch;
        }

        if ($booking->location_code != $request->location_code) {
            $rem[] = "Location Changed from " . $booking->location_code . " to " . $request->location_code;
            $booking->location_code = $request->location_code;
        }

        if ($booking->location_other != $request->location_other) {
            $rem[] = "Location Other Changed from " . ($booking->location_other ?? '0') . " to " . $request->location_other;
            $booking->location_other = $request->location_other;
        }

        if ($booking->segment_id != $request->segment_id) {
            $rem[] = "Segment Changed from " . CommonHelper::enumValueById($booking->segment_id) . " to " . CommonHelper::enumValueById($request->segment_id);
            $booking->segment_id = $request->segment_id;
        }

        if ($booking->model != $request->model) {
            $rem[] = "Model Changed from " . $booking->model . " to " . $request->model;
            $booking->model = $request->model;
        }

        if ($booking->variant != $request->variant) {
            $rem[] = "Variant Changed from " . $booking->variant . " to " . $request->variant;
            $booking->variant = $request->variant;
        }

        if ($booking->color != $request->color) {
            $rem[] = "Color Changed from " . $booking->color . " to " . $request->color;
            $booking->color = $request->color;
        }

        if ($booking->seating != $request->seating) {
            $rem[] = "Seating Changed from " . ($booking->seating ?? '0') . " to " . $request->seating;
            $booking->seating = $request->seating;
        }

        if ($booking->name != $request->name) {
            $rem[] = "Name Changed from " . $booking->name . " to " . $request->name;
            $booking->name = $request->name;
        }

        if ($booking->care_of_type != $request->care_of) {
            $rem[] = "Care Of Type Changed";
            $booking->care_of_type = $request->care_of;
        }

        if ($booking->care_of != $request->care_of_name) {
            $oldCare = $booking->care_of ?? 'None';
            $newCare = $request->care_of_name ?? 'None';
            $rem[] = "Care Of Changed from {$oldCare} to {$newCare}";
            $booking->care_of = $request->care_of_name;
        }

        if ($booking->mobile != $request->mobile) {
            $rem[] = "Mobile Changed from " . $booking->mobile . " to " . $request->mobile;
            $booking->mobile = $request->mobile;
        }

        if ($booking->alt_mobile != $request->alt_mobile) {
            $rem[] = "Alt Mobile Changed from " . ($booking->alt_mobile ?? '0') . " to " . $request->alt_mobile;
            $booking->alt_mobile = $request->alt_mobile;
        }

        if ($booking->gender != $request->gender) {
            $rem[] = "Gender Changed from " . ($booking->gender ?? '0') . " to " . $request->gender;
            $booking->gender = $request->gender;
        }

        if ($booking->occ != $request->occupation) {
            $rem[] = "Occupation Changed from " . ($booking->occ ?? '0') . " to " . $request->occupation;
            $booking->occ = $request->occupation;
        }

        if ($booking->buyer_type != $request->buyer_type) {
            $rem[] = "Buyer Type Changed from " . ($booking->buyer_type ?? '0') . " to " . $request->buyer_type;
            $booking->buyer_type = $request->buyer_type;
        }

        // Purchase fields (exchange/scrappage etc.) – तुम्हारे नामों के हिसाब से
        if ($booking->exist_oem1 != $request->enum_master1) {
            $rem[] = "Brand (Make 1) Changed";
            $booking->exist_oem1 = $request->enum_master1;
        }

        if ($booking->vh1_detail != $request->vehicle_details) {
            $rem[] = "Model & Variant 1 Changed";
            $booking->vh1_detail = $request->vehicle_details;
        }

        if ($booking->exist_oem2 != $request->enum_master2) {
            $rem[] = "Brand (Make 2) Changed";
            $booking->exist_oem2 = $request->enum_master2;
        }

        if ($booking->vh2_detail != $request->vehicle_details2) {
            $rem[] = "Model & Variant 2 Changed";
            $booking->vh2_detail = $request->vehicle_details2;
        }

        if ($booking->registration_no != $request->registration_no) {
            $rem[] = "Vehicle Registration No Changed";
            $booking->registration_no = $request->registration_no;
        }

        if ($booking->make_year != $request->manufacturing_year) {
            $rem[] = "Manufacturing Year Changed";
            $booking->make_year = $request->manufacturing_year;
        }

        if ($booking->odo_reading != $request->odometer_reading) {
            $rem[] = "Odometer Reading Changed";
            $booking->odo_reading = $request->odometer_reading;
        }

        if ($booking->expected_price != $request->expected_price) {
            $rem[] = "Expected Price Changed";
            $booking->expected_price = $request->expected_price;
        }

        if ($booking->offered_price != $request->offered_price) {
            $rem[] = "Offered Price Changed";
            $booking->offered_price = $request->offered_price;
        }

        if ($booking->exchange_bonus != $request->exchange_bonus) {
            $rem[] = "Exchange Bonus Changed";
            $booking->exchange_bonus = $request->exchange_bonus;
        }

        if ($booking->pan_no != $request->pan_no) {
            $rem[] = "Pan No Changed from " . ($booking->pan_no ?? '0') . " to " . $request->pan_no;
            $booking->pan_no = $request->pan_no;
        }

        if ($booking->adhar_no != $adhar_no_normalized) {
            $rem[] = "Adhar No Changed from " . ($booking->adhar_no ?? '0') . " to " . $adhar_no_normalized;
            $booking->adhar_no = $adhar_no_normalized;
        }

        if ($booking->c_dob != $request->hidden_customer_dob) {
            $oldDob = $booking->c_dob ? Carbon::parse($booking->c_dob)->format('d-M-Y') : 'null';
            $newDob = $request->hidden_customer_dob ? Carbon::parse($request->hidden_customer_dob)->format('d-M-Y') : 'null';
            $rem[] = "Customer D.O.B. Changed from {$oldDob} to {$newDob}";
            $booking->c_dob = $request->hidden_customer_dob;
        }

        if ($booking->del_type != $request->delivery_type) {
            $rem[] = "Delivery Type Changed from " . ($booking->del_type ?? 'null') . " to " . $request->delivery_type;
            $booking->del_type = $request->delivery_type;
        }

        if ($booking->del_date != $request->expected_del_date_actual) {
            $oldDate = $booking->del_date ? Carbon::parse($booking->del_date)->format('d-M-Y') : 'null';
            $newDate = $request->expected_del_date_actual ? Carbon::parse($request->expected_del_date_actual)->format('d-M-Y') : 'null';
            $rem[] = "Expected Delivery Date Changed from {$oldDate} to {$newDate}";
            $booking->del_date = $request->expected_del_date_actual;
        }

        if ($booking->fin_mode != $request->fin_mode) {
            $rem[] = "Fin Mode Changed from " . ($booking->fin_mode ?? 'null') . " to " . $request->fin_mode;
            $booking->fin_mode = $request->fin_mode;
        }

        if ($booking->financier != $request->financier) {
            $oldF = $getFinancierName($booking->financier);
            $newF = $getFinancierName($request->financier);
            $rem[] = "Financier Changed from {$oldF} to {$newF}";
            $booking->financier = $request->financier;
        }

        if ($booking->loan_status != $request->loan_status) {
            $rem[] = "Loan Status Changed from " . ($booking->loan_status ?? 'null') . " to " . ($request->loan_status ?? 'null');
            $booking->loan_status = $request->loan_status;
        }

        $accessoriesString = $request->has('accessories') && $request->accessories ? implode(',', $request->accessories) : null;
        if ($booking->accessories != $accessoriesString) {
            $rem[] = "Accessories Changed";
            $booking->accessories = $accessoriesString;
        }

        if ($booking->apack_amount != $request->apack_amount) {
            $rem[] = "Apack Amount Changed from " . ($booking->apack_amount ?? '0') . " to " . $request->apack_amount;
            $booking->apack_amount = $request->apack_amount;
        }

        if ($booking->chasis_no != $request->chassis) {
            $rem[] = "Chasis No Changed from " . ($booking->chasis_no ?? 'null') . " to " . ($request->chassis ?? 'null');
            $booking->chasis_no = $request->chassis;
        }

        if ($booking->dms_otf != $request->dms_otf) {
            $rem[] = "DMS OTF Changed";
            $booking->dms_otf = $request->dms_otf;
        }



        // Referred by fields
        if ($booking->r_name != $request->r_name) {
            $rem[] = "Referred Name Changed";
            $booking->r_name = $request->r_name;
        }

        if ($booking->r_mobile != $request->r_mobile) {
            $rem[] = "Referred Mobile Changed";
            $booking->r_mobile = $request->r_mobile;
        }

        if ($booking->r_model != $request->r_model) {
            $rem[] = "Referred Model Changed";
            $booking->r_model = $request->r_model;
        }

        if ($booking->r_variant != $request->r_variant) {
            $rem[] = "Referred Variant Changed";
            $booking->r_variant = $request->r_variant;
        }

        if ($booking->r_chassis != $request->r_chassis) {
            $rem[] = "Referred Chassis Changed";
            $booking->r_chassis = $request->r_chassis;
        }

        if ($booking->order != $request->input('make_order')) {
            if ($booking->order == 0 && $request->input('make_order') == 1) {
                $rem[] = "Requested to order";
            } elseif ($booking->order == 1 && $request->input('make_order') == 0) {
                $rem[] = "Cancelled request for order";
            }
            $booking->order = $request->input('make_order');
        }

        // Final delayed updates
        $booking->col_type = $request->col_type;
        $booking->col_by   = $request->user;
        $booking->vh_id    = $request->input('vh_id');

        // Pending status
        $booking->pending        = $pending;
        $booking->pending_remark = !empty($pendingFields) ? implode(' , ', $pendingFields) : null;
        if ($pending > 0) {
            $booking->status = 8;
        }

        $booking->save();

        // Create XExchange if Exchange Buy and not exists
        if ($request->filled('buyer_type') && $request->buyer_type === 'Exchange Buy') {
            if (!XExchange::where('bid', $booking->id)->exists()) {
                XExchange::create([
                    'bid'                 => $booking->id,
                    'vh_id'               => $booking->vh_id,
                    'verification_status' => 1,
                    'case_status'         => 1,
                    'purchase_type'       => $request->buyer_type,
                ]);
                $rem[] = "New exchange entry created with Verification Status: Unverified and Case Status: In-Process for Exchange Buy";
            }
        }

        // Create XFinance if In-house and not exists
        if ($request->fin_mode === 'In-house') {
            if (!XFinance::where('bid', $booking->id)->exists()) {
                XFinance::create([
                    'bid'                 => $booking->id,
                    'vh_id'               => $booking->vh_id,
                    'fin_mode'            => 'In-house',
                    'verification_status' => 1,
                    'case_status'         => 1,
                ]);
                $rem[] = "New finance entry created with Verification Status: Unverified and Case Status: In-Process for In-house financing";
            }
        }

        // Log remarks in chat
        if (!empty($rem)) {
            $commid = ChatHelper::get_commid(3, $booking->id, "Booking Created");
            ChatHelper::add_followup($commid, "Booking Updated" . $request->details, implode(" , ", $rem), null, 1);
        }

        return redirect(config('backpack.base.route_prefix', 'admin') . '/booking')
            ->with('success', 'Booking updated successfully!');
    }

    public function storeFollowup(Request $request)
    {
        $user = backpack_auth()->user(); // better to get full user object early
        $userId   = $user?->id   ?? 'guest/unknown';
        $userName = $user?->name ?? 'system/unknown';

        Log::info('BOOKING_FOLLOWUP_START', [
            'user_id'   => $userId,
            'user_name' => $userName,
            'ip'        => $request->ip(),
            'input'     => $request->except(['_token', 'password', 'fdoc']), // sensitive fields excluded
        ]);

        // ────────────────────────────────────────────────
        // Validation
        // ────────────────────────────────────────────────
        $validator = Validator::make($request->all(), [
            'id'     => 'required',
            'remark' => 'required|string|min:3|max:1500',
            'status' => 'nullable|in:0,1,2,3,4,5,6,7,8',
            'fdoc'   => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'dept'   => 'nullable|string|max:50',
            // You can add more rules if needed (invoice_number format etc.)
        ]);

        if ($validator->fails()) {
            Log::warning('BOOKING_FOLLOWUP_VALIDATION_FAILED', [
                'user_id'     => $userId,
                'booking_id'  => $request->input('id', 'missing'),
                'errors'      => $validator->errors()->toArray(),
                'input'       => $request->except(['_token', 'fdoc']),
            ]);

            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Please fill required fields correctly.');
        }

        try {
            $id = (int) $request->input('id');
            Log::info('BOOKING_FOLLOWUP_LOOKUP', ['booking_id' => $id, 'user_id' => $userId]);

            $booking = Booking::findOrFail($id);

            Log::debug('BOOKING_FOUND', [
                'id'            => $booking->id,
                'current_status' => $booking->status,
                'current_inv_no' => $booking->inv_no,
                'dealer_inv_no' => $booking->dealer_inv_no,
                'dealer_status' => $booking->dealer_status ?? 'null',
            ]);

            $newStatus = (int) $request->input('status', 0);

            // ────────────────────────────────────────────────
            // Invoice fields logic (only when trying to set status=2)
            // ────────────────────────────────────────────────
            if ($newStatus === 2) {
                Log::info('INVOICE_FIELDS_PROCESSING_START', ['booking_id' => $id]);

                $normalInvoiceNumber = trim($request->invoice_number ?? '');
                $normalInvoiceDate   = trim($request->invoice_date ?? '');
                $dealerInvoiceNumber = trim($request->dealer_invoice_number ?? '');
                $dealerInvoiceDate   = trim($request->dealer_invoice_date ?? '');

                $normalFilled = !empty($normalInvoiceNumber) && !empty($normalInvoiceDate);
                $dealerFilled = !empty($dealerInvoiceNumber) && !empty($dealerInvoiceDate);

                Log::debug('INVOICE_FIELDS_STATUS', [
                    'normal_filled'  => $normalFilled,
                    'dealer_filled'  => $dealerFilled,
                    'normal_number'  => $normalInvoiceNumber ?: null,
                    'normal_date'    => $normalInvoiceDate ?: null,
                    'dealer_number'  => $dealerInvoiceNumber ?: null,
                    'dealer_date'    => $dealerInvoiceDate ?: null,
                ]);

                // Auto-copy logic
                if ($normalFilled && empty($dealerInvoiceNumber) && empty($dealerInvoiceDate)) {
                    Log::notice('AUTO_COPY_INVOICE_TO_DEALER', [
                        'booking_id' => $id,
                        'from_number' => $normalInvoiceNumber,
                        'from_date'  => $normalInvoiceDate,
                    ]);

                    $request->merge([
                        'dealer_invoice_number' => $normalInvoiceNumber,
                        'dealer_invoice_date'   => $normalInvoiceDate,
                    ]);
                }

                // Final dealer_status decision
                $finalDealerStatus = 0;
                if ($normalFilled && $dealerFilled) {
                    $finalDealerStatus = 2;
                } elseif (!$normalFilled && $dealerFilled) {
                    $finalDealerStatus = 1;
                }

                // Apply changes
                $booking->inv_no          = $normalInvoiceNumber ?: null;
                $booking->inv_date        = $normalInvoiceDate ?: null;
                $booking->dealer_inv_no   = $request->dealer_invoice_number ?: null;
                $booking->dealer_inv_date = $request->dealer_invoice_date ?: null;
                $booking->dealer_status   = $finalDealerStatus;

                Log::info('INVOICE_FIELDS_UPDATED', [
                    'booking_id'     => $id,
                    'inv_no'         => $booking->inv_no,
                    'inv_date'       => $booking->inv_date,
                    'dealer_inv_no'  => $booking->dealer_inv_no,
                    'dealer_inv_date' => $booking->dealer_inv_date,
                    'dealer_status'  => $booking->dealer_status,
                ]);
            }

            // ────────────────────────────────────────────────
            // Status change
            // ────────────────────────────────────────────────
            $oldStatus = $booking->status;
            $oldStatusName = $this->getStatusName($oldStatus);

            if ($newStatus !== 0 && $newStatus !== $oldStatus) {
                Log::notice('STATUS_CHANGE_ATTEMPT', [
                    'booking_id'    => $id,
                    'from'          => $oldStatus,
                    'from_name'     => $oldStatusName,
                    'to'            => $newStatus,
                    'to_name'       => $this->getStatusName($newStatus),
                    'user_id'       => $userId,
                ]);

                $booking->status = $newStatus;

                if ($newStatus == 3) {
                    $booking->cancel_date = Carbon::now()->format('Y-m-d');
                    Log::info('CANCEL_DATE_SET', ['booking_id' => $id, 'date' => $booking->cancel_date]);
                }

                if ($newStatus == 7) {
                    $booking->refund_rejection_date = Carbon::now()->format('Y-m-d');
                    Log::info('REFUND_REJECTION_DATE_SET', ['booking_id' => $id, 'date' => $booking->refund_rejection_date]);
                }
            }

            // ────────────────────────────────────────────────
            // Save booking
            // ────────────────────────────────────────────────
            $booking->save();
            Log::info('BOOKING_SAVED_SUCCESSFULLY', [
                'booking_id'    => $id,
                'new_status'    => $booking->status,
                'dealer_status' => $booking->dealer_status ?? 'null',
                'changes'       => $booking->getChanges(),
            ]);

            // ────────────────────────────────────────────────
            // Follow-up / comment (uncomment when you reactivate this part)
            // ────────────────────────────────────────────────
            /*
        $newStatusName = ($newStatus !== 0) ? $this->getStatusName($newStatus) : $oldStatusName;

        $rem = ($newStatus !== 0 && $newStatus !== $oldStatus)
            ? "{$userName} ने status {$oldStatusName} → {$newStatusName} किया"
            : "{$userName} ने remark जोड़ा";

        $commid = ChatHelper::get_commid(3, $booking->id, "Booking Follow-up");

        $file = $request->hasFile('fdoc') ? $request->file('fdoc') : null;

        $followupAdded = ChatHelper::add_followup(
            $commid,
            $request->remark,
            $rem,
            $file,
            1,
            $request->dept ?? 'admin'
        );

        Log::info('FOLLOWUP_COMMENT_ADDED', [
            'commid'     => $commid,
            'remark'     => Str::limit($request->remark, 120),
            'file'       => $file ? $file->getClientOriginalName() : null,
            'success'    => $followupAdded,
        ]);
        */

            return redirect()->route('booking.index')
                ->with('success', 'Booking update aur followup successfully save ho gaya.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('BOOKING_NOT_FOUND', [
                'requested_id' => $request->input('id'),
                'user_id'      => $userId,
                'ip'           => $request->ip(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Booking record nahi mila.');
        } catch (\Exception $e) {
            Log::critical('BOOKING_FOLLOWUP_CRASH', [
                'booking_id'   => $request->input('id', 'N/A'),
                'user_id'      => $userId,
                'user_name'    => $userName,
                'message'      => $e->getMessage(),
                'file'         => $e->getFile(),
                'line'         => $e->getLine(),
                'trace'        => $e->getTraceAsString(),
                'input'        => $request->except(['_token', 'fdoc']),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Kuch technical issue aa gaya. Technical team ko inform kar diya gaya hai.');
        }
    }

    private function getStatusName($status)
    {
        return match ((int) $status) {
            1  => 'Active',
            2  => 'Invoiced',
            3  => 'Cancelled',
            4  => 'In Refund Queue',
            5  => 'Closed',
            6  => 'On-Hold',
            7  => 'Refund Rejected',
            8  => 'Active (Pending)',
            0  => 'No Change',
            default => 'Unknown (' . $status . ')'
        };
    }



    public function show($id)
    {
        $this->crud->hasAccessOrFail('show');

        // Yeh line important hai – Backpack ko entry set karne deta hai
        // taaki $this->crud->getEntry($id) sahi se kaam kare
        $this->crud->getEntry($id);

        // Ab humara centralized data loader call karte hain
        return $this->getFullBookingData($id, 'show');
    }
    // public function show($id)
    // {
    //     $this->crud->hasAccessOrFail('show');
    //     $this->crud->getEntry($id);

    //     $viewData = $this->getFullBookingData($id, 'show');

    //     return response()->json([
    //         'success' => true,
    //         'booking_id' => $id,
    //         'status' => $viewData->getData()['booking']->status ?? 'missing',
    //         'refund_found' => isset($viewData->getData()['refund']) ? 'YES' : 'NO',
    //         'deduction' => $viewData->getData()['deduction'] ?? 'not set',
    //         'remaining' => $viewData->getData()['refund']['remaining_amount'] ?? 'not set',
    //         'acc_proof' => $viewData->getData()['acc_proof'] ?? 'EMPTY',
    //         'aadhar'    => $viewData->getData()['aadhar'] ?? 'EMPTY',
    //         'pan'       => $viewData->getData()['pan'] ?? 'EMPTY',
    //         'full_data_keys' => array_keys($viewData->getData()),
    //     ]);
    // }

    public function getModels($segment_id)
    {
        $models = XpricingHelper::getModelsX($segment_id);
        return response()->json($models);
    }

    public function CheckReceipt($rn)
    {
        $count = XpricingHelper::checkReceiptX($rn);
        return response()->json((int)$count > 0 ? 1 : 0);
    }

    public function getVariants($model)
    {
        $variants = XpricingHelper::getVehiclesX($model);
        return response()->json($variants);
    }

    public function getColors($variant)
    {
        $colors = XpricingHelper::getColorX($variant);
        return response()->json($colors);
    }

    public function getChassisNumbers($modelCode)
    {
        $chassisNumbers = Stock::where('model_code', $modelCode)->select('chasis_no', 'id')->get()->toArray();
        //print_r($chassisNumbers);
        return response()->json($chassisNumbers);
    }

    public function getBranchLocation($bids)
    {
        $data = CommonHelper::getLocations($bids);
        //print_r($data);
        return $data;
    }

    public function getAccessories($segment, $model, $variant)
    {
        $accessories = XpricingHelper::getAccessories($segment, $model, $variant);
        return response()->json($accessories);
    }

    public function getLocations($state_id)
    {

        $locations = XCommonHelper::getLocationsByState($state_id);


        return response()->json($locations);
    }

    public function getLocationsByPincode($pincode)
    {
        $locations = PinCodes::where('pincode', $pincode)->get(['id', 'name']);

        if ($locations->isNotEmpty()) {
            return response()->json($locations);
        } else {
            return response()->json([]);
        }
    }

    public function getStateByLocation($location_code)
    {
        $location = PinCodes::where('id', $location_code)->first(['id', 'parent', 'level']);

        if (!$location) {
            return response()->json(null);
        }

        // Keep finding the parent until we reach the STATE level
        while ($location && $location->level !== 'STATE') {
            $location = PinCodes::where('id', $location->parent)->first(['id', 'parent', 'level']);
        }

        return response()->json([
            'state_id' => $location ? $location->id : null
        ]);
    }

    public function orderVerification(Request $request)
    {
        $this->crud->hasAccessOrFail('list');

        $this->data['crud'] = $this->crud;
        $this->data['title'] = 'Order Verification';

        $query = $this->getBaseQuery();

        $status_filter = $request->input('status_filter', '1');

        if ($status_filter === 'all') {
            $query->whereIn('bookings.order', [0, 1, 2]);
        } else {
            $query->where('bookings.order', $status_filter);
        }

        $query->orderBy('bookings.id', 'DESC');

        $paginatedBookings = $query->paginate(50);

        $lookups = $this->getCommonLookups();
        extract($lookups);

        $user = backpack_user();
        $allowedUsers = [5, 23, 123];

        $gridData = $paginatedBookings->map(function ($t, $index) use (
            $paginatedBookings,
            $segments,
            $user,
            $allowedUsers
        ) {
            $row = $this->mapBookingForGrid($t);

            $row->serial_no = ($paginatedBookings->currentPage() - 1) * $paginatedBookings->perPage() + $index + 1;

            if (in_array($user->id, $allowedUsers)) {
                $action = '<div style="display:flex;gap:8px;justify-content:center;">';
                if ($t->order == 1) {
                    $action .= '<a href="' . route('booking.orderupdate', ['id' => $t->id, 'status' => 2]) . '"
                            class="btn btn-success btn-sm">Accept</a>';
                    $action .= '<a href="' . route('booking.orderupdate', ['id' => $t->id, 'status' => 0]) . '"
                            class="btn btn-danger btn-sm">Reject</a>';
                } elseif ($t->order == 2) {
                    $action .= '<a href="' . route('booking.orderupdate', ['id' => $t->id, 'status' => 0]) . '"
                            class="btn btn-danger btn-sm">Reject</a>';
                } elseif ($t->order == 0) {
                    $action .= '<a href="' . route('booking.orderupdate', ['id' => $t->id, 'status' => 2]) . '"
                            class="btn btn-success btn-sm">Accept</a>';
                }
                $action .= '</div>';
                $row->action = $action;
            } else {
                $row->action = '<div class="text-center text-muted">---</div>';
            }

            return $row;
        })->values();

        $columns = $this->getAgGridColumns();

        $hasAction = collect($columns)->contains('field', 'action');
        if (!$hasAction) {
            $columns[] = [
                'field'         => 'action',
                'headerName'    => 'Action',
                'width'         => 180,
                'pinned'        => 'right',
                'sortable'      => false,
                'filter'        => false,
                'cellRenderer'  => 'htmlRenderer',
                'cellClass'     => 'text-center p-0',
                'autoHeight'    => true,
            ];
        }

        $gridConfig = [
            'columns' => $columns,
            'data'    => $gridData,
        ];

        $this->data['gridConfig'] = $gridConfig;

        return view('booking.order-verification', $this->data);
    }

    private function getCommonLookups()
    {
        return [
            'segments' => XpricingHelper::getSegments(),
            'saleConsultants' => collect(XpricingHelper::selectfsc()),
            'financiers' => XlFinancier::select('id', 'name')->get()->keyBy('id')->toArray(),
        ];
    }
    public function orderUpdate(Request $request, $id, $status)
    {
        // Sirf specific users hi access kar sakte hain
        if (! in_array(backpack_user()->id, [5, 23, 123])) {
            abort(403, 'Unauthorized');
        }

        // Sirf 0 aur 1 allowed (hold aur release)
        $allowedStatuses = [0, 1];
        if (! in_array($status, $allowedStatuses)) {
            return redirect()->back()->with('error', 'Invalid status value. Only hold (1) or release (0) allowed.');
        }

        // Booking find karo
        $booking = Booking::findOrFail($id);

        // Remark message status ke hisaab se
        $remark = '';
        if ($status == 1) {
            $remark = "Booking put on hold by verifier";
        } elseif ($status == 0) {
            $remark = "Hold released, booking activated";
        }

        // Chat/followup add karo (optional, lekin useful hai audit ke liye)
        try {
            $commid = ChatHelper::get_commid(3, $booking->id, "Booking Created");
            ChatHelper::add_followup(
                $commid,
                "Booking Status Changed",
                $remark,
                null,
                1
            );
        } catch (\Exception $e) {
            \Log::error('ChatHelper followup failed in orderUpdate', [
                'booking_id' => $id,
                'status'     => $status,
                'error'      => $e->getMessage()
            ]);
        }

        // Booking update
        $booking->order = (int) $status;
        $booking->saveQuietly();  // agar timestamps update nahi karna chahte

        // User-friendly message
        $messages = [
            0 => 'Hold released successfully. Booking is now active.',
            1 => 'Booking successfully put on hold.',
        ];

        $message = $messages[$status] ?? 'Booking status updated successfully';

        return redirect()->back()->with('success', $message);
    }

    public function pendingorder(Request $request)
    {
        $this->crud->hasAccessOrFail('list');

        $this->data['crud'] = $this->crud;
        $this->data['title'] = 'Ordered Verification';


        $query = $this->getBaseQuery()
            ->withoutGlobalScopes()
            ->withoutGlobalScope(SoftDeletingScope::class);

        // मुख्य filters
        $query->whereIn('bookings.segment_id', [753, 21589])
            ->where(function ($q) {
                $q->whereNull('bookings.order')           // order IS NULL
                    ->orWhereIn('bookings.order', [0, 1]);  // order = 0 या 1
            });


        $query->orderBy('bookings.id', 'DESC');

        $paginatedBookings = $query->paginate(50);
        // dd([
        //     'total_records' => $paginatedBookings->total(),
        //     'items_on_this_page' => $paginatedBookings->count(),
        //     'first_booking_id' => $paginatedBookings->first()?->id ?? 'No records',
        //     'first_segment_id' => $paginatedBookings->first()?->segment_id ?? null,
        //     'first_order_value' => $paginatedBookings->first()?->order ?? null,
        //     'first_status_value' => $paginatedBookings->first()?->status ?? null,  // ← ये देखना जरूरी
        // ]);
        $lookups = $this->getCommonLookups();
        extract($lookups);

        $user = backpack_user();
        $allowedUsers = [5, 23, 123];

        $gridData = $paginatedBookings->map(function ($t, $index) use (
            $paginatedBookings,
            $user,
            $allowedUsers
        ) {

            $row = $this->mapBookingForGrid($t);
            if (empty((array)$row)) {
                \Log::info("mapBookingForGrid returned empty for booking ID: " . $t->id);
            }
            \Log::debug("Mapped row for ID {$t->id}", (array)$row);

            $row->serial_no = ($paginatedBookings->currentPage() - 1) * $paginatedBookings->perPage() + $index + 1;

            if (in_array($user->id, $allowedUsers)) {
                $row->action = '<div class="table-actions text-center">
                    <a class="btn btn-sm btn-primary py-1 px-2" href="' . route('dms-edit', $t->id) . '?from=pending" title="Edit DMS / SO">
                        Process
                    </a>
                </div>';
            } else {
                $row->action = '<div class="table-actions text-center text-muted">---</div>';
            }

            return $row;
        })->values();

        $columns = $this->getAgGridColumns();

        $hasAction = collect($columns)->contains('field', 'action');
        if (!$hasAction) {
            $columns[] = [
                'field'         => 'action',
                'headerName'    => 'Action',
                'width'         => 120,
                'pinned'        => 'right',
                'sortable'      => false,
                'filter'        => false,
                'cellRenderer'  => 'htmlRenderer',
                'cellClass'     => 'text-center p-0',
                'autoHeight'    => true,
            ];
        }

        $gridConfig = [
            'columns' => $columns,
            'data'    => $gridData,
        ];

        $this->data['gridConfig'] = $gridConfig;

        return view('booking.pending-order', $this->data);
    }



    public function dmsedit($id, Request $request)
    {
        $booking = Booking::findOrFail($id);

        $branchName    = X_Branch::find($booking->branch_code)->name ?? 'N/A';
        $locationName  = !empty($booking->location_code) && $booking->location_code > 0
            ? (X_Location::find($booking->location_code)->name ?? 'N/A')
            : ($booking->location_other ?? 'N/A');
        $collectorName = $booking->col_by
            ? (User::find($booking->col_by)->name ?? 'N/A')
            : 'N/A';
        $fromPending = $request->query('from') === 'pending';   // ya $request->boolean('from_pending')

        $isBevOrPersonal = in_array($booking->segment_id ?? 0, [753, 21589]);
        $data = [
            'branch'         => $branchName,
            'location'       => $locationName,
            'collector_name' => $collectorName,
            'accessories'    => $booking->accessories ?? 'N/A',
            'total_amount'   => $booking->total_amount ?? 0,
            'is_bev_or_personal' => in_array($booking->segment_id, [753, 21589]),
            'from_pending'       => $fromPending,
            'so_required'        => $fromPending && $isBevOrPersonal,
        ];


        return view('booking.dms-edit', compact('booking', 'data'));
    }

    public function dmsupdate(Request $request, $id)
    {
        Log::info('Starting dmsupdate', [
            'booking_id'    => $id,
            'user_id'       => backpack_auth()->id(),
            'pending_flag'  => $request->has('pending_flag'),
            'ip'            => $request->ip(),
        ]);

        $booking = Booking::findOrFail($id);

        // Uppercase important fields (backend safety + consistency)
        $request->merge([
            'dms_no'  => strtoupper(trim($request->input('dms_no', ''))),
            'dms_otf' => strtoupper(trim($request->input('dms_otf', ''))),
            'dms_so'  => strtoupper(trim($request->input('dms_so', ''))),
        ]);

        // Validation Rules (same strict format as frontend)
        $rules = [
            'dms_no'          => ['required', 'regex:/^B-\d{8}$/'],
            'dms_otf'         => ['required', 'regex:/^OTF\d{2}[A-Z]\d{6}$/'],
            'otf_date'        => ['required', 'date_format:d-m-Y'],          // ← changed to numeric
            'hidden_otf_date' => ['required', 'date:Y-m-d'],                 // stricter
        ];

        if ($booking->order == 2) {
            $rules['dms_so'] = ['required', 'regex:/^\d{10}$/'];
        }

        $messages = [
            'dms_no.required'    => 'DMSBooking Number is required.',
            'dms_no.regex'       => 'Please enter a valid DMSBooking number (e.g., B-12345678).',
            'dms_otf.required'   => 'DMS OTF Number is required.',
            'dms_otf.regex'      => 'Please enter a valid OTF number (e.g., OTF00A123456).',
            'dms_so.required'    => 'DMS SO Number is required.',
            'dms_so.regex'       => 'Please enter a valid SO number (exactly 10 digits).',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            Log::warning('Validation failed in dmsupdate', [
                'booking_id' => $id,
                'errors'     => $validator->errors()->toArray(),
            ]);

            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Prepare changes for remarks
        $remarks = [];

        if ($booking->dms_no !== $request->dms_no) {
            $remarks[] = "DMS Booking No updated to {$request->dms_no}";
        }
        if ($booking->dms_otf !== $request->dms_otf) {
            $remarks[] = "DMS OTF updated to {$request->dms_otf}";
        }
        if ($booking->otf_date !== $request->hidden_otf_date) {
            $remarks[] = "DMS OTF Date updated to {$request->hidden_otf_date}";
        }
        if ($booking->order == 2 && $booking->dms_so !== $request->dms_so) {
            $remarks[] = "DMS SO Number updated to {$request->dms_so}";
        }

        // Update using mass assignment (safer & cleaner)
        $updateData = [
            'dms_no'   => $request->dms_no,
            'dms_otf'  => $request->dms_otf,
            'otf_date' => $request->hidden_otf_date,   // already in Y-m-d
        ];

        if ($booking->order == 2) {
            $updateData['dms_so'] = $request->dms_so;
        }

        $booking->update($updateData);

        // ── Pending remark logic ────────────────────────────────────────────────
        $existingPending = $booking->pending_remark
            ? explode(' , ', trim($booking->pending_remark))
            : [];

        $dmsPendingItems = [
            'DMS Booking no needs to be updated',
            'DMS OTF needs to be updated',
            'DMS OTF Date needs to be updated',
            'DMS SO number needs to be updated',
        ];

        // Keep only non-DMS pending items
        $remainingPending = array_diff($existingPending, $dmsPendingItems);

        // Check if any DMS field is still empty (unlikely after validation, but safety)
        $newPending = [];
        if (empty($booking->dms_no))   $newPending[] = 'DMS Booking no needs to be updated';
        if (empty($booking->dms_otf))  $newPending[] = 'DMS OTF needs to be updated';
        if (empty($booking->otf_date)) $newPending[] = 'DMS OTF Date needs to be updated';
        if ($booking->order == 2 && empty($booking->dms_so)) {
            $newPending[] = 'DMS SO number needs to be updated';
        }

        $finalPending = array_merge($remainingPending, $newPending);
        $finalPending = array_unique(array_filter($finalPending));

        $booking->pending_remark = $finalPending ? implode(' , ', $finalPending) : null;
        $booking->pending = count($finalPending);

        // ── Status & Order updates ──────────────────────────────────────────────
        if ($booking->pending === 0) {
            $booking->status = 1;
            Log::info('Booking status set to 1 (no pending fields left)', ['booking_id' => $id]);
        }

        $isPersonalOrBev = in_array($booking->segment_id ?? 0, [753, 21589]);
        $booking->order = 2;
        if ($isPersonalOrBev) {
            if (empty(trim($request->input('dms_so', '')))) {
                $booking->order = 3;
                Log::info('Order set to 3 - Personal/BEV from pending DMS, SO missing in this submit', [
                    'booking_id' => $id,
                    'segment_id' => $booking->segment_id ?? 'N/A'
                ]);
            } else {
                Log::info('Order remains 2 - Personal/BEV but SO provided in this submit', ['booking_id' => $id]);
            }
        }

        if ($booking->pending === 0) {
            $booking->status = 1;
            Log::info('Booking status set to 1 (no pending fields left)', ['booking_id' => $id]);
        }

        $booking->saveQuietly();

        // ── Log remarks to chat/followup if changes were made ───────────────────
        if (!empty($remarks)) {
            $rem_string = implode(', ', $remarks);
            Log::info('DMS changes recorded', ['booking_id' => $id, 'remarks' => $rem_string]);

            try {
                $commid = ChatHelper::get_commid(3, $booking->id, "Booking Created");
                ChatHelper::add_followup(
                    $commid,
                    $rem_string,
                    "Pending DMS Data Updated",
                    null,
                    1
                );
            } catch (\Exception $e) {
                Log::error('ChatHelper followup failed', [
                    'booking_id' => $id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        // ── Redirect based on final order ───────────────────────────────────────
        $message = 'DMS details updated successfully!';
        $fromPending = $request->input('from') === 'pending';

        $message = 'DMS details updated successfully!';

        if ($fromPending) {
            return redirect()->route('booking.pending-order')
                ->with('success', $message);
        }

        return redirect()->route('booking.pending-dms')  // ya 'booking.pending-dms'
            ->with('success', $message);
    }


    // ======= PENDING KYC FUNCTION ======

    public function pendingKyc(Request $request)
    {
        $this->crud->hasAccessOrFail('list');

        $this->data['crud'] = $this->crud;
        $this->data['title'] = 'Pending KYC';

        $query = $this->getBaseQuery();

        $query->whereIn('bookings.status', [1, 8]);

        $query->where(function ($q) {
            $q->whereNull('bookings.pan_no')
                ->orWhere('bookings.pan_no', '')
                ->orWhereNull('bookings.adhar_no')
                ->orWhere('bookings.adhar_no', '');
        });

        $query->orderBy('bookings.id', 'desc');

        $paginatedBookings = $query->paginate(50);

        $gridData = $paginatedBookings->map(function ($t, $index) use ($paginatedBookings) {
            $row = $this->mapBookingForGrid($t);

            $row->serial_no = ($paginatedBookings->currentPage() - 1) * $paginatedBookings->perPage() + $index + 1;

            $row->action = '<a href="' . route('booking.kyc.edit', $t->id) . '"
                        class="btn btn-sm btn-primary py-1 px-2" title="Complete KYC">
                            Process
                       </a>';

            return $row;
        })->values();

        $columns = $this->getAgGridColumns();

        $hasAction = collect($columns)->contains('field', 'action');
        if (!$hasAction) {
            $columns[] = [
                'field'         => 'action',
                'headerName'    => 'Action',
                'autoWidth'     => true,
                'pinned'        => 'right',
                'sortable'      => false,
                'filter'        => false,
                'cellRenderer'  => 'htmlRenderer',
                'cellClass'     => 'text-center p-0',
                'autoHeight'    => true,
            ];
        }

        $gridConfig = [
            'columns' => $columns,
            'data'    => $gridData,
        ];

        $this->data['gridConfig'] = $gridConfig;

        if ($gridData->isEmpty()) {
            session()->flash('info', 'No pending KYC bookings found.');
        }

        return view('booking.pending-kyc', $this->data);
    }

    // Show the KYC edit form
    // KYC Edit Page
    public function kycEdit($id)
    {
        $this->crud->hasAccessOrFail('update');

        $booking = Booking::findOrFail($id);

        // Extra data अगर blade में इस्तेमाल हो रही है
        $data = [
            'branches'       => X_Branch::pluck('name', 'id')->toArray(),
            'locations'      => X_Location::pluck('name', 'id')->toArray(),
            'segments'       => XpricingHelper::getSegments() ?? [],
            'saleconsultants' => collect(XpricingHelper::selectfsc()),
        ];

        return view('booking.kyc-edit', compact('booking', 'data'));
    }




    // KYC Update (Form Submit)
    public function kycUpdate(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);

        $validated = $request->validate([
            'pan_no'   => [
                'required',
                'string',
                'size:10',
                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/',
            ],
            'adhar_no' => [
                'required',
                'string',
                'regex:/^[2-9]{1}[0-9]{3}[ -]?[0-9]{4}[ -]?[0-9]{4}$/',
            ],
            'gst_no'   => [
                'nullable',
                'string',
                'size:15',
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/',
            ],
        ]);

        // GST logic
        $gstValue = $request->has('gst_not_required') && $request->gst_not_required
            ? '0'
            : ($validated['gst_no'] ?? $booking->gstn ?? '0');

        $booking->update([
            'pan_no'   => strtoupper($validated['pan_no']),
            'adhar_no' => preg_replace('/[ -]/', '', $validated['adhar_no']),
            'gstn'     => $gstValue,
            // अगर KYC status track करते हो तो यहाँ अपडेट कर सकते हो
            // 'kyc_status' => 'completed',
        ]);

        // Success मैसेज के साथ Bookings index पर redirect
        return redirect()
            ->route('booking.pending-kyc')  // अगर आपका route name अलग है तो बदल लें
            ->with('success', "Booking #{$booking->id} की KYC successfully complete हो गई है!");
    }

    // public function pendingDms(Request $request)
    // {
    //     $this->crud->hasAccessOrFail('list');
    //     $this->data['crud'] = $this->crud;
    //     // $this->data['title'] = 'Pending DMS';

    //     $query = $this->getBaseQuery();

    //     // Common conditions
    //     $query->whereIn('bookings.status', [1, 8]);
    //     $query->where('bookings.b_type', 'Active');

    //     // DMS incomplete hone chahiye
    //     $query->where(function ($q) {
    //         $q->whereNull('bookings.dms_no')->orWhere('bookings.dms_no', '')
    //             ->orWhereNull('bookings.dms_otf')->orWhere('bookings.dms_otf', '')
    //             ->orWhereNull('bookings.otf_date')->orWhere('bookings.otf_date', '');
    //     });

    //     // Filter logic
    //     $status_filter = $request->input('status_filter', 'active');  // default Active

    //     if ($status_filter === 'active') {
    //         // Active → order blank ya 0
    //         $query->where(function ($q) {
    //             $q->whereNull('bookings.order')
    //                 ->orWhere('bookings.order', 0);
    //         });
    //     } elseif ($status_filter === 'hold') {
    //         // Hold → sirf order = 1
    //         $query->where('bookings.order', 1);
    //     } else {
    //         // kuch galat aaye to default active dikha do
    //         $query->where(function ($q) {
    //             $q->whereNull('bookings.order')
    //                 ->orWhere('bookings.order', 0);
    //         });
    //     }
    //     $query->orderBy('bookings.id', 'desc');

    //     $paginatedBookings = $query->paginate(50);

    //     // Lookups, user, allowedUsers ... (same as before)
    //     $lookups = $this->getCommonLookups();
    //     extract($lookups);
    //     $user = backpack_user();
    //     $allowedUsers = [5, 23, 123];

    //     $gridData = $paginatedBookings->map(function ($t, $index) use (
    //         $paginatedBookings,
    //         $user,
    //         $allowedUsers
    //     ) {
    //         $row = $this->mapBookingForGrid($t);
    //         $row->serial_no = ($paginatedBookings->currentPage() - 1) * $paginatedBookings->perPage() + $index + 1;

    //         $actionHtml = '<div class="d-flex gap-2 justify-content-center flex-wrap align-items-center">';

    //         if ($t->order == 1) {
    //             // Hold में है → सिर्फ Play button (hold release)
    //             $actionHtml .= '
    //     <a href="' . route('booking.orderupdate', ['id' => $t->id, 'status' => 0]) . '"
    //        class="btn btn-sm btn-success">
    //         Resume
    //     </a>';
    //             // ← यहाँ edit button नहीं डाला गया
    //         } else {
    //             // Normal (order 0 या null) → Hold + Edit दोनों buttons
    //             $actionHtml .= '
    //     <a href="' . route('booking.orderupdate', ['id' => $t->id, 'status' => 1]) . '"
    //        class="btn btn-sm btn-danger">
    //         Hold
    //     </a>';

    //             $actionHtml .= '
    //     <a href="' . route('dms-edit', $t->id) . '"
    //        class="btn btn-sm btn-primary py-1 px-2">
    //         Process
    //     </a>';
    //         }

    //         $actionHtml .= '</div>';

    //         $row->action = $actionHtml;
    //         return $row;
    //     })->values();

    //     $columns = $this->getAgGridColumns();
    //     $hasAction = collect($columns)->contains('field', 'action');
    //     if (!$hasAction) {
    //         $columns[] = [
    //             'field'         => 'action',
    //             'headerName'    => 'Action',
    //             'width'         => 140,
    //             'pinned'        => 'right',
    //             'sortable'      => false,
    //             'filter'        => false,
    //             'cellRenderer'  => 'htmlRenderer',
    //             'cellClass'     => 'text-center p-0',
    //             'autoHeight'    => true,
    //         ];
    //     }

    //     $gridConfig = [
    //         'columns' => $columns,
    //         'data'    => $gridData,
    //     ];

    //     $this->data['gridConfig'] = $gridConfig;

    //     // if ($gridData->isEmpty()) {
    //     //     session()->flash('info', 'No bookings found for selected status.');
    //     // }

    //     return view('booking.pending-dms', $this->data);
    // }
    public function pendingDms(Request $request)
    {
        $this->crud->hasAccessOrFail('list');
        $this->data['crud'] = $this->crud;
        $this->data['title'] = 'Pending DMS';

        $query = $this->getBaseQuery();

        // Common conditions
        $query->whereIn('bookings.status', [1, 8]);
        $query->where('bookings.b_type', 'Active');

        // DMS incomplete hone chahiye - CORRECTED
        $query->where(function ($q) {
            $q->whereNull('bookings.dms_no')
                ->orWhere('bookings.dms_no', '')
                ->orWhereNull('bookings.dms_otf')
                ->orWhere('bookings.dms_otf', '')
                ->orWhereNull('bookings.otf_date')                    // NULL allowed
                ->orWhere('bookings.otf_date', '0000-00-00');         // Default invalid date
            // → '' (empty string) ko hata diya hai
        });

        // Filter logic (Active / Hold)
        $status_filter = $request->input('status_filter', 'active');

        if ($status_filter === 'active') {
            $query->where(function ($q) {
                $q->whereNull('bookings.order')
                    ->orWhere('bookings.order', 0);
            });
        } elseif ($status_filter === 'hold') {
            $query->where('bookings.order', 1);
        } else {
            // default active
            $query->where(function ($q) {
                $q->whereNull('bookings.order')
                    ->orWhere('bookings.order', 0);
            });
        }

        $query->orderBy('bookings.id', 'desc');

        $paginatedBookings = $query->paginate(50);

        // Rest of your code (lookups, grid mapping, etc.)
        $lookups = $this->getCommonLookups();
        extract($lookups);

        $user = backpack_user();
        $allowedUsers = [5, 23, 123];

        $gridData = $paginatedBookings->map(function ($t, $index) use (
            $paginatedBookings,
            $user,
            $allowedUsers
        ) {
            $row = $this->mapBookingForGrid($t);
            $row->serial_no = ($paginatedBookings->currentPage() - 1) * $paginatedBookings->perPage() + $index + 1;

            $actionHtml = '<div class="d-flex gap-2 justify-content-center flex-wrap align-items-center">';

            if ($t->order == 1) {
                $actionHtml .= '
                <a href="' . route('booking.orderupdate', ['id' => $t->id, 'status' => 0]) . '"
                   class="btn btn-sm btn-success">Resume</a>';
            } else {
                $actionHtml .= '
                <a href="' . route('booking.orderupdate', ['id' => $t->id, 'status' => 1]) . '"
                   class="btn btn-sm btn-danger">Hold</a>';

                $actionHtml .= '
                <a href="' . route('dms-edit', $t->id) . '"
                   class="btn btn-sm btn-primary py-1 px-2">Process</a>';
            }

            $actionHtml .= '</div>';
            $row->action = $actionHtml;

            return $row;
        })->values();

        $columns = $this->getAgGridColumns();
        $hasAction = collect($columns)->contains('field', 'action');

        if (!$hasAction) {
            $columns[] = [
                'field'         => 'action',
                'headerName'    => 'Action',
                'width'         => 140,
                'pinned'        => 'right',
                'sortable'      => false,
                'filter'        => false,
                'cellRenderer'  => 'htmlRenderer',
                'cellClass'     => 'text-center p-0',
                'autoHeight'    => true,
            ];
        }

        $gridConfig = [
            'columns' => $columns,
            'data'    => $gridData,
        ];

        $this->data['gridConfig'] = $gridConfig;

        return view('booking.pending-dms', $this->data);
    }



    public function Exchange(Request $request)
    {
        $this->crud->hasAccessOrFail('list');

        $this->data['crud'] = $this->crud;
        $this->data['title'] = 'Int in Exchange';

        // ────────────────────────────────────────────────
        // Query – getBaseQuery() se start karo
        // ────────────────────────────────────────────────
        $query = $this->getBaseQuery();

        // Exchange-specific filter
        $query->where('bookings.buyer_type', 'Exchange Buy');

        $query->orderBy('bookings.id', 'DESC');

        $paginatedBookings = $query->paginate(50);

        // ────────────────────────────────────────────────
        // Preloads
        // ────────────────────────────────────────────────
        $lookups = $this->getCommonLookups();
        extract($lookups);

        $saleConsultants = $lookups['saleConsultants'] ?? [];

        // ────────────────────────────────────────────────
        // Mapping
        // ────────────────────────────────────────────────
        $gridData = $paginatedBookings->map(function ($t, $index) use (
            $paginatedBookings,
            $segments,
            $saleConsultants
        ) {
            $row = $this->mapBookingForGrid($t);

            $row->serial_no = ($paginatedBookings->currentPage() - 1) * $paginatedBookings->perPage() + $index + 1;

            // Exchange-specific calculated fields
            $price_gap = ($t->expected_price ?? 0) - ($t->offered_price ?? 0);
            $row->price_gap = number_format($price_gap);

            $row->exist_oem1 = CommonHelper::enumValueById($t->brand_make_1 ?? null);

            // Location logic
            $location = $t->location_code && $t->location_code > 0
                ? (X_Location::find($t->location_code)->name ?? 'N/A')
                : ($t->location_other ?? 'N/A');

            $row->location = $location;

            // Action button
            $row->action = '<div class="text-center">
            <a href="' . route('exchange-edit', $t->id) . '#exch"
               class="btn btn-primary btn-sm py-1 px-2">
                Process
            </a>
        </div>';

            return $row;
        })->values();

        // ────────────────────────────────────────────────
        // Columns – sirf reusable wala call
        // ────────────────────────────────────────────────
        $columns = $this->getAgGridColumns();

        // Action column add agar missing hai (duplicate avoid)
        $hasAction = collect($columns)->contains('field', 'action');
        if (!$hasAction) {
            $columns[] = [
                'field'         => 'action',
                'headerName'    => 'Actions',
                'width'         => 150,
                'pinned'        => 'right',
                'sortable'      => false,
                'filter'        => false,
                'cellRenderer'  => 'htmlRenderer',
                'cellClass'     => 'text-center p-0',
                'autoHeight'    => true,
            ];
        }

        // Optional: Exchange-specific formatting (prices right-align)
        foreach ($columns as &$col) {
            if (in_array($col['field'], ['expected_price', 'offered_price', 'exchange_bonus', 'price_gap'])) {
                $col['type'] = 'rightAligned';
                $col['cellClass'] = 'text-right';
            }
        }
        unset($col);

        $gridConfig = [
            'columns' => $columns,
            'data'    => $gridData,
        ];

        $this->data['gridConfig'] = $gridConfig;

        if ($gridData->isEmpty()) {
            session()->flash('info', 'No exchange interested bookings found.');
        }

        return view('booking.exchange', $this->data);
    }


    public function Scrappage(Request $request)
    {
        $this->crud->hasAccessOrFail('list');

        $this->data['crud'] = $this->crud;
        $this->data['title'] = 'Int in Scrappage';

        // ────────────────────────────────────────────────
        // Query – getBaseQuery() se start karo
        // ────────────────────────────────────────────────
        $query = $this->getBaseQuery();

        // Scrappage-specific filter
        $query->where('bookings.buyer_type', 'Scrappage');

        $query->orderBy('bookings.id', 'DESC');

        $paginatedBookings = $query->paginate(50);

        // ────────────────────────────────────────────────
        // Preloads
        // ────────────────────────────────────────────────
        $lookups = $this->getCommonLookups();
        extract($lookups);

        $saleConsultants = $lookups['saleConsultants'] ?? [];

        // ────────────────────────────────────────────────
        // Mapping – action button tumhara original
        // ────────────────────────────────────────────────
        $gridData = $paginatedBookings->map(function ($t, $index) use (
            $paginatedBookings,
            $segments,
            $saleConsultants
        ) {
            $row = $this->mapBookingForGrid($t);

            $row->serial_no = ($paginatedBookings->currentPage() - 1) * $paginatedBookings->perPage() + $index + 1;

            // Scrappage-specific calculated fields
            $price_gap = ($t->expected_price ?? 0) - (($t->offered_price ?? 0) + ($t->exchange_bonus ?? 0));
            $row->price_gap = number_format($price_gap);

            $row->expected_price = '₹ ' . number_format($t->expected_price ?? 0);
            $row->offered_price   = '₹ ' . number_format($t->offered_price ?? 0);
            $row->exchange_bonus  = '₹ ' . number_format($t->exchange_bonus ?? 0);

            // Location logic (same as Exchange)
            $location = $t->location_code && $t->location_code > 0
                ? (X_Location::find($t->location_code)->name ?? 'N/A')
                : ($t->location_other ?? 'N/A');

            $row->location = $location;

            // Action button (tumhara original)
            $row->action = '
            <div class="text-center">
                <a href="' . route('exchange-edit', $t->id) . '#exch"
                   class="btn btn-primary btn-sm py-1 px-2"
                   >
                    Process
                </a>
            </div>';

            return $row;
        })->values();

        // ────────────────────────────────────────────────
        // Columns – sirf reusable wala call
        // ────────────────────────────────────────────────
        $columns = $this->getAgGridColumns();

        // Action column add agar missing hai (duplicate avoid)
        $hasAction = collect($columns)->contains('field', 'action');
        if (!$hasAction) {
            $columns[] = [
                'field'         => 'action',
                'headerName'    => 'Actions',
                'width'         => 150,
                'pinned'        => 'right',
                'sortable'      => false,
                'filter'        => false,
                'cellRenderer'  => 'htmlRenderer',
                'cellClass'     => 'text-center p-0',
                'autoHeight'    => true,
            ];
        }

        // Optional: price columns ko right-align karna (Scrappage specific)
        foreach ($columns as &$col) {
            if (in_array($col['field'], ['expected_price', 'offered_price', 'exchange_bonus', 'price_gap'])) {
                $col['type'] = 'rightAligned';
                $col['cellClass'] = 'text-right';
            }
        }
        unset($col);

        $gridConfig = [
            'columns' => $columns,
            'data'    => $gridData,
        ];

        $this->data['gridConfig'] = $gridConfig;

        if ($gridData->isEmpty()) {
            session()->flash('info', 'No scrappage interested bookings found.');
        }

        return view('booking.scrappage', $this->data);
    }


    public function exchnotInterested(Request $request)
    {
        $this->crud->hasAccessOrFail('list');

        $this->data['crud'] = $this->crud;
        $this->data['title'] = 'Not Interested';

        // ────────────────────────────────────────────────
        // Query – getBaseQuery() se start karo
        // ────────────────────────────────────────────────
        $query = $this->getBaseQuery();

        // Not Interested filter (tumhara original logic)
        $query->whereIn('bookings.buyer_type', ['First time Buyer', 'Additional Buy']);

        $query->orderBy('bookings.id', 'DESC');

        $paginatedBookings = $query->paginate(50);

        // ────────────────────────────────────────────────
        // Preloads
        // ────────────────────────────────────────────────
        $lookups = $this->getCommonLookups();
        extract($lookups);

        $saleConsultants = $lookups['saleConsultants'] ?? [];

        // ────────────────────────────────────────────────
        // Mapping – action button tumhara original
        // ────────────────────────────────────────────────
        $gridData = $paginatedBookings->map(function ($t, $index) use (
            $paginatedBookings,
            $segments,
            $saleConsultants
        ) {
            $row = $this->mapBookingForGrid($t);

            $row->serial_no = ($paginatedBookings->currentPage() - 1) * $paginatedBookings->perPage() + $index + 1;

            // Not Interested specific calculated fields
            $price_gap = ($t->used_vehicle_exp_price ?? 0) - ($t->used_vehicle_off_price ?? 0);
            $row->price_gap = number_format($price_gap);

            $row->used_vehicle_exp_price = '₹ ' . number_format($t->used_vehicle_exp_price ?? 0);
            $row->used_vehicle_off_price = '₹ ' . number_format($t->used_vehicle_off_price ?? 0);
            $row->new_vehicle_exc_bonus  = '₹ ' . number_format($t->new_vehicle_exc_bonus ?? 0);

            // Location logic (same as Exchange)
            $location = $t->location_code && $t->location_code > 0
                ? (X_Location::find($t->location_code)->name ?? 'N/A')
                : ($t->location_other ?? 'N/A');

            $row->location = $location;

            $row->brand_make_1 = CommonHelper::enumValueById($t->brand_make_1 ?? null);

            // Action button (tumhara original)
            $row->action = '
                <div class="text-center">
                    <a href="' . route('exchange-edit', $t->id) . '#exch"
                    class="btn btn-primary btn-sm py-1 px-2"
                    >
                        Process
                    </a>
                </div>';

            return $row;
        })->values();

        // ────────────────────────────────────────────────
        // Columns – sirf reusable wala call
        // ────────────────────────────────────────────────
        $columns = $this->getAgGridColumns();

        // Action column add agar missing hai (duplicate avoid)
        $hasAction = collect($columns)->contains('field', 'action');
        if (!$hasAction) {
            $columns[] = [
                'field'         => 'action',
                'headerName'    => 'Actions',
                'width'         => 150,
                'pinned'        => 'right',
                'sortable'      => false,
                'filter'        => false,
                'cellRenderer'  => 'htmlRenderer',
                'cellClass'     => 'text-center p-0',
                'autoHeight'    => true,
            ];
        }

        // Optional: price columns ko right-align karna (Not Interested specific)
        foreach ($columns as &$col) {
            if (in_array($col['field'], ['used_vehicle_exp_price', 'used_vehicle_off_price', 'new_vehicle_exc_bonus', 'price_gap'])) {
                $col['type'] = 'rightAligned';
                $col['cellClass'] = 'text-right';
            }
        }
        unset($col);

        $gridConfig = [
            'columns' => $columns,
            'data'    => $gridData,
        ];

        $this->data['gridConfig'] = $gridConfig;

        if ($gridData->isEmpty()) {
            session()->flash('info', 'No not-interested bookings found.');
        }

        return view('booking.exchange-not-interested', $this->data);
    }



    public function intInFinance(Request $request)
    {
        $this->crud->hasAccessOrFail('list');

        $this->data['crud'] = $this->crud;
        $this->data['title'] = 'Int in Finance';

        // ────────────────────────────────────────────────
        // Base query (tumhara existing reusable method)
        // ────────────────────────────────────────────────
        $query = $this->getBaseQuery();

        // Left join finance table
        $query->leftJoin('xlr8_booking_finance as xf', 'bookings.id', '=', 'xf.bid');

        // Common conditions
        $query->where('bookings.status', '!=', 2)
            ->where(function ($q) {
                $q->whereNull('xf.fin_mode')
                    ->orWhere('xf.fin_mode', 'In-house');
            })
            ->orderBy('bookings.id', 'DESC');

        // Status filter (tumhara original logic)
        $status_filter = $request->input('status_filter', 'pending');
        if ($status_filter === 'pending') {
            $query->where('xf.status', 1);
        } elseif ($status_filter === 'complete') {
            $query->where('xf.status', 2);
        } else {
            // Default: Pending
            $query->where('xf.status', 1);
        }

        // Pagination
        $paginatedBookings = $query->paginate(50);

        // ────────────────────────────────────────────────
        // Preloads (same as before)
        // ────────────────────────────────────────────────
        $lookups = $this->getCommonLookups();
        extract($lookups);

        $saleConsultants = $lookups['saleConsultants'] ?? [];
        $financiers = $lookups['financiers'] ?? [];

        // ────────────────────────────────────────────────
        // Mapping – action button tumhara original
        // ────────────────────────────────────────────────
        $gridData = $paginatedBookings->map(function ($t, $index) use (
            $paginatedBookings,
            $segments,
            $saleConsultants,
            $financiers
        ) {
            $row = $this->mapBookingForGrid($t);

            $row->serial_no = ($paginatedBookings->currentPage() - 1) * $paginatedBookings->perPage() + $index + 1;

            // Finance-specific fields
            $row->fsc = optional($saleConsultants->firstWhere('id', $t->consultant))->name ?? 'N/A';
            $row->finance_status = $t->finance_status == 1 ? 'Pending' : ($t->finance_status == 2 ? 'Complete' : 'N/A');

            // Location logic
            $location = $t->location_code && $t->location_code > 0
                ? (X_Location::find($t->location_code)->name ?? 'N/A')
                : ($t->location_other ?? 'N/A');

            $row->location = $location;

            // Action button
            $row->action = '
                <div class="text-center">
                    <a href="' . route('finance-edit', $t->id) . '"
                    class="btn btn-primary btn-sm py-1 px-2"
                    >
                        Process
                    </a>
                </div>';

            return $row;
        })->values();

        // ────────────────────────────────────────────────
        // Columns – sirf reusable wala call
        // ────────────────────────────────────────────────
        $columns = $this->getAgGridColumns();

        // Action column add agar missing hai (duplicate avoid)
        $hasAction = collect($columns)->contains('field', 'action');
        if (!$hasAction) {
            $columns[] = [
                'field'         => 'action',
                'headerName'    => 'Actions',
                'width'         => 150,
                'pinned'        => 'right',
                'sortable'      => false,
                'filter'        => false,
                'cellRenderer'  => 'htmlRenderer',
                'cellClass'     => 'text-center p-0',
                'autoHeight'    => true,
            ];
        }

        $gridConfig = [
            'columns' => $columns,
            'data'    => $gridData,
        ];

        $this->data['gridConfig'] = $gridConfig;

        if ($gridData->isEmpty()) {
            session()->flash('info', 'No finance interested bookings found.');
        }

        return view('booking.int-in-finance', $this->data);
    }

    public function finnotInterested(Request $request)
    {
        $this->crud->hasAccessOrFail('list');

        $this->data['crud'] = $this->crud;
        $this->data['title'] = 'Not Interested';

        // ────────────────────────────────────────────────
        // Query – getBaseQuery() se start karo
        // ────────────────────────────────────────────────
        $query = $this->getBaseQuery();

        // Not Interested in Finance filter (tumhara original logic)
        $query->whereIn('bookings.fin_mode', ['Customer Self', 'Cash', 'Yet To Decide']);

        $query->orderBy('bookings.id', 'DESC');

        $paginatedBookings = $query->paginate(50);

        // ────────────────────────────────────────────────
        // Preloads
        // ────────────────────────────────────────────────
        $lookups = $this->getCommonLookups();
        extract($lookups);

        $saleConsultants = $lookups['saleConsultants'] ?? [];

        // ────────────────────────────────────────────────
        // Mapping – action button tumhara original
        // ────────────────────────────────────────────────
        $gridData = $paginatedBookings->map(function ($t, $index) use (
            $paginatedBookings,
            $segments,
            $saleConsultants
        ) {
            $row = $this->mapBookingForGrid($t);

            $row->serial_no = ($paginatedBookings->currentPage() - 1) * $paginatedBookings->perPage() + $index + 1;

            // Not Interested specific calculated fields
            $price_gap = ($t->used_vehicle_exp_price ?? 0) - ($t->used_vehicle_off_price ?? 0);
            $row->price_gap = number_format($price_gap);

            $row->used_vehicle_exp_price = '₹ ' . number_format($t->used_vehicle_exp_price ?? 0);
            $row->used_vehicle_off_price = '₹ ' . number_format($t->used_vehicle_off_price ?? 0);
            $row->new_vehicle_exc_bonus  = '₹ ' . number_format($t->new_vehicle_exc_bonus ?? 0);

            // Location logic (same as Exchange)
            $location = $t->location_code && $t->location_code > 0
                ? (X_Location::find($t->location_code)->name ?? 'N/A')
                : ($t->location_other ?? 'N/A');

            $row->location = $location;

            $row->brand_make_1 = CommonHelper::enumValueById($t->brand_make_1 ?? null);

            // Action button (tumhara original)
            $row->action = '
            <div class="text-center">
                <a href="' . route('finance-edit', $t->id) . '"
                   class="btn btn-primary btn-sm"
                   >
                    Process
                </a>
            </div>';

            return $row;
        })->values();

        // ────────────────────────────────────────────────
        // Columns – sirf reusable wala call
        // ────────────────────────────────────────────────
        $columns = $this->getAgGridColumns();

        // Action column add agar missing hai (duplicate avoid)
        $hasAction = collect($columns)->contains('field', 'action');
        if (!$hasAction) {
            $columns[] = [
                'field'         => 'action',
                'headerName'    => 'Actions',
                'width'         => 150,
                'pinned'        => 'right',
                'sortable'      => false,
                'filter'        => false,
                'cellRenderer'  => 'htmlRenderer',
                'cellClass'     => 'text-center p-0',
                'autoHeight'    => true,
            ];
        }

        // Optional: price columns ko right-align karna (Not Interested specific)
        foreach ($columns as &$col) {
            if (in_array($col['field'], ['used_vehicle_exp_price', 'used_vehicle_off_price', 'new_vehicle_exc_bonus', 'price_gap'])) {
                $col['type'] = 'rightAligned';
                $col['cellClass'] = 'text-right';
            }
        }
        unset($col);

        $gridConfig = [
            'columns' => $columns,
            'data'    => $gridData,
        ];

        $this->data['gridConfig'] = $gridConfig;

        if ($gridData->isEmpty()) {
            session()->flash('info', 'No not-interested finance bookings found.');
        }

        return view('booking.finance-not-interested', $this->data);
    }

    public function finRetail(Request $request)
    {
        $this->crud->hasAccessOrFail('list');

        $this->data['crud'] = $this->crud;
        $this->data['title'] = 'Finance Retail';

        // ────────────────────────────────────────────────
        // Query – getBaseQuery() se start karo
        // ────────────────────────────────────────────────
        $query = $this->getBaseQuery();

        // Retail filter (tumhara original logic)
        $query->where('bookings.status', 2);
        $query->where('bookings.retail', 0);  // Retail = 0

        $query->orderBy('bookings.id', 'DESC');

        $paginatedBookings = $query->paginate(50);

        // ────────────────────────────────────────────────
        // Preloads
        // ────────────────────────────────────────────────
        $lookups = $this->getCommonLookups();
        extract($lookups);

        $saleConsultants = $lookups['saleConsultants'] ?? [];
        $financiers = $lookups['financiers'] ?? [];

        // ────────────────────────────────────────────────
        // Mapping – action button tumhara original
        // ────────────────────────────────────────────────
        $gridData = $paginatedBookings->map(function ($t, $index) use (
            $paginatedBookings,
            $segments,
            $saleConsultants,
            $financiers
        ) {
            $row = $this->mapBookingForGrid($t);

            $row->serial_no = ($paginatedBookings->currentPage() - 1) * $paginatedBookings->perPage() + $index + 1;

            // Finance-specific fields
            $row->fsc = optional($saleConsultants->firstWhere('id', $t->consultant))->name ?? 'N/A';
            // $row->financier = optional($financiers->firstWhere('id', $t->financier))->name ?? 'N/A';

            // Location logic
            $location = $t->location_code && $t->location_code > 0
                ? (X_Location::find($t->location_code)->name ?? 'N/A')
                : ($t->location_other ?? 'N/A');

            $row->location = $location;

            // Action button (tumhara original)
            $row->action = '
            <div class="text-center">
                <a href="' . route('finance.retailedit', $t->id) . '"
                   class="btn btn-primary btn-sm py-1 px-2"
                    >
                    Process
                </a>
            </div>';

            return $row;
        })->values();

        // ────────────────────────────────────────────────
        // Columns – sirf reusable wala call
        // ────────────────────────────────────────────────
        $columns = $this->getAgGridColumns();

        // Action column add agar missing hai (duplicate avoid)
        $hasAction = collect($columns)->contains('field', 'action');
        if (!$hasAction) {
            $columns[] = [
                'field'         => 'action',
                'headerName'    => 'Actions',
                'width'         => 150,
                'pinned'        => 'right',
                'sortable'      => false,
                'filter'        => false,
                'cellRenderer'  => 'htmlRenderer',
                'cellClass'     => 'text-center p-0',
                'autoHeight'    => true,
            ];
        }

        $gridConfig = [
            'columns' => $columns,
            'data'    => $gridData,
        ];

        $this->data['gridConfig'] = $gridConfig;

        if ($gridData->isEmpty()) {
            session()->flash('info', 'No retail finance bookings found.');
        }

        return view('booking.finance-retail', $this->data);
    }


    // public function finRetailed(Request $request)
    // {
    //     $this->crud->hasAccessOrFail('list');

    //     $this->data['crud'] = $this->crud;
    //     $this->data['title'] = 'Finance Retail';

    //     // ────────────────────────────────────────────────
    //     // Query – getBaseQuery() se start karo
    //     // ────────────────────────────────────────────────
    //     $query = $this->getBaseQuery();

    //     // Retail filter
    //     $query->where('bookings.status', 2);
    //     $query->where('bookings.retail', 1);

    //     $query->orderBy('bookings.id', 'DESC');

    //     $paginatedBookings = $query->paginate(50);

    //     // ────────────────────────────────────────────────
    //     // Preloads
    //     // ────────────────────────────────────────────────
    //     $lookups = $this->getCommonLookups();
    //     extract($lookups);

    //     $saleConsultants = $lookups['saleConsultants'] ?? [];
    //     $financiers = $lookups['financiers'] ?? [];

    //     // ────────────────────────────────────────────────
    //     // Mapping – action button tumhara original
    //     // ────────────────────────────────────────────────
    //     $gridData = $paginatedBookings->map(function ($t, $index) use (
    //         $paginatedBookings,
    //         $segments,
    //         $saleConsultants,
    //         $financiers
    //     ) {
    //         $row = $this->mapBookingForGrid($t);

    //         $row->serial_no = ($paginatedBookings->currentPage() - 1) * $paginatedBookings->perPage() + $index + 1;

    //         // Finance-specific fields
    //         $row->fsc = optional($saleConsultants->firstWhere('id', $t->consultant))->name ?? 'N/A';
    //         $row->financier = optional($financiers->firstWhere('id', $t->financier))->name ?? 'N/A';

    //         // Location logic
    //         $location = $t->location_code && $t->location_code > 0
    //             ? (X_Location::find($t->location_code)->name ?? 'N/A')
    //             : ($t->location_other ?? 'N/A');

    //         $row->location = $location;

    //         // Action button (tumhara original)
    //         $row->action = '
    //         <div class="text-center">
    //             <a href="' . route('finance.view', $t->id) . '"
    //                class="btn btn-info btn-sm"
    //                title="View Finance">
    //                 <i class="fas fa-eye"></i> View
    //             </a>
    //         </div>';

    //         return $row;
    //     })->values();

    //     // ────────────────────────────────────────────────
    //     // Columns – sirf reusable wala call
    //     // ────────────────────────────────────────────────
    //     $columns = $this->getAgGridColumns();

    //     // Action column add agar missing hai (duplicate avoid)
    //     $hasAction = collect($columns)->contains('field', 'action');
    //     if (!$hasAction) {
    //         $columns[] = [
    //             'field'         => 'action',
    //             'headerName'    => 'Actions',
    //             'width'         => 150,
    //             'pinned'        => 'right',
    //             'sortable'      => false,
    //             'filter'        => false,
    //             'cellRenderer'  => 'htmlRenderer',
    //             'cellClass'     => 'text-center p-0',
    //             'autoHeight'    => true,
    //         ];
    //     }

    //     $gridConfig = [
    //         'columns' => $columns,
    //         'data'    => $gridData,
    //     ];

    //     $this->data['gridConfig'] = $gridConfig;

    //     if ($gridData->isEmpty()) {
    //         session()->flash('info', 'No retail finance bookings found.');
    //     }

    //     return view('booking.finance-retailed', $this->data);
    // }


    // 1. Pending Payout
    public function finPayout(Request $request)
    {
        $this->crud->hasAccessOrFail('list');

        $this->data['crud'] = $this->crud;
        $this->data['title'] = 'Finance Payout - Pending';

        $query = $this->getBaseQuery();

        $query->where('bookings.payout', 1);
        $query->where('bookings.retail', 1);
        $query->where('bookings.status', 2);

        $query->orderBy('bookings.id', 'DESC');

        $paginatedBookings = $query->paginate(50);

        $lookups = $this->getCommonLookups();
        extract($lookups);

        $saleConsultants = $lookups['saleConsultants'] ?? [];
        $financiers = $lookups['financiers'] ?? [];

        $gridData = $paginatedBookings->map(function ($t, $index) use (
            $paginatedBookings,
            $saleConsultants,
            $financiers
        ) {
            $row = $this->mapBookingForGrid($t);

            $row->serial_no = ($paginatedBookings->currentPage() - 1) * $paginatedBookings->perPage() + $index + 1;

            $row->fsc = optional($saleConsultants->firstWhere('id', $t->consultant))->name ?? 'N/A';
            // $row->financier = optional($financiers->firstWhere('id', $t->financier))->name ?? 'N/A';


            $location = $t->location_code && $t->location_code > 0
                ? (X_Location::find($t->location_code)->name ?? 'N/A')
                : ($t->location_other ?? 'N/A');

            $row->location = $location;

            // Action for Pending: Edit
            $row->action = '
            <div class="text-center">
                <a href="' . route('finance.payoutedit', $t->id) . '"
                   class="btn btn-primary btn-sm py-1 px-2"
                   >
                    Process
                </a>
            </div>';

            return $row;
        })->values();

        $columns = $this->getAgGridColumns();

        $hasAction = collect($columns)->contains('field', 'action');
        if (!$hasAction) {
            $columns[] = [
                'field'         => 'action',
                'headerName'    => 'Actions',
                'width'         => 140,
                'pinned'        => 'right',
                'sortable'      => false,
                'filter'        => false,
                'cellRenderer'  => 'htmlRenderer',
                'cellClass'     => 'text-center p-0',
                'autoHeight'    => true,
            ];
        }

        $gridConfig = [
            'columns' => $columns,
            'data'    => $gridData,
            'type'    => 'pending'
        ];

        $this->data['gridConfig'] = $gridConfig;

        // if ($gridData->isEmpty()) {
        //     session()->flash('info', 'No pending payout bookings found.');
        // }

        return view('booking.finance-payout', $this->data);
    }

    // 2. Completed Payout
    public function finPayoutCompleted(Request $request)
    {
        $this->crud->hasAccessOrFail('list');

        $this->data['crud'] = $this->crud;
        $this->data['title'] = 'Finance Payout - Completed';

        $query = $this->getBaseQuery();

        $query->where('bookings.payout', 2);
        $query->where('bookings.retail', 1);
        $query->where('bookings.status', 2);

        // Filter logic (short, excess, reconciled)
        $filter = $request->query('status_filter', 'all');
        if ($filter === 'short') {
            $query->whereRaw('fin.difference < -100');
        } elseif ($filter === 'excess') {
            $query->whereRaw('fin.difference > 100');
        } elseif ($filter === 'reconciled') {
            $query->whereRaw('fin.difference BETWEEN -100 AND 100');
        }

        $query->orderBy('bookings.id', 'DESC');

        $paginatedBookings = $query->paginate(50);

        $lookups = $this->getCommonLookups();
        extract($lookups);

        $saleConsultants = $lookups['saleConsultants'] ?? [];
        $financiers = $lookups['financiers'] ?? [];

        $gridData = $paginatedBookings->map(function ($t, $index) use (
            $paginatedBookings,
            $saleConsultants,
            $financiers
        ) {
            $row = $this->mapBookingForGrid($t);

            $row->serial_no = ($paginatedBookings->currentPage() - 1) * $paginatedBookings->perPage() + $index + 1;

            $row->fsc = optional($saleConsultants->firstWhere('id', $t->consultant))->name ?? 'N/A';
            // $row->financier = optional($financiers->firstWhere('id', $t->financier))->name ?? 'N/A';

            $location = $t->location_code && $t->location_code > 0
                ? (X_Location::find($t->location_code)->name ?? 'N/A')
                : ($t->location_other ?? 'N/A');

            $row->location = $location;

            // Action for Completed: View only
            $row->action = '
            <div class="text-center">
                <a href="' . route('finance.view', $t->id) . '"
                   class="btn btn-info btn-sm"
                   title="View Finance">
                    <i class="fas fa-eye"></i> View
                </a>
            </div>';

            return $row;
        })->values();

        $columns = $this->getAgGridColumns();

        $hasAction = collect($columns)->contains('field', 'action');
        if (!$hasAction) {
            $columns[] = [
                'field'         => 'action',
                'headerName'    => 'Actions',
                'width'         => 140,
                'pinned'        => 'right',
                'sortable'      => false,
                'filter'        => false,
                'cellRenderer'  => 'htmlRenderer',
                'cellClass'     => 'text-center p-0',
                'autoHeight'    => true,
            ];
        }

        $gridConfig = [
            'columns' => $columns,
            'data'    => $gridData,
            'type'    => 'completed'
        ];

        $this->data['gridConfig'] = $gridConfig;

        if ($gridData->isEmpty()) {
            session()->flash('info', 'No completed payout bookings found.');
        }

        return view('booking.finance-payout-completed', $this->data);
    }


    public function fetchPendBkData()
    {
        $now = Carbon::now();
        $mtdStart = $now->copy()->startOfMonth();
        $ytdStart = $now->copy()->startOfYear();

        // Cache the query for 1 hour
        $data = Cache::remember('cbr_data_' . $now->format('YmdH'), 3600, function () use ($mtdStart, $ytdStart, $now) {
            // Bulk fetch bookings
            $bookings = DB::table('xlr8_booking_master as bm')
                ->join('xlr8_vehicle_master as vm', 'bm.vh_id', '=', 'vm.id')
                ->join('bmpl_enum_master as em', 'vm.segment_id', '=', 'em.id')
                ->whereIn('bm.status', [1, 4, 6, 8])
                ->select(
                    'bm.id',
                    'bm.status',
                    'bm.b_type',
                    'bm.fin_mode',
                    'bm.buyer_type',
                    'bm.pending',
                    'bm.order',
                    'bm.dms_so',
                    'bm.booking_amount',
                    'bm.created_at',
                    DB::raw('CONCAT(em.value, "|", COALESCE(vm.oem_model, ""), "|", COALESCE(vm.oem_variant, ""), "|", COALESCE(vm.color, "")) as group_key'),
                    'em.value as seg',
                    'vm.oem_model as model',
                    'vm.oem_variant as variant',
                    'vm.color as clr',
                    'vm.code'
                )
                ->get()
                ->groupBy('group_key');

            // Bulk fetch booking amounts
            $bookingAmounts = DB::table('xlr8_booking_amount')
                ->where('status', 1)
                ->select('bid', DB::raw('SUM(amount) as total_amount'))
                ->groupBy('bid')
                ->pluck('total_amount', 'bid');




            // Bulk fetch exchange and scrappage pending statuses
            $exchanges = DB::table('xlr8_exchange')
                ->whereIn('verification_status', [0, null])
                ->select('bid', 'purchase_type')
                ->get()
                ->groupBy('bid')
                ->map(function ($group) {
                    return [
                        'exchange_pending' => $group->where('purchase_type', 'Exchange')->count() > 0 ? 1 : 0,
                        'scrappage_pending' => $group->where('purchase_type', 'Scrappage')->count() > 0 ? 1 : 0,
                    ];
                });

            // Bulk fetch finance pending statuses
            $finances = DB::table('xlr8_booking_finance')
                ->whereIn('verification_status', [0, null])
                ->pluck('bid')
                ->mapWithKeys(fn($bid) => [$bid => 1]);

            $data = collect();
            $index = 1;

            foreach ($bookings as $groupKey => $groupBookings) {
                [$seg, $model, $variant, $clr] = explode('|', $groupKey);

                $liveGroup = $groupBookings->whereIn('status', [1, 6, 8])->where('b_type', '!=', 'dummy');

                $total_bookings = $liveGroup->count();
                if ($total_bookings === 0) continue;

                $bkn_bookings = $liveGroup->where('b_type', 'Individual')->count();
                $chr_bookings = $liveGroup->where('b_type', 'Dealer')->count();



                $on_hold = $liveGroup->where('status', 6)->count();

                $verify = $liveGroup->where('order', 1)->count();

                $orders = $liveGroup->where('order', 2)->whereNull('dms_so')->count();

                $payments = $liveGroup->filter(function ($booking) use ($bookingAmounts) {
                    $total_amount = $bookingAmounts->get($booking->id, 0);
                    return $total_amount < $booking->booking_amount;
                })->count();

                $data_pending = $liveGroup->where('pending', '>', 0)->count();

                $refunds = $groupBookings->where('status', 4)->count();



                $data->push([
                    'sno' => $index++,
                    'seg' => $seg,
                    'model' => $model,
                    'variant' => $variant,
                    'clr' => $clr,
                    'total_bookings' => $total_bookings,
                    'bkn_bookings' => $bkn_bookings,
                    'chr_bookings' => $chr_bookings,
                    'verify' => $verify,
                    'orders' => $orders,
                    'payments' => $payments,
                    'data' => $data_pending,
                    'refund' => $refunds,
                ]);
            }

            return $data;
        });



        $title = 'Pending Data Report';
        $filename = 'PndngDataRprt_' . $now->format('Y-m-d-H-i-s') . '.xlsx';
        $stkbr = $tbr = null;
        $header = [
            ['title' => 'S.No.', 'field' => 'sno', 'hozAlign' => 'center', 'formatter' => 'plaintext'],
            [
                'title' => 'Vehicle Info',
                'columns' => [
                    ['title' => 'Segment', 'field' => 'seg', 'headerFilter' => 'select'],
                    ['title' => 'Model', 'field' => 'model', 'headerFilter' => 'select'],
                    ['title' => 'Variant', 'field' => 'variant', 'headerFilter' => 'select'],
                    ['title' => 'Color', 'field' => 'clr', 'headerFilter' => 'select'],
                ]
            ],

            [
                'title' => 'Bookings',
                'columns' => [
                    ['title' => 'Total', 'field' => 'total_bookings', 'bottomCalc' => 'sum'],
                    ['title' => 'BKN', 'field' => 'bkn_bookings', 'bottomCalc' => 'sum'],
                    ['title' => 'CHR', 'field' => 'chr_bookings', 'bottomCalc' => 'sum'],
                ]
            ],

            [
                'title' => 'Pending Actions',
                'columns' => [
                    ['title' => 'Verify', 'field' => 'verify', 'bottomCalc' => 'sum'],
                    ['title' => 'Orders', 'field' => 'orders', 'bottomCalc' => 'sum'],
                    ['title' => 'Payments', 'field' => 'payments', 'bottomCalc' => 'sum'],
                    ['title' => 'Data', 'field' => 'data', 'bottomCalc' => 'sum'],
                    ['title' => 'Refund', 'field' => 'refund', 'bottomCalc' => 'sum'],
                ]
            ],

        ];

        return [$header, $data, $tbr, $stkbr, $filename, $title];
    }


    public function pendingPayment(Request $request)
    {
        $this->crud->hasAccessOrFail('list');

        $this->data['crud'] = $this->crud;
        $this->data['title'] = 'Pending Payment';

        $bookingAmountTable = (new Bookingamount)->getTable();

        $query = $this->getBaseQuery();

        $query->whereIn('bookings.status', [1, 8]);
        $query->where('bookings.b_type', 'Active');
        $query->whereIn('bookings.col_type', [2, 3]);

        $query->where(function ($q) use ($bookingAmountTable) {
            $q->whereRaw("bookings.booking_amount > COALESCE((
                SELECT SUM(amount)
                FROM {$bookingAmountTable}
                WHERE {$bookingAmountTable}.bid = bookings.id
                AND {$bookingAmountTable}.deleted_at IS NULL
            ), 0)")
                ->orWhereNull('bookings.receipt_no')
                ->orWhere('bookings.receipt_no', '');
        });

        $query->orderBy('bookings.id', 'desc');

        $paginatedBookings = $query->paginate(50);

        $gridData = $paginatedBookings->map(function ($t, $index) use ($paginatedBookings) {
            $row = $this->mapBookingForGrid($t);

            $row->serial_no = ($paginatedBookings->currentPage() - 1) * $paginatedBookings->perPage() + $index + 1;

            $paid = Bookingamount::where('bid', $t->id)->sum('amount') ?? 0;
            $balance = $t->booking_amount - $paid;

            $row->booking_amount = number_format($t->booking_amount ?? 0);
            $row->paid_amount = number_format($paid);
            $row->balance = number_format($balance);
            $row->receipt_no = $t->receipt_no ?? 'Missing';

            $row->action = '<a href="' . route('booking.pending-edit', $t->id) . '#pending"
                            class="btn btn-primary btn-sm py-1 px-2" title="Add/Edit Payment">
                                Process
                        </a>';

            return $row;
        })->values();

        $columns = $this->getAgGridColumns();

        $hasAction = collect($columns)->contains('field', 'action');
        if (!$hasAction) {
            $columns[] = [
                'field'         => 'action',
                'headerName'    => 'Action',
                'width'         => 140,
                'pinned'        => 'right',
                'sortable'      => false,
                'filter'        => false,
                'cellRenderer'  => 'htmlRenderer',
                'cellClass'     => 'text-center p-0',
                'autoHeight'    => true,
            ];
        }

        $gridConfig = [
            'columns' => $columns,
            'data'    => $gridData,
        ];

        $this->data['gridConfig'] = $gridConfig;



        return view('booking.pending-payment', $this->data);
    }

    public function pendingInsurance(Request $request)
    {
        $this->crud->hasAccessOrFail('list');

        $this->data['crud'] = $this->crud;
        $this->data['title'] = 'Pending Insurance';

        // ────────────────────────────────────────────────
        // Query – full model (no manual select)
        // ────────────────────────────────────────────────
        $query = $this->getBaseQuery();

        $query->where('bookings.status', 2); // Invoiced

        // Already insured bookings exclude karo
        $insuredBookingIds = DB::table('xlr8_booking_insurance')
            ->pluck('bid')
            ->toArray();

        $query->whereNotIn('bookings.id', $insuredBookingIds);

        // Time-based filter (tumhara original logic)
        $status_filter = $request->input('status_filter', 'all');
        $now = Carbon::now();

        if ($status_filter === 'this_month') {
            $query->whereMonth('booking_date', $now->month)
                ->whereYear('booking_date', $now->year);
        } elseif ($status_filter === 'last_month') {
            $query->whereMonth('booking_date', $now->subMonth()->month)
                ->whereYear('booking_date', $now->subMonth()->year);
        } elseif ($status_filter === 'this_year') {
            $query->whereYear('booking_date', $now->year);
        }

        $paginatedBookings = $query->orderBy('booking_date', 'DESC')->paginate(50);

        // ────────────────────────────────────────────────
        // Preloads (N+1 avoid)
        // ────────────────────────────────────────────────
        $lookups = $this->getCommonLookups();
        extract($lookups);

        $saleConsultants = $lookups['saleConsultants'] ?? [];
        $financiers = $lookups['financiers'] ?? [];

        // ────────────────────────────────────────────────
        // Mapping – action button tumhara original
        // ────────────────────────────────────────────────
        $gridData = $paginatedBookings->map(function ($t, $index) use (
            $paginatedBookings,
            $segments,
            $saleConsultants,
            $financiers
        ) {
            $row = $this->mapBookingForGrid($t);

            $row->serial_no = ($paginatedBookings->currentPage() - 1) * $paginatedBookings->perPage() + $index + 1;

            $row->action = '
            <div class="text-center">
                <a href="' . route('insurance.edit', $t->id) . '"
                   class="btn btn-primary btn-sm py-1 px-2"
                   >
                    Process
                </a>
            </div>';

            return $row;
        })->values();

        // ────────────────────────────────────────────────
        // Columns – reusable call
        // ────────────────────────────────────────────────
        $columns = $this->getAgGridColumns();

        // Action column add agar missing hai (duplicate avoid)
        $hasAction = collect($columns)->contains('field', 'action');
        if (!$hasAction) {
            $columns[] = [
                'field'         => 'action',
                'headerName'    => 'Action',
                'width'         => 120,
                'pinned'        => 'right',
                'sortable'      => false,
                'filter'        => false,
                'cellRenderer'  => 'htmlRenderer',
                'cellClass'     => 'text-center p-0',
                'autoHeight'    => true,
            ];
        }

        $gridConfig = [
            'columns' => $columns,
            'data'    => $gridData,
        ];

        $this->data['gridConfig'] = $gridConfig;



        return view('booking.pending-insurance', $this->data);
    }


    public function pendingRto(Request $request)
    {
        $this->crud->hasAccessOrFail('list');

        $this->data['crud'] = $this->crud;
        $this->data['title'] = 'Pending RTO';

        // ────────────────────────────────────────────────
        // Query – full model (no manual select)
        // ────────────────────────────────────────────────
        $query = $this->getBaseQuery();

        $query->where('bookings.status', 2); // Invoiced

        // Already RTO processed bookings exclude karo
        $rtoDoneIds = DB::table('xlr8_booking_rto')
            ->where('status', 2)   // ← yahan apna table ka status column use karo
            ->pluck('bid')
            ->toArray();

        $query->whereNotIn('bookings.id', $rtoDoneIds);

        // Time-based filter (tumhara original logic)
        $status_filter = $request->input('status_filter', 'all');
        $now = Carbon::now();

        if ($status_filter === 'this_month') {
            $query->whereMonth('booking_date', $now->month)
                ->whereYear('booking_date', $now->year);
        } elseif ($status_filter === 'last_month') {
            $query->whereMonth('booking_date', $now->subMonth()->month)
                ->whereYear('booking_date', $now->subMonth()->year);
        } elseif ($status_filter === 'this_year') {
            $query->whereYear('booking_date', $now->year);
        }

        $paginatedBookings = $query->orderBy('booking_date', 'DESC')->paginate(50);

        // ────────────────────────────────────────────────
        // Preloads
        // ────────────────────────────────────────────────
        $lookups = $this->getCommonLookups();
        extract($lookups);

        $saleConsultants = $lookups['saleConsultants'] ?? [];
        $financiers = $lookups['financiers'] ?? [];

        // ────────────────────────────────────────────────
        // Mapping – action button tumhara original
        // ────────────────────────────────────────────────
        $gridData = $paginatedBookings->map(function ($t, $index) use (
            $paginatedBookings,
            $segments,
            $saleConsultants,
            $financiers
        ) {
            $row = $this->mapBookingForGrid($t);

            $row->serial_no = ($paginatedBookings->currentPage() - 1) * $paginatedBookings->perPage() + $index + 1;

            $row->action = '
            <div class="text-center">
                <a href="' . route('booking.rto.edit', $t->id) . '"
                   class="btn btn-primary btn-sm py-1 px-2"
                   >
                    Process
                </a>
            </div>';

            return $row;
        })->values();

        // ────────────────────────────────────────────────
        // Columns – reusable call
        // ────────────────────────────────────────────────
        $columns = $this->getAgGridColumns();

        // Action column add agar missing hai (duplicate avoid)
        $hasAction = collect($columns)->contains('field', 'action');
        if (!$hasAction) {
            $columns[] = [
                'field'         => 'action',
                'headerName'    => 'Action',
                'width'         => 120,
                'pinned'        => 'right',
                'sortable'      => false,
                'filter'        => false,
                'cellRenderer'  => 'htmlRenderer',
                'cellClass'     => 'text-center p-0',
                'autoHeight'    => true,
            ];
        }

        $gridConfig = [
            'columns' => $columns,
            'data'    => $gridData,
        ];

        $this->data['gridConfig'] = $gridConfig;

        if ($gridData->isEmpty()) {
            session()->flash('info', 'No pending RTO bookings found for the selected period.');
        }

        return view('booking.pending-rto', $this->data);
    }

    public function pendingDeliveries(Request $request)
    {
        $this->crud->hasAccessOrFail('list');

        $this->data['crud'] = $this->crud;
        $this->data['title'] = 'Pending Deliveries';

        // ────────────────────────────────────────────────
        // Query – full model (no manual select)
        // ────────────────────────────────────────────────
        $query = $this->getBaseQuery();

        $query->where('bookings.status', 2); // Invoiced

        // Already delivered bookings exclude karo
        $deliveredIds = DB::table('xlr8_booking_delivered')
            ->where('status', 1)
            ->pluck('bid')
            ->toArray();

        $query->whereNotIn('bookings.id', $deliveredIds);

        $paginatedBookings = $query->orderBy('booking_date', 'DESC')->paginate(50);

        // ────────────────────────────────────────────────
        // Preloads (agar zaroori ho to)
        // ────────────────────────────────────────────────
        $lookups = $this->getCommonLookups();
        extract($lookups);

        $saleConsultants = $lookups['saleConsultants'] ?? [];
        $financiers = $lookups['financiers'] ?? [];

        // ────────────────────────────────────────────────
        // Mapping – action button tumhara original
        // ────────────────────────────────────────────────
        $gridData = $paginatedBookings->map(function ($t, $index) use (
            $paginatedBookings,
            $segments,
            $saleConsultants,
            $financiers
        ) {
            $row = $this->mapBookingForGrid($t);

            $row->serial_no = ($paginatedBookings->currentPage() - 1) * $paginatedBookings->perPage() + $index + 1;

            $row->action = '
            <div class="text-center">
                <a href="' . backpack_url("booking/{$t->id}/delivery-edit") . '#delivery"
                   class="btn btn-primary btn-sm py-1 px-2">
                    Process
                </a>
            </div>';

            return $row;
        })->values();

        // ────────────────────────────────────────────────
        // Columns – reusable call
        // ────────────────────────────────────────────────
        $columns = $this->getAgGridColumns();

        // Action column add agar missing hai (duplicate avoid)
        $hasAction = collect($columns)->contains('field', 'action');
        if (!$hasAction) {
            $columns[] = [
                'field'         => 'action',
                'headerName'    => 'Action',
                'width'         => 120,
                'pinned'        => 'right',
                'sortable'      => false,
                'filter'        => false,
                'cellRenderer'  => 'htmlRenderer',
                'cellClass'     => 'text-center p-0',
                'autoHeight'    => true,
            ];
        }

        $gridConfig = [
            'columns' => $columns,
            'data'    => $gridData,
        ];

        $this->data['gridConfig'] = $gridConfig;

        if ($gridData->isEmpty()) {
            session()->flash('info', 'No pending deliveries found.');
        }

        return view('booking.pending-deliveries', $this->data);
    }

    public function pendingRegistration(Request $request)
    {
        $this->crud->hasAccessOrFail('list');

        $this->data['crud'] = $this->crud;
        $this->data['title'] = 'Pending Registration';

        $query = $this->getBaseQuery()
            ->join('xlr8_booking_rto', function ($join) {
                $join->on('xlr8_booking_rto.bid', '=', 'bookings.id')
                    ->where('xlr8_booking_rto.status', 1)
                    ->whereNull('xlr8_booking_rto.vh_rgn_no');
            });

        if ($request->has('customer_type') && $request->customer_type !== 'all') {
            $filterType = $request->customer_type === 'actual' ? 'active' : $request->customer_type;
            $query->where('bookings.b_type', $filterType);
        }

        $query->orderBy('bookings.id', 'DESC');

        $paginatedBookings = $query->paginate(50);

        $lookups = $this->getCommonLookups();
        extract($lookups);

        $gridData = $paginatedBookings->map(function ($t, $index) use ($paginatedBookings) {
            $row = $this->mapBookingForGrid($t);

            $row->serial_no = ($paginatedBookings->currentPage() - 1) * $paginatedBookings->perPage() + $index + 1;

            $row->action = '
            <div class="text-center">
                <a href="' . route('booking.rto.edit', $t->id) . '"
                   class="btn btn-primary btn-sm py-1 px-2">
                    Process
                </a>
            </div>';

            return $row;
        })->values();

        $columns = $this->getAgGridColumns();

        $hasAction = collect($columns)->contains('field', 'action');
        if (!$hasAction) {
            $columns[] = [
                'field'         => 'action',
                'headerName'    => 'Action',
                'width'         => 120,
                'pinned'        => 'right',
                'sortable'      => false,
                'filter'        => false,
                'cellRenderer'  => 'htmlRenderer',
                'cellClass'     => 'text-center p-0',
                'autoHeight'    => true,
            ];
        }

        $gridConfig = [
            'columns' => $columns,
            'data'    => $gridData,
        ];

        $this->data['gridConfig'] = $gridConfig;

        if ($gridData->isEmpty()) {
            session()->flash('info', 'No pending registration bookings found.');
        }

        return view('booking.pending-registration', $this->data);
    }

    public function pendingDO(Request $request)
    {
        $this->crud->hasAccessOrFail('list');
        $this->data['crud'] = $this->crud;
        $this->data['title'] = 'Pending DO';

        $query = $this->getBaseQuery();

        // === Updated Pending DO Logic ===
        // Sirf woh rows dikhao jinke instrument_type 1 ya 2 Nahi hain,
        // ya phir type 1/2 hai lekin instrument_ref_no already filled hai.
        $query->whereNotIn('f.instrument_type', [1, 2])
            ->where(function ($q) {
                $q->whereNull('f.instrument_ref_no')
                    ->orWhere('f.instrument_ref_no', '');
            });

        $paginatedBookings = $query->orderBy('bookings.booking_date', 'DESC')
            ->paginate(50);

        $gridData = $paginatedBookings->map(function ($booking, $index) use ($paginatedBookings) {
            $row = $this->mapBookingForGrid($booking);
            $row->serial_no = ($paginatedBookings->currentPage() - 1) * $paginatedBookings->perPage() + $index + 1;

            $row->action = '<div class="text-center">
            <a href="' . route('finance.do.edit', $booking->id) . '"
               class="btn btn-primary btn-sm py-1 px-2">
                </i> Process
            </a>
        </div>';

            return $row;
        })->values();

        $columns = $this->getAgGridColumns();
        if (!collect($columns)->pluck('field')->contains('action')) {
            $columns[] = [
                'field'        => 'action',
                'headerName'   => 'Action',
                'width'        => 160,
                'pinned'       => 'right',
                'sortable'     => false,
                'filter'       => false,
                'cellRenderer' => 'htmlRenderer',
            ];
        }

        $this->data['gridConfig'] = [
            'columns' => $columns,
            'data'    => $gridData,
        ];

        if ($gridData->isEmpty()) {
            session()->flash('info', 'No pending Delivery Order found.');
        }

        return view('booking.pending-do', $this->data);
    }
    public function doEdit($id)
    {
        $this->crud->hasAccessOrFail('list');

        $booking = Booking::findOrFail($id);

        // Finance record agar nahi hai to bana do
        $finance = XFinance::firstOrCreate(
            ['bid' => $id],
            [
                'vh_id' => $booking->vh_id,
                'fin_mode' => $booking->fin_mode ?? 'In-house',
                'verification_status' => 1,
                'case_status' => 1,
                'created_by' => Auth::id(),
            ]
        );

        $data = $this->getFullBookingData($id, 'doedit'); // yeh existing method use kar rahe hain

        return view('booking.doedit', array_merge($data->getData(), [
            'booking' => $booking,
            'finance' => $finance,
        ]));
    }

    public function doUpdate(Request $request, $id)
    {
        $request->validate([
            'instrument_ref_no' => 'required|string|max:50|min:3',
        ]);

        $finance = XFinance::where('bid', $id)->firstOrFail();

        $finance->update([
            'instrument_ref_no' => trim($request->instrument_ref_no),
            'retail'            => 1,
            'updated_by'        => Auth::id(),
            'updated_at'        => now(),
        ]);

        return redirect()->route('booking.pending-do')
            ->with('success', "Delivery Order #{$request->instrument_ref_no} saved successfully!");
    }





    public function pendingInvoices(Request $request)
    {
        $this->crud->hasAccessOrFail('list');

        $this->data['crud'] = $this->crud;
        $this->data['title'] = 'Pending Invoices';

        // ────────────────────────────────────────────────
        // Query – full model (no manual select)
        // ────────────────────────────────────────────────
        $query = $this->getBaseQuery()
            ->whereIn('dealer_status', [1]);

        if ($request->has('customer_type') && $request->customer_type !== 'all') {
            $filterType = $request->customer_type === 'actual' ? 'active' : $request->customer_type;
            $query->where('bookings.b_type', $filterType);
        }

        $paginatedBookings = $query->orderBy('bookings.id', 'DESC')->paginate(50);

        // ────────────────────────────────────────────────
        // Preloads (agar zaroori ho to)
        // ────────────────────────────────────────────────
        $lookups = $this->getCommonLookups();
        extract($lookups);

        $saleConsultants = $lookups['saleConsultants'] ?? [];
        $financiers = $lookups['financiers'] ?? [];

        // ────────────────────────────────────────────────
        // Mapping – action button same as tumhara original
        // ────────────────────────────────────────────────
        $gridData = $paginatedBookings->map(function ($t, $index) use (
            $paginatedBookings,
            $segments,
            $saleConsultants,
            $financiers
        ) {
            $row = $this->mapBookingForGrid($t);

            $row->serial_no = ($paginatedBookings->currentPage() - 1) * $paginatedBookings->perPage() + $index + 1;

            // Invoice fields – pending ke liye default values
            $row->inv_date = '---';
            // $row->dealer_inv_date = '---';
            $row->inv_no = '<span class="text-danger">Pending</span>';

            // Action button – tumhara original
            $row->action = '
            <div class="text-center">
                <a href="' . backpack_url("booking/{$t->id}/dealer-invoice") . '"
                   class="btn btn-primary btn-sm py-1 px-2"
                   title="Edit Dealer Invoice">
                    Process
                </a>
            </div>';

            return $row;
        })->values();

        // ────────────────────────────────────────────────
        // Columns – reusable call
        // ────────────────────────────────────────────────
        $columns = $this->getAgGridColumns();

        // Action column add agar missing hai (duplicate avoid)
        $hasAction = collect($columns)->contains('field', 'action');
        if (!$hasAction) {
            $columns[] = [
                'field'         => 'action',
                'headerName'    => 'Action',
                'width'         => 120,
                'pinned'        => 'right',
                'sortable'      => false,
                'filter'        => false,
                'cellRenderer'  => 'htmlRenderer',
                'cellClass'     => 'text-center p-0',
                'autoHeight'    => true,
            ];
        }

        $gridConfig = [
            'columns' => $columns,
            'data'    => $gridData,
        ];

        $this->data['gridConfig'] = $gridConfig;

        if ($gridData->isEmpty()) {
            session()->flash('info', 'No pending invoices found.');
        }

        return view('booking.pending-invoices', $this->data);
    }

    public function pendingEdit($id)
    {
        $this->crud->hasAccessOrFail('update');

        $booking = Booking::findOrFail($id);

        // Total paid amount
        $totalPaid = \App\Models\Bookingamount::where('bid', $booking->id)
            ->sum('amount') ?? 0;

        // Fetch all receipt logs for this booking (this was missing)
        $receiptLogs = \App\Models\Bookingamount::where('bid', $booking->id)
            ->orderBy('date', 'desc') // or 'created_at'
            ->get();

        // Prepare data array
        $data = [
            'total_amount'    => $totalPaid,
            'collector_name'  => User::find($booking->col_by)?->name ?? 'N/A',
            // Add other data if needed (branch, location, etc.)
            'branch'          => $booking->branch?->name ?? 'N/A',
            'location'        => $booking->location?->name ?? 'N/A',
        ];

        return view('booking.pendedit', compact('booking', 'data', 'receiptLogs'));
    }

    public function pendingUpdate(Request $request, $id)
    {
        Log::info('=== PENDING UPDATE STARTED ===', [
            'booking_id' => $id,
            'user_id'    => backpack_auth()->id(),
            'ip'         => $request->ip(),
            'pending_flag' => $request->has('pending_flag'),
            'user_agent' => $request->userAgent(),
        ]);



        $booking = Booking::findOrFail($id);

        // dd($request->all());
        // UPPERCASE ALL IMPORTANT FIELDS
        $request->merge([
            'pan_no'                => strtoupper($request->input('pan_no', '')),
            'adhar_no'              => strtoupper($request->input('adhar_no', '')),
            'online_bk_ref_no'      => strtoupper($request->input('online_bk_ref_no', '')),
            'dms_no'                => strtoupper($request->input('dms_no', '')),
            'dms_otf'               => strtoupper($request->input('dms_otf', '')),
            'dms_so'                => strtoupper($request->input('dms_so', '')),
            'chassis'               => strtoupper($request->input('chassis', '')),
            'invoice_number'        => strtoupper($request->input('invoice_number', '')),
            'dealer_invoice_number' => strtoupper($request->input('dealer_invoice_number', '')),
        ]);

        Log::info('Form data after uppercase', $request->except(['_token']));

        // =============== VALIDATION ===============
        $validator = Validator::make($request->all(), [
            'pan_no'                => ['required', 'regex:/^[A-Z]{5}\d{4}[A-Z]$/'],
            'adhar_no'              => ['required', 'regex:/^\d{4}-\d{4}-\d{4}$/'],
            'dms_no'                => ['required', 'regex:/^B-\d{8}$/'],
            'dms_otf'               => ['required', 'regex:/^OTF\d{2}[A-Z]\d{6}$/'],
            'hidden_otf_date'       => ['required'],
            'online_bk_ref_no'      => ['required_if:b_mode,Online', 'nullable'],
            'chassis'               => [$request->has('pending_flag') ? 'required' : 'nullable', 'regex:/^S\d[A-Z]\d{5}$/'],
            'invoice_number'        => ['nullable', 'regex:/^INV\d{2}[A-Z]\d{6}$/'],
            'invoice_date'          => ['nullable'],
            'dealer_invoice_number' => ['nullable', 'regex:/^[A-Z]{3}\d{2}[A-Z]\d{6}$/'],
            'dealer_invoice_date'   => ['nullable'],
        ], [
            'pan_no.regex'              => 'PAN must be like ABCDE1234F',
            'adhar_no.regex'            => 'Aadhar must be 1234-5678-9012',
            'dms_no.regex'              => 'DMS No must be B-12345678',
            'dms_otf.regex'             => 'OTF must be OTF00A123456',
            'chassis.regex'             => 'Chassis must be S1A12345',
            'dealer_invoice_number.regex' => 'Dealer Invoice must be like ABC12K555555 or INV12K555555',
            'invoice_number.regex'      => 'Invoice must be INV00A123456',
        ]);


        // =============== INVOICE HANDLING ===============
        if ($request->has('pending_flag')) {
            $oldStatus = $booking->status;

            // OEM invoice – sirf hidden se lein (Y-m-d format)
            $booking->inv_no   = $request->input('invoice_number');
            $booking->inv_date = $request->filled('hidden_invoice_date')
                ? $request->hidden_invoice_date
                : null;

            // Dealer invoice – sirf tab update jab user ne bhara ho
            if ($request->filled('dealer_invoice_number') && $request->filled('hidden_dealer_invoice_date')) {
                $booking->dealer_inv_no   = $request->dealer_invoice_number;
                $booking->dealer_inv_date = $request->hidden_dealer_invoice_date;

                // Yeh line add kar di — jo tumne bola
                $booking->dealer_status = 1;
                Log::info('Dealer status set to 1', [
                    'dealer_inv_no'   => $booking->dealer_inv_no,
                    'dealer_inv_date' => $booking->dealer_inv_date,
                ]);
            } else {
                // Dealer khali hai → dealer fields touch nahi karenge
                // (purana value rehne do ya null kar do — abhi null nahi kar rahe)
            }

            $booking->status = 2; // Invoiced

            // Log karo save se pehle
            Log::info('Invoice fields before save', [
                'inv_no'           => $booking->inv_no,
                'inv_date'         => $booking->inv_date,
                'dealer_inv_no'    => $booking->dealer_inv_no,
                'dealer_inv_date'  => $booking->dealer_inv_date,
                'dealer_status'    => $booking->dealer_status ?? 'unchanged',
            ]);

            // Optional: debug ke liye full object log
            // Log::debug('Full booking object before save', $booking->toArray());
        }

        if ($validator->fails()) {
            Log::warning('Validation FAILED', [
                'booking_id' => $id,
                'errors'     => $validator->errors()->toArray()
            ]);

            return redirect()->back()->withErrors($validator)->withInput();
        }

        Log::info('Validation PASSED');


        $changes = [];

        $this->logChange($booking, 'online_bk_ref_no', $request->online_bk_ref_no, $changes);
        $this->logChange($booking, 'pan_no', $request->pan_no, $changes);
        $this->logChange($booking, 'adhar_no', $request->adhar_no, $changes);
        $this->logChange($booking, 'dms_no', $request->dms_no, $changes);
        $this->logChange($booking, 'dms_otf', $request->dms_otf, $changes);
        $this->logChange($booking, 'otf_date', $request->hidden_otf_date, $changes);
        $this->logChange($booking, 'chasis_no', $request->chassis, $changes);

        // DMS SO Handling
        if ($request->has('not_required')) {
            if ($booking->dms_so != 0) {
                $changes[] = "DMS SO marked as Not Required";
                Log::info('DMS SO marked not required');
            }
            $booking->dms_so = 0;
        } else {
            $this->logChange($booking, 'dms_so', $request->dms_so, $changes);
        }



        // =============== SAVE ===============
        try {
            $booking->save();
            Log::info('Booking saved successfully', ['booking_id' => $id, 'changes_count' => count($changes)]);
        } catch (\Exception $e) {
            Log::error('FAILED to save booking', [
                'booking_id' => $id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString()
            ]);
            return redirect()->back()->withErrors(['save' => 'Database error. Check logs.'])->withInput();
        }

        // =============== FINAL LOG & REDIRECT ===============
        if (!empty($changes)) {
            Log::info('Pending data updated', ['changes' => $changes]);
        }

        $msg = $request->has('pending_flag')
            ? 'Booking successfully marked as INVOICED! Chassis: ' . $request->chassis
            : 'Pending data updated successfully!';

        Log::info('=== PENDING UPDATE SUCCESS ===', [
            'booking_id' => $id,
            'final_status' => $booking->status,
            'message' => $msg
        ]);

        return redirect()->route('booking.index')->with('success', $msg);
    }

    private function logChange($model, $field, $newValue, &$changes)
    {
        if ($model->$field != $newValue) {
            $old = $model->$field ?? '(empty)';
            $new = $newValue ?? '(empty)';
            $changes[] = ucfirst(str_replace('_', ' ', $field)) . " changed from '{$old}' → '{$new}'";
            $model->$field = $newValue;
            Log::info("Field updated: {$field}", ['old' => $old, 'new' => $new]);
        }
    }
    /**
     * Helper to update only if different + track change
     */
    private function updateIfDifferent($model, $field, $newValue, &$changes)
    {
        $current = $model->$field;
        $new     = $newValue ?? null;

        if ($current != $new) {
            $model->$field = $new;
            if (!empty($new)) {
                $changes[] = ucfirst(str_replace('_', ' ', $field)) . " updated to " . $new;
            }
        }
    }

    public function requestRefund(Request $request, $id)
    {
        $userId   = backpack_auth()->id()   ?? 'unknown';
        $userName = backpack_auth()->user()?->name ?? 'system';

        Log::info('REFUND_REQUEST_START', [
            'user_id'    => $userId,
            'user_name'  => $userName,
            'booking_id' => $id,
            'ip'         => $request->ip(),
            'input_keys' => array_keys($request->all()),
        ]);

        // Booking lookup
        $booking = Booking::find($id);

        if (!$booking) {
            Log::warning('REFUND_BOOKING_NOT_FOUND', [
                'requested_id' => $id,
                'user_id'      => $userId,
            ]);
            return redirect()->back()->with('error', 'Booking not found.');
        }

        Log::info('REFUND_BOOKING_FOUND', [
            'booking_id'     => $booking->id,
            'current_status' => $booking->status,
            'booking_amount' => $booking->booking_amount ?? 'MISSING_FIELD',
            'user_id'        => $userId,
        ]);

        // Validation
        $validator = Validator::make($request->all(), [
            'deduction'        => 'required|numeric|min:0|lte:booking_amount',
            'remaining_amount' => 'required|numeric|min:0',
            'bank_name'        => 'required|string|max:255',
            'branch_name'      => 'required|string|max:255',
            'account_type'     => 'required|in:savings,current',
            'account_number'   => 'required|string|max:20',
            'holder_name'      => 'required|string|max:255',
            'ifsc_code'        => 'required|string',
            'deduction_reason' => 'required|string|max:500',
            'acc_proof'        => 'required|file|mimes:jpeg,png,jpg,pdf|max:2048',
            'aadhar'           => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
            'pan'              => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
        ], [
            'deduction.lte'      => 'Deduction cannot exceed booking amount.',
            'acc_proof.required' => 'Account proof is mandatory.',
            'acc_proof.max'      => 'File size max 2MB.',
        ]);

        if ($validator->fails()) {
            Log::warning('REFUND_VALIDATION_FAILED', [
                'booking_id' => $id,
                'user_id'    => $userId,
                'errors'     => $validator->errors()->toArray(),
                'input'      => $request->except(['_token', 'acc_proof', 'aadhar', 'pan']),
            ]);
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        Log::info('REFUND_VALIDATION_PASSED', ['booking_id' => $id]);

        // Remaining amount check
        $calculated = (float) ($booking->booking_amount ?? 0) - (float) ($request->deduction ?? 0);
        $submitted  = (float) ($request->remaining_amount ?? 0);

        Log::debug('REFUND_AMOUNT_CALCULATION', [
            'booking_amount' => $booking->booking_amount ?? 'null',
            'deduction'      => $request->deduction,
            'calculated'     => $calculated,
            'submitted'      => $submitted,
            'difference'     => abs($calculated - $submitted),
        ]);

        if (abs($calculated - $submitted) > 0.01) {
            Log::warning('REFUND_AMOUNT_MISMATCH', [
                'booking_id' => $id,
                'calculated' => $calculated,
                'submitted'  => $submitted,
                'diff'       => abs($calculated - $submitted),
                'user_id'    => $userId,
            ]);
            return redirect()->back()
                ->with('error', 'Remaining amount does not match calculation.')
                ->withInput();
        }

        try {
            Log::info('REFUND_CREATE_START', [
                'booking_id' => $id,
                'amount'     => $submitted,
            ]);

            $refund = Xl_Refunds::create([
                'entity_type'    => 'booking',
                'entity_id'      => $booking->id,
                'bank_name'      => strtoupper(trim($request->bank_name ?? '')),
                'branch_name'    => strtoupper(trim($request->branch_name ?? '')),
                'account_type'   => $request->account_type,
                'account_number' => trim($request->account_number ?? ''),
                'holder_name'    => trim($request->holder_name ?? ''),
                'ifsc_code'      => strtoupper(trim($request->ifsc_code ?? '')),
                'req_date'       => now()->format('Y-m-d'),
                'req_by'         => $userId,
                'amount'         => $submitted,
                'details'        => trim($request->deduction_reason ?? ''),
            ]);

            Log::notice('REFUND_RECORD_CREATED', [
                'refund_id'   => $refund->id,
                'booking_id'  => $booking->id,
                'amount'      => $refund->amount,
                'req_by'      => $userId,
            ]);

            // Media attachments
            foreach (['acc_proof' => 'account_proof', 'aadhar' => 'aadhar', 'pan' => 'pan'] as $field => $type) {
                if ($request->hasFile($field) && $request->file($field)->isValid()) {
                    try {
                        $media = $refund->addMediaFromRequest($field)
                            ->withCustomProperties(['document_type' => $type])
                            ->toMediaCollection('refund-documents');

                        Log::info('REFUND_MEDIA_ADDED', [
                            'refund_id' => $refund->id,
                            'field'     => $field,
                            'type'      => $type,
                            'media_id'  => $media->id ?? 'unknown',
                            'file_name' => $media->file_name ?? 'unknown',
                        ]);
                    } catch (\Exception $mediaEx) {
                        Log::error('REFUND_MEDIA_UPLOAD_FAILED', [
                            'refund_id' => $refund->id,
                            'field'     => $field,
                            'message'   => $mediaEx->getMessage(),
                        ]);
                    }
                }
            }

            // Booking update
            $oldStatus = $booking->status;

            $booking->update([
                'status'              => 4,
                'refund_request_date' => now(),
            ]);

            Log::notice('REFUND_BOOKING_STATUS_UPDATED', [
                'booking_id' => $booking->id,
                'old_status' => $oldStatus,
                'new_status' => $booking->status,
                'refund_id'  => $refund->id,
                'user_id'    => $userId,
            ]);

            Log::info('REFUND_REQUEST_COMPLETED_SUCCESS', [
                'booking_id' => $booking->id,
                'refund_id'  => $refund->id,
            ]);

            return redirect()->route('booking.index')
                ->with('success', 'Refund request submitted successfully for Booking #' . $booking->id);
        } catch (\Illuminate\Database\QueryException $dbEx) {
            Log::critical('REFUND_DATABASE_ERROR', [
                'booking_id' => $id,
                'sql_error'  => $dbEx->getMessage(),
                'sql_code'   => $dbEx->getCode(),
                'input'      => $request->except(['_token', 'acc_proof', 'aadhar', 'pan']),
            ]);
            return redirect()->back()
                ->with('error', 'Database error while processing refund.')
                ->withInput();
        } catch (\Exception $e) {
            Log::error('REFUND_PROCESS_UNEXPECTED_ERROR', [
                'booking_id' => $id,
                'message'    => $e->getMessage(),
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
                'trace'      => $e->getTraceAsString(),
                'input'      => $request->except(['_token', 'acc_proof', 'aadhar', 'pan']),
            ]);

            return redirect()->back()
                ->with('error', 'Something went wrong while processing refund.')
                ->withInput();
        }
    }


    public function statusave(Request $request, $id)
    {
        // 1. Start of function - request data log karo (debug ke liye)
        \Log::debug('statusave called', [
            'booking_id' => $id,
            'request_all' => $request->all(),
            'ip' => $request->ip(),
        ]);

        try {
            $booking = Booking::findOrFail($id);
            \Log::info('Booking found', ['id' => $id, 'current_status' => $booking->status]);

            $oldStatus = $booking->status;
            $newStatus = $request->input('status');

            \Log::info('Status change requested', [
                'old' => $oldStatus,
                'new' => $newStatus,
                'remark' => $request->input('remark'),
            ]);



            $statusNames = [
                1 => 'Live',
                2 => 'Invoiced',
                3 => 'Cancelled',
                4 => 'Refund Queued',
                5 => 'Refunded',
                6 => 'On Hold',
                7 => 'Refund Rejected',
                8 => 'Pending',
            ];

            $oldName = $statusNames[$oldStatus] ?? 'Unknown';
            $newName = $statusNames[$newStatus] ?? 'Unknown';

            $statusRemark = ($oldStatus != $newStatus)
                ? "Booking status changed from {$oldName} to {$newName}"
                : null;

            $adminRemark = $request->input('remark', 'Restored from cancelled');

            \Log::info('Preparing followup log', [
                'status_remark' => $statusRemark,
                'admin_remark' => $adminRemark,
            ]);

            if ($statusRemark || $adminRemark) {
                $commid = ChatHelper::get_commid(3, $booking->id, "Booking Created");
                \Log::debug('Comm ID fetched', ['commid' => $commid]);

                ChatHelper::add_followup(
                    $commid,
                    $statusRemark ?: $adminRemark,
                    $statusRemark ? $adminRemark : null,
                    null,
                    1
                );
                \Log::info('Followup added successfully');
            }

            $booking->update([
                'status' => $newStatus,
                'refund_request_date' => null,
            ]);
            \Log::info('Booking updated successfully', ['new_status' => $newStatus]);

            return redirect()->route('booking.index')
                ->with('success', 'Booking successfully restored!');
        } catch (\Exception $e) {
            \Log::error('Error in statusave', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'booking_id' => $id,
            ]);

            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function receiptEdit($bookingId, $receiptId)
    {
        $this->crud->hasAccessOrFail('update');  // या अगर permission अलग है तो वो चेक कर लो

        $receipt = \App\Models\BookingAmount::findOrFail($receiptId);

        // Security check
        if ($receipt->bid != $bookingId) {
            abort(403, 'This receipt does not belong to the specified booking.');
        }

        $booking = $this->crud->getEntry($bookingId);

        $this->data['entry']      = $receipt;
        $this->data['crud']       = $this->crud;
        $this->data['saveAction'] = $this->crud->getSaveAction();  // backpack के save options
        $this->data['title']      = 'Edit Receipt #' . ($receipt->reciept ?? $receiptId);
        $this->data['booking']    = $booking;
        $this->data['booking_id'] = $bookingId;
        $this->data['receipt_id'] = $receiptId;

        // Backpack का layout इस्तेमाल करने के लिए data तैयार
        return view('booking.recedit', $this->data);
    }


    public function receiptUpdate(Request $request, $bookingId, $receiptId)
    {
        $receipt = \App\Models\BookingAmount::findOrFail($receiptId);

        if ($receipt->bid != $bookingId) {
            abort(403, 'Receipt does not belong to this booking.');
        }

        if ($request->has('action') && $request->action === 'delete') {
            // Step 1: Booking ढूंढो
            $booking = \App\Models\Booking::findOrFail($bookingId);

            // Step 2: Receipt की amount को booking_amount से घटाओ
            $booking->booking_amount = max(0, $booking->booking_amount - $receipt->amount);
            // max(0, ...) → negative न हो जाए कभी

            $booking->save();  // booking update करो

            // Step 3: Media delete + receipt delete
            $receipt->clearMediaCollection('amount-proof');
            $receipt->delete();

            \Alert::warning('Receipt deleted and amount deducted from booking successfully.')->flash();

            // Redirect back to booking show page
            return redirect(backpack_url("booking/{$bookingId}"));
            // या named route: return redirect()->route('booking.show', $bookingId);
        }

        // Normal update logic (ये वही रहेगा)
        $validated = $request->validate([
            'reciept'      => 'required|string|max:100',
            'date'         => 'required|date_format:d-M-Y',
            'amount'       => 'required|numeric|min:0',
            'amount_proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $receipt->reciept = $request->reciept;
        $receipt->date    = \Carbon\Carbon::createFromFormat('d-M-Y', $request->date)->format('Y-m-d');
        $receipt->amount  = $request->amount;

        if ($request->hasFile('amount_proof') && $request->file('amount_proof')->isValid()) {
            $receipt->clearMediaCollection('amount-proof');
            $receipt->addMediaFromRequest('amount_proof')->toMediaCollection('amount-proof');
        }

        $receipt->save();

        \Alert::success('Receipt updated successfully.')->flash();

        return redirect()->back();
    }
    /**
     * Display Dealer Invoice Form
     */
    public function dealerInvoice($id)
    {
        $this->crud->hasAccessOrFail('list'); // or create appropriate permission

        $booking = Booking::findOrFail($id);

        // Check if booking is eligible for dealer invoice
        if ($booking->dealer_status != 1) {
            return redirect()->back()->with('error', 'This booking is not pending for dealer invoice.');
        }

        $this->data['crud'] = $this->crud;
        $this->data['title'] = 'Dealer Invoice Details - Booking #' . $booking->id;
        $this->data['booking'] = $booking;
        $this->data['saveAction'] = backpack_url("booking/{$id}/dealer-invoice");

        // Get common lookups if needed
        $this->data['lookups'] = $this->getCommonLookups();

        return view('booking.dealer-edit', $this->data);
    }

    /**
     * Update Dealer Invoice Details
     */
    public function dealerInvoiceUpdate(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);

        // Authorization check
        $this->crud->hasAccessOrFail('update'); // or create appropriate permission

        // Check if booking is eligible
        if ($booking->dealer_status != 1) {
            return redirect()->back()->with('error', 'This booking is not pending for dealer invoice.')->withInput();
        }

        // Validation
        $validator = Validator::make($request->all(), [
            'dms_invoice_number' => 'required|string|regex:/^INV\d{2}[A-Z]\d{6}$/',
            'dms_invoice_date' => 'required|date|before_or_equal:today',
            'hidden_dealer_invoice_number' => 'nullable|string',
            'hidden_dealer_inv_date' => 'nullable|date',
        ], [
            'dms_invoice_number.required' => 'DMS Invoice Number is required.',
            'dms_invoice_number.regex' => 'DMS Invoice Number must be in format INV00A123456.',
            'dms_invoice_date.required' => 'DMS Invoice Date is required.',
            'dms_invoice_date.before_or_equal' => 'DMS Invoice Date cannot be in the future.',
        ]);

        if ($validator->fails()) {
            Log::warning('Dealer Invoice Validation Failed', [
                'booking_id' => $id,
                'errors' => $validator->errors()->toArray()
            ]);

            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Update booking with DMS invoice details
            $booking->inv_no = $request->input('dms_invoice_number');
            $booking->inv_date = $request->input('dms_invoice_date');
            $booking->dealer_status = 2; // Mark as invoiced

            // Keep existing dealer invoice details if they exist
            if ($request->filled('hidden_dealer_invoice_number')) {
                $booking->dealer_inv_no = $request->input('hidden_dealer_invoice_number');
            }
            if ($request->filled('hidden_dealer_inv_date')) {
                $booking->dealer_inv_date = $request->input('hidden_dealer_inv_date');
            }

            // Log before save
            Log::info('Dealer Invoice Update - Before Save', [
                'booking_id' => $id,
                'inv_no' => $booking->inv_no,
                'inv_date' => $booking->inv_date,
                'dealer_status' => $booking->dealer_status,
                'dealer_inv_no' => $booking->dealer_inv_no,
                'dealer_inv_date' => $booking->dealer_inv_date,
            ]);

            $booking->save();

            // Add ChatHelper follow-up
            try {
                $commid = ChatHelper::get_commid(3, $booking->id, "Booking Created");
                ChatHelper::add_followup(
                    $commid,
                    "DMS Invoice details updated successfully. Invoice No: " . $booking->inv_no,
                    backpack_user()->name . " has updated DMS invoice details",
                    null,
                    1
                );
                Log::info('ChatHelper follow-up added for dealer invoice', ['booking_id' => $id]);
            } catch (\Exception $e) {
                Log::error('ChatHelper failed for dealer invoice', [
                    'booking_id' => $id,
                    'error' => $e->getMessage()
                ]);
            }

            Log::info('Dealer Invoice Updated Successfully', [
                'booking_id' => $id,
                'inv_no' => $booking->inv_no,
                'status' => 'success'
            ]);

            return redirect()->route('booking.pending-invoices')
                ->with('success', 'Dealer invoice details updated successfully for Booking #' . $booking->id);
        } catch (\Exception $e) {
            Log::error('Dealer Invoice Update Failed', [
                'booking_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'input' => $request->except(['_token'])
            ]);

            return redirect()->back()
                ->with('error', 'Failed to update dealer invoice details. Please try again.')
                ->withInput();
        }
    }

    public function insEdit($id)
    {
        $booking = Booking::findOrFail($id);

        // Existing insurance record (if any)
        $insurance = XlInsurer::where('bid', $id)->first();  // adjust table/model name if needed

        $data = [];

        $data['segments']      = XpricingHelper::getSegments() ?? [];
        $data['models']        = XpricingHelper::getModelsX() ?? [];
        $data['variants']      = XpricingHelper::getVehiclesX() ?? [];
        $data['colors']        = XpricingHelper::getColorX() ?? [];
        $data['branch']        = optional(X_Branch::find($booking->branch_code))->name ?? 'N/A';
        $data['location']      = $booking->location_code
            ? optional(X_Location::find($booking->location_code))->name
            : ($booking->location_other ?? 'N/A');
        $data['insurances']    = XlInsurance::select('id', 'name', 'short_name')->get()->toArray();
        $data['allusers']      = XpricingHelper::selectUsers() ?? [];
        $data['fbranch']       = $data['branch']; // duplicate if needed
        $data['flocation']     = $data['location'];

        // Chassis related
        $stock = Stock::find($booking->chasis_no);
        if ($stock) {
            $data['bchasis'] = $stock->chasis_no;
            $data['chassis'] = Stock::where('model_code', $stock->model_code)
                ->select('chasis_no', 'id')
                ->get()
                ->toArray();
        } else {
            $data['bchasis'] = 'Not Available';
            $data['chassis'] = [];
        }

        // DSA / Collector
        $dsaRecords = Xl_Dsa_Master::all();
        $data['dsa_details'] = $dsaRecords->map(fn($dsa) => [
            'id'       => $dsa->id,
            'name'     => $dsa->name,
            'mobile'   => $dsa->mobile,
            'email'    => $dsa->email,
            'location' => $dsa->dlocation,
        ])->toArray();

        $collector = User::find($booking->col_by);
        $data['collector_name'] = $collector
            ? $collector->name . ' - (' . ($collector->emp_code ?? 'N/A') . ')'
            : 'N/A';

        $drec = Xl_Dsa_Master::find($booking->dsa_id);
        $dsaname = $drec ? $drec->name . ' - ' . $drec->mobile : 'N/A';

        $data['saleconsultants'] = XpricingHelper::selectfsc() ?? [];

        $data['make1'] = Commonhelper::enumValueById($booking->exist_oem1) ?? 'N/A';
        $data['make2'] = Commonhelper::enumValueById($booking->exist_oem2) ?? 'N/A';

        $data['insurers'] = $data['insurances']; // alias if needed

        $uid = backpack_auth()->id();

        return view('booking.insurance-edit', compact(
            'booking',
            'insurance',
            'data',
            'dsaname',
            'uid'
        ));
    }

    public function insUpdate(Request $request, $id)
    {
        // Step 1: Request received
        Log::info('insUpdate called', [
            'booking_id' => $request->booking_id ?? 'missing',
            'user_id'    => backpack_auth()->id() ?? 'guest',
            'ip'         => $request->ip(),
            'all_input'  => $request->except(['policy_copy']), // file को log मत करो
        ]);

        try {
            // Step 2: Validation
            $validated = $request->validate([
                'booking_id'          => 'required',
                'insurance_category'  => 'required|integer|in:1,2,3',
                'insurance_company'   => 'required|integer',
                'policy_no'           => 'required|string|min:10|max:20|regex:/^[A-Z0-9]+$/i',
                'hidden_policy_date'  => 'required|date_format:Y-m-d',
                'policy_type'         => 'required|integer|in:1,2,3,4',
                'policy_copy'         => 'nullable|file|mimes:pdf|max:5120',
            ]);

            Log::info('Validation passed successfully', [
                'booking_id' => $request->booking_id,
                'policy_no'  => $request->policy_no,
            ]);

            // Step 3: Prepare data
            $data = [
                'bid'         => $request->booking_id,
                'source'      => $request->insurance_category,
                'insurer'     => $request->insurance_company,
                'pol_no'      => strtoupper($request->policy_no),
                'pol_date'    => $request->hidden_policy_date,
                'pol_type'    => $request->policy_type,
                'status'      => 1,
                'updated_by'  => backpack_auth()->id() ?? 1,
            ];

            $allFieldsFilled = $request->filled([
                'booking_id',
                'insurance_category',
                'insurance_company',
                'policy_no',
                'hidden_policy_date',
                'policy_type',
            ]) && $request->hasFile('policy_copy');

            if ($allFieldsFilled) {
                $data['status'] = 2;
                Log::info('All required fields filled + file uploaded → status set to 2');
            } else {
                Log::info('Status remains 1 - missing some required field or file'); // optional: missing keys log कर सकते हो

            }

            // Step 4: Update or Create record
            Log::info('Attempting to update/create insurance record', ['bid' => $request->booking_id]);

            $insurance = XlInsurer::updateOrCreate(
                ['bid' => $request->booking_id],
                $data
            );

            Log::info('Insurance record saved/updated', [
                'insurance_id' => $insurance->id,
                'bid'          => $insurance->bid,
                'status'       => $insurance->status,
            ]);

            // Step 5: File upload handling
            if ($request->hasFile('policy_copy') && $request->file('policy_copy')->isValid()) {
                Log::info('Policy copy file detected', [
                    'original_name' => $request->file('policy_copy')->getClientOriginalName(),
                    'size'          => $request->file('policy_copy')->getSize() . ' bytes',
                ]);

                $insurance->clearMediaCollection('policy_copy');
                Log::info('Cleared old policy_copy media collection');

                $insurance->addMediaFromRequest('policy_copy')
                    ->usingFileName("policy_{$request->booking_id}_" . time() . ".pdf")
                    ->toMediaCollection('policy_copy');

                Log::info('New policy copy file uploaded successfully');
            } else {
                Log::info('No valid policy_copy file uploaded or file invalid');
            }

            // Success
            Log::info('insUpdate completed successfully', ['booking_id' => $request->booking_id]);

            return redirect()->route('booking.pending-insurance')
                ->with('success', 'Insurance details saved successfully for Booking #' . $request->booking_id);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation failed in insUpdate', [
                'booking_id' => $request->booking_id ?? 'unknown',
                'errors'     => $e->errors(),
            ]);

            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Critical error in insUpdate', [
                'booking_id' => $request->booking_id ?? 'unknown',
                'message'    => $e->getMessage(),
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
                'trace'      => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to save insurance data. Please check logs or try again.')
                ->withInput();
        }
    }


    public function rtoEdit($id)
    {
        $booking = Booking::findOrFail($id);

        // RTO record (if exists)
        $rto = XlRto::where('bid', $id)->first();

        $data = [];

        // तुम्हारे पुराने कोड से copy-paste (सिर्फ जरूरी fields रखे हैं)
        $data['segments']     = XpricingHelper::getSegments() ?? [];
        $data['models']       = XpricingHelper::getModelsX() ?? [];
        $data['variants']     = XpricingHelper::getVehiclesX() ?? [];
        $data['colors']       = XpricingHelper::getColorX() ?? [];
        $data['branch']       = optional(X_Branch::find($booking->branch_code))->name ?? 'N/A';
        $data['location']     = $booking->location_code
            ? optional(X_Location::find($booking->location_code))->name
            : ($booking->location_other ?? 'N/A');
        $data['rto_rules']    = XlRtoRules::select('sale_type', 'permit', 'body_type', 'reg_no_type', 'trc_number', 'trc_pay', 'trc_copy', 'app_no', 'tax_pay', 'veh_reg', 'tax_copy')
            ->get()
            ->toArray();
        $data['allusers']     = XpricingHelper::selectUsers() ?? [];
        $data['fbranch']      = $data['branch'];
        $data['flocation']    = $data['location'];

        // Chassis logic
        $cr = Stock::find($booking->chasis_no);
        if ($cr) {
            $data['bchasis'] = $cr->chasis_no;
            $data['chassis'] = Stock::where('model_code', $cr->model_code)
                ->select('chasis_no', 'id')
                ->get()
                ->toArray();
        } else {
            $data['bchasis'] = 'Not Available';
            $data['chassis'] = [];
        }

        // DSA details
        $dsaRecords = Xl_Dsa_Master::all();
        $data['dsa_details'] = $dsaRecords->map(fn($dsa) => [
            'id'       => $dsa->id,
            'name'     => $dsa->name,
            'mobile'   => $dsa->mobile,
            'email'    => $dsa->email,
            'location' => $dsa->dlocation,
        ])->toArray();

        $collector = User::find($booking->col_by);
        $data['collector_name'] = $collector
            ? $collector->name . ' - (' . ($collector->emp_code ?? 'N/A') . ')'
            : 'N/A';

        $drec = Xl_Dsa_Master::find($booking->dsa_id);
        $dsaname = $drec ? $drec->name . '-' . $drec->mobile : 'N/A';

        $data['saleconsultants'] = XpricingHelper::selectfsc() ?? [];

        $data['make1'] = Commonhelper::enumValueById($booking->exist_oem1) ?? 'N/A';
        $data['make2'] = Commonhelper::enumValueById($booking->exist_oem2) ?? 'N/A';

        $uid = backpack_auth()->id();

        return view('booking.rto-edit', compact('booking', 'rto', 'data', 'dsaname', 'uid'));
    }

    public function rtoUpdate(Request $request, $id)
    {
        // Validation
        $validated = $request->validate([
            'trade_used'           => 'required|in:1,2,3,4,5,6',
            'sale_type'            => 'required|in:1,2',
            'permit'               => 'required|in:1,2,3,4,5,6,7,8,9,10,11',
            'body_type'            => 'required|in:1,2',
            'registration_type'    => 'required|in:1,2,3',
            'reg_no_type'          => 'required|in:1,2,3',
            'trc_number'           => 'nullable|string|max:15|regex:/^[A-Z0-9]{10,15}$/',
            'bank_ref_no'          => 'nullable|string|max:20|regex:/^[A-Z0-9]{10,20}$/',
            'trc_copy'             => 'nullable|file|mimes:pdf|max:5120',
            'application_no'       => 'nullable|string|max:15|regex:/^[A-Z0-9]{10,15}$/',
            'tax_payment_ref_no'   => 'nullable|string|max:20|regex:/^[A-Z0-9]{10,20}$/',
            'vehicle_reg_no'       => 'nullable|string',
            'tax_receipt_copy'     => 'nullable|file|mimes:pdf|max:5120',
        ]);

        try {
            // Fetch all rto_rules once
            $rto_rules = XlRtoRules::select(
                'sale_type',
                'permit',
                'body_type',
                'reg_no_type',
                'trc_number',
                'trc_pay',
                'trc_copy',
                'app_no',
                'tax_pay',
                'veh_reg',
                'tax_copy'
            )->get()->toArray();

            // Text mapping for form values → rule matching
            $saleTypeMap = [
                '1' => 'Within State',
                '2' => 'Outside State',
            ];

            $permitMap = [
                '1'  => 'Private - U/C (4 Wheeler)',
                '2'  => 'Private - BH (4 Wheeler)',
                '3'  => 'Private - EV (4 Wheeler)',
                '4'  => 'Goods - G (4 Wheeler)',
                '5'  => 'Goods - G 3 Ton+ (4 Wheeler)',
                '6'  => 'Goods - G (3 Wheeler)',
                '7'  => 'Goods - G EV (3 Wheeler)',
                '8'  => 'Taxi - T (4 Wheeler)',
                '9'  => 'Passenger - P (3 Wheeler)',
                '10' => 'Passenger - P EV (3 Wheeler)',
                '11' => 'Ambulance (Misc.)',
            ];

            $bodyTypeMap = [
                '1' => 'Complete',
                '2' => 'CBC',
            ];

            $regNoTypeMap = [
                '1' => 'Regular',
                '2' => 'BH',
                '3' => 'Special',
            ];

            // Get text values from numeric inputs
            $saleText       = $saleTypeMap[$request->sale_type]       ?? '';
            $permitText     = $permitMap[$request->permit]           ?? '';
            $bodyText       = $bodyTypeMap[$request->body_type]      ?? '';
            $regNoTypeText  = $regNoTypeMap[$request->reg_no_type]   ?? '';

            // Find matching rule
            $matchingRule = null;
            foreach ($rto_rules as $rule) {
                if (
                    $rule['sale_type']    === $saleText &&
                    $rule['permit']       === $permitText &&
                    $rule['body_type']    === $bodyText &&
                    $rule['reg_no_type']  === $regNoTypeText
                ) {
                    $matchingRule = $rule;
                    break;
                }
            }

            // Required fields check based on rule
            $allRequiredFilled = true;

            if ($matchingRule) {
                $fieldMap = [
                    'trc_number'         => 'trc_number',
                    'bank_ref_no'        => 'trc_pay',
                    'trc_copy'           => 'trc_copy',
                    'application_no'     => 'app_no',
                    'tax_payment_ref_no' => 'tax_pay',
                    'vehicle_reg_no'     => 'veh_reg',
                    'tax_receipt_copy'   => 'tax_copy',
                ];

                foreach ($fieldMap as $formField => $ruleKey) {
                    if ($matchingRule[$ruleKey] === 'Yes') {
                        if (in_array($formField, ['trc_copy', 'tax_receipt_copy'])) {
                            // File required
                            if (!$request->hasFile($formField) || !$request->file($formField)->isValid()) {
                                $allRequiredFilled = false;
                                break;
                            }
                        } else {
                            // Text field required
                            if (!$request->filled($formField)) {
                                $allRequiredFilled = false;
                                break;
                            }
                        }
                    }
                }
            } else {
                // No matching rule → incomplete
                $allRequiredFilled = false;
            }

            // Prepare data for DB
            $data = [
                'bid'                      => $id,
                'trade_used'               => $request->trade_used,
                'sale_type'                => $request->sale_type,
                'permit'                   => $request->permit,
                'body_type'                => $request->body_type,
                'rgn_type'                 => $request->registration_type,
                'rgn_no_type'              => $request->reg_no_type,
                'trc_no'                   => $request->trc_number,
                'trc_payment_no'           => $request->bank_ref_no,
                'app_no'                   => $request->application_no,
                'tax_payment_bank_ref_no'  => $request->tax_payment_ref_no,
                'vh_rgn_no'                => $request->vehicle_reg_no,
                'status'                   => $allRequiredFilled ? 2 : 1,
                'updated_by'               => backpack_auth()->id() ?? 1,
            ];

            // Update or Create
            $rto = XlRto::updateOrCreate(
                ['bid' => $id],
                $data
            );

            // Handle file uploads
            if ($request->hasFile('trc_copy') && $request->file('trc_copy')->isValid()) {
                $rto->clearMediaCollection('trc_copy');
                $rto->addMediaFromRequest('trc_copy')
                    ->toMediaCollection('trc_copy');
            }

            if ($request->hasFile('tax_receipt_copy') && $request->file('tax_receipt_copy')->isValid()) {
                $rto->clearMediaCollection('tax_receipt_copy');
                $rto->addMediaFromRequest('tax_receipt_copy')
                    ->toMediaCollection('tax_receipt_copy');
            }

            // Success
            return redirect()
                ->route('booking.pending-rto')  // या जो भी pending list route है
                ->with('success', 'RTO data saved successfully for Booking #' . $id);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Validation fail → back with errors
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
            \Log::error('RTO Update Failed', [
                'booking_id' => $id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to save RTO data. Please try again or contact support.')
                ->withInput();
        }
    }

    public function PendDeliveryEdit($id)
    {
        $booking = Booking::findOrFail($id);

        // Prepare data similar to other views
        $segments = EnumMaster::where('val_type', 'segment')->get()->mapWithKeys(function ($item) {
            return [$item->id => ['name' => $item->value]];
        });

        // Assuming sales consultants are users; removed invalid 'role' filter
        $saleconsultants = User::get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'emp_code' => $user->emp_code ?? 'N/A',
                ];
            })
            ->toArray();

        $branch = X_Branch::find($booking->branch_code)->name ?? 'N/A';
        $location = X_Location::find($booking->location_code)->name ?? $booking->location_other ?? 'N/A';
        $financier = XlFinancier::find($booking->financier)->name ?? 'N/A'; // Assuming financier is ID
        $bchasis = $booking->chasis_no ?? 'N/A'; // Note: Typo in variable name, consistent with blade

        $data = [
            'segments' => $segments,
            'saleconsultants' => $saleconsultants,
            'branch' => $branch,
            'location' => $location,
            'financier' => $financier,
            'bchasis' => $bchasis,
        ];

        return view('booking.delivery-edit', compact('booking', 'data'));
    }

    public function PendDeliveryUpdate(Request $request, $id)
    {
        \Log::debug('PendDeliveryUpdate started', [
            'booking_id' => $id,
            'user_id'    => backpack_auth()->id() ?? 'unknown',
            'ip'         => $request->ip(),
        ]);

        // Log full request (without huge file contents)
        \Log::debug('Request input (non-file)', $request->except(['photos']));

        // Safe files logging
        $filesInfo = [];
        $photos = $request->file('photos') ?? [];
        foreach ($photos as $key => $file) {
            if ($file instanceof \Illuminate\Http\UploadedFile) {
                $filesInfo[$key] = [
                    'original_name' => $file->getClientOriginalName(),
                    'size_kb'       => round($file->getSize() / 1024, 2),
                    'mime'          => $file->getMimeType(),
                    'error'         => $file->getError(),
                ];
            } else {
                $filesInfo[$key] = 'invalid-file-object';
            }
        }
        \Log::debug('Uploaded photos (nested structure)', $filesInfo);

        // ────────────────────────────────────────────────
        // VALIDATION - updated for nested photos.*
        // ────────────────────────────────────────────────
        $rules = [
            'remarks'                          => 'required|string|max:1000',
            'photos.delivery_ceremony_with_customer'  => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'photos.bonnet'                           => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'photos.windshield_glass'                 => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'photos.vehicle_driver_side'              => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'photos.vehicle_co_driver_side'           => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'photos.vehicle_rear_side'                => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'photos.tire_front_driver_side'           => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'photos.tire_front_co_driver_side'        => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'photos.tire_rear_driver_side'            => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'photos.tire_rear_co_driver_side'         => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'photos.stepney'                          => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'photos.foot_rest_driver_side'            => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'photos.foot_rest_co_driver_side'         => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'photos.tool_kit'                         => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'photos.vehicle_chassis_no_photo'         => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'photos.chassis_no_screenshot_invoice'    => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'photos.chassis_no_screenshot_insurance'  => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'chassis_no_verified'                     => 'nullable|boolean',   // or 'nullable|in:on'
        ];

        try {
            $validated = $request->validate($rules);
            \Log::info('Validation passed', ['booking_id' => $id]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::warning('Validation failed', [
                'booking_id' => $id,
                'errors'     => $e->errors(),
            ]);
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        }

        try {
            // ────────────────────────────────────────────────
            // Save / Update delivery record
            // ────────────────────────────────────────────────
            $delivery = XlDelivery::updateOrCreate(
                ['bid' => $id],
                [
                    'remarks'      => $request->remarks,
                    'verification' => $request->boolean('chassis_no_verified', false),
                    'status'       => 1,
                    'created_by'   => backpack_auth()->id() ?? 1,
                    'updated_by'   => backpack_auth()->id() ?? 1,
                ]
            );

            \Log::info('Delivery record updated/created', [
                'delivery_id' => $delivery->id,
                'bid'         => $id,
            ]);

            // ────────────────────────────────────────────────
            // Media collections
            // ────────────────────────────────────────────────
            $collections = [
                'delivery_ceremony_with_customer',
                'bonnet',
                'windshield_glass',
                'vehicle_driver_side',
                'vehicle_co_driver_side',
                'vehicle_rear_side',
                'tire_front_driver_side',
                'tire_front_co_driver_side',
                'tire_rear_driver_side',
                'tire_rear_co_driver_side',
                'stepney',
                'foot_rest_driver_side',
                'foot_rest_co_driver_side',
                'tool_kit',
                'vehicle_chassis_no_photo',
                'chassis_no_screenshot_invoice',
                'chassis_no_screenshot_insurance',
            ];

            foreach ($collections as $collection) {
                $photoKey = "photos.{$collection}";

                if ($request->hasFile($photoKey) && $request->file($photoKey)->isValid()) {
                    $file = $request->file($photoKey);

                    \Log::info("Processing photo: {$collection}", [
                        'original_name' => $file->getClientOriginalName(),
                        'size_kb'       => round($file->getSize() / 1024, 2),
                    ]);

                    // Clear old media
                    $delivery->clearMediaCollection($collection);
                    \Log::debug("Cleared old media: {$collection}");

                    // Add new media
                    $media = $delivery->addMedia($file)
                        ->toMediaCollection($collection, 'public');

                    \Log::info("Media added", [
                        'collection' => $collection,
                        'media_id'   => $media->id,
                        'filename'   => $media->file_name,
                    ]);
                } else {
                    \Log::debug("No valid file for: {$collection} (key: {$photoKey})");
                }
            }

            return redirect()
                ->route('booking.pending-deliveries')
                ->with('success', 'Delivery updated successfully with photos! Booking #' . $id);
        } catch (\Spatie\MediaLibrary\MediaCollections\Exceptions\FileCannotBeAdded $e) {
            \Log::error('Media upload error', [
                'booking_id' => $id,
                'message'    => $e->getMessage(),
            ]);
            return redirect()->back()
                ->with('error', 'Photo upload failed: ' . $e->getMessage())
                ->withInput();
        } catch (\Exception $e) {
            \Log::critical('PendDeliveryUpdate failed', [
                'booking_id' => $id,
                'message'    => $e->getMessage(),
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
                'trace'      => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'Something went wrong while saving delivery. Check logs.')
                ->withInput();
        }
    }

    public function exchangeEdit($id)
    {
        $booking = Booking::findOrFail($id);
        $exchange = XExchange::where('bid', $id)->first(); // Fetch XExchange entry if it exists
        $data = array();
        $comm = ChatHelper::get_communication(3, $id);

        // FIXED: Use Backpack's auth helper
        $uid = backpack_user()->id ?? null;  // Safe fallback if no user (though middleware should prevent this)

        $data['branch'] = X_Branch::find($booking->branch_code)->name ?? 'N/A';
        $data['location'] = ($booking->location_code > 0) ? X_Location::find($booking->location_code)->name : $booking->location_other;
        $acc = explode(',', $booking->accessories);
        foreach ($acc as $a) {
            $accessory = Xessories::find($a);
            $tmp = array();
            if ($accessory) {
                $temp[] = $accessory->item;
            }
            if (!empty($temp))
                $data['accessories'] = implode(",", $temp);
            else
                $data['accessories'] = "N/A";
        }
        $chassis = Stock::find($booking->chasis_no);
        $data['bchasis'] = $chassis ? $chassis->chasis_no : 'N/A';
        $data['segments'] = XpricingHelper::getSegments();
        $data['remark'] = 0;
        $data['saleconsultants'] = XpricingHelper::selectfsc();
        $drec = Xl_DSA_Master::find($booking->dsa_id);
        $dsaname = $drec ? $drec->name . '-' . $drec->mobile : "N/A";
        $user = backpack_user();  // Use Backpack helper here too for consistency
        $collector = User::find($booking->col_by);
        if ($collector) {
            $data['collector_name'] = $collector->name . ' - ' . $collector->emp_code;
        } else {
            $data['collector_name'] = 'N/A';
        }
        $depts = explode(",", $user->department);
        foreach ($depts as $dept) {
            if (commonhelper::enumValueById($dept) == "SALES")
                $data['remark'] = 1;
            if (commonhelper::enumValueById($dept) == "ACCOUNTS")
                $data['remark'] = 2;
        }
        $data['make1'] = Commonhelper::enumValueById($booking->exist_oem1) ?? 'N/A';
        $data['make2'] = Commonhelper::enumValueById($booking->exist_oem2) ?? 'N/A';
        $enumMasterRecords = EnumMaster::where('master_id', 94)->select('id', 'value')->get();
        $data['enum_master'] = $enumMasterRecords;
        $enumMasterIds = explode(',', $booking->exist_oem);
        $data['oem_ids'] = $enumMasterIds;
        // Pass both booking and exchange data to the view
        return view('booking.exch-edit', compact('booking', 'exchange', 'comm', 'data', 'dsaname', 'uid'));
    }

    public function exchangeUpdate(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);
        // Validation rules
        $validator = Validator::make($request->all(), [
            'buyer_type' => 'required|string|in:First time Buyer,Additional Buy,Exchange Buy,Scrappage',
            'enum_master1' => 'nullable|integer',
            'vehicle_details' => 'nullable|string|max:255',
            'enum_master2' => 'nullable|integer',
            'vehicle_details2' => 'nullable|string|max:255',
            'registration_no' => 'nullable|string|max:255',
            'manufacturing_year' => 'nullable|integer',
            'odometer_reading' => 'nullable|string|max:255',
            'expected_price' => 'nullable|numeric',
            'offered_price' => 'nullable|numeric',
            'exchange_bonus' => 'nullable|numeric',
            'update' => 'required|integer|in:1,2,3', // Verification Status
            'case_status' => 'required|integer|in:1,2,3', // Case Status
            'remark' => 'required|string',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withInput()->with('error', $validator->messages()->first());
        }
        $rem = []; // Array to store remarks about changes
        // Define status mappings for remarks
        $verificationStatusMap = [
            1 => 'Unverified',
            2 => 'Verified (Data Match)',
            3 => 'Verified (Data Mismatch)',
        ];
        $caseStatusMap = [
            1 => 'In-Process',
            2 => 'Exchange Done',
            3 => 'Case Lost',
        ];
        // Update Booking fields and log changes
        if ($booking->buyer_type != $request->buyer_type) {
            $tvl = empty($booking->buyer_type) ? 'null' : $booking->buyer_type;
            $rem[] = "Buyer Type Changed from " . $tvl . " to " . $request->buyer_type;
            $booking->buyer_type = $request->input('buyer_type');
        }
        if ($booking->exist_oem1 != $request->enum_master1) {
            $tvl = empty($booking->exist_oem1) ? 'null' : $booking->exist_oem1;
            $rem[] = "Brand (Make 1) Changed from " . $tvl . " to " . $request->enum_master1;
            $booking->exist_oem1 = $request->input('enum_master1');
        }
        if ($booking->vh1_detail != $request->vehicle_details) {
            $tvl = empty($booking->vh1_detail) ? 'null' : $booking->vh1_detail;
            $rem[] = "Model & Variant 1 Changed from " . $tvl . " to " . $request->vehicle_details;
            $booking->vh1_detail = $request->input('vehicle_details');
        }
        if ($booking->exist_oem2 != $request->enum_master2) {
            $tvl = empty($booking->exist_oem2) ? 'null' : $booking->exist_oem2;
            $rem[] = "Brand (Make 2) Changed from " . $tvl . " to " . $request->enum_master2;
            $booking->exist_oem2 = $request->input('enum_master2');
        }
        if ($booking->vh2_detail != $request->vehicle_details2) {
            $tvl = empty($booking->vh2_detail) ? 'null' : $booking->vh2_detail;
            $rem[] = "Model & Variant 2 Changed from " . $tvl . " to " . $request->vehicle_details2;
            $booking->vh2_detail = $request->input('vehicle_details2');
        }
        if ($booking->registration_no != $request->registration_no) {
            $tvl = empty($booking->registration_no) ? 'null' : $booking->registration_no;
            $rem[] = "Vehicle Registration No Changed from " . $tvl . " to " . $request->registration_no;
            $booking->registration_no = $request->input('registration_no');
        }
        if ($booking->make_year != $request->manufacturing_year) {
            $tvl = empty($booking->make_year) ? 'null' : $booking->make_year;
            $rem[] = "Manufacturing Year Changed from " . $tvl . " to " . $request->manufacturing_year;
            $booking->make_year = $request->input('manufacturing_year');
        }
        if ($booking->odo_reading != $request->odometer_reading) {
            $tvl = empty($booking->odo_reading) ? 'null' : $booking->odo_reading;
            $rem[] = "Odometer Reading Changed from " . $tvl . " to " . $request->odometer_reading;
            $booking->odo_reading = $request->input('odometer_reading');
        }
        if ($booking->expected_price != $request->expected_price) {
            $tvl = empty($booking->expected_price) ? 'null' : $booking->expected_price;
            $rem[] = "Expected Price Changed from " . $tvl . " to " . $request->expected_price;
            $booking->expected_price = $request->input('expected_price');
        }
        if ($booking->offered_price != $request->offered_price) {
            $tvl = empty($booking->offered_price) ? 'null' : $booking->offered_price;
            $rem[] = "Offered Price Changed from " . $tvl . " to " . $request->offered_price;
            $booking->offered_price = $request->input('offered_price');
        }
        if ($booking->exchange_bonus != $request->exchange_bonus) {
            $tvl = empty($booking->exchange_bonus) ? 'null' : $booking->exchange_bonus;
            $rem[] = "Exchange Bonus Changed from " . $tvl . " to " . $request->exchange_bonus;
            $booking->exchange_bonus = $request->input('exchange_bonus');
        }
        // Handle remarks
        if ($request->has('remark')) {
            $booking->pending_remark = $request->input('remark');
            $rem[] = "Remarks updated: " . $request->input('remark');
        }
        // Save changes to Booking
        $booking->save();
        // Handle XExchange table
        $verificationStatus = $request->input('update');
        $caseStatus = $request->input('case_status');
        $purchaseType = $request->input('buyer_type'); // Get buyer_type for purchase_type column
        // Check if an XExchange entry exists for this booking
        $exchangeEntry = XExchange::where('bid', $booking->id)->first();
        if (!$exchangeEntry) {
            // First submission: Create a new entry with default values if not provided
            $defaultVerificationStatus = $verificationStatus ?? 1; // Default to 1 (Unverified)
            $defaultCaseStatus = $caseStatus ?? 1; // Default to 1 (In-Process)
            $defaultPurchaseType = $purchaseType; // Use buyer_type from form
            XExchange::create([
                'bid' => $booking->id,
                'verification_status' => $defaultVerificationStatus,
                'case_status' => $defaultCaseStatus,
                'purchase_type' => $defaultPurchaseType,
            ]);
            // Add remark for new entry creation (without purchase_type)
            $rem[] = "New exchange entry created with Verification Status: " . $verificationStatusMap[$defaultVerificationStatus] .
                " and Case Status: " . $caseStatusMap[$defaultCaseStatus];
        } else {
            // Subsequent submission: Update the existing entry and log verification/case status changes
            $changes = [];
            if ($exchangeEntry->verification_status != $verificationStatus) {
                $oldVerification = $verificationStatusMap[$exchangeEntry->verification_status] ?? 'null';
                $newVerification = $verificationStatusMap[$verificationStatus];
                $changes[] = "Verification Status changed from " . $oldVerification . " to " . $newVerification;
            }
            if ($exchangeEntry->case_status != $caseStatus) {
                $oldCase = $caseStatusMap[$exchangeEntry->case_status] ?? 'null';
                $newCase = $caseStatusMap[$caseStatus];
                $changes[] = "Case Status changed from " . $oldCase . " to " . $newCase;
            }
            if (!empty($changes)) {
                $rem = array_merge($rem, $changes);
            }
            // Update the entry (including purchase_type, but don't log it separately)
            $exchangeEntry->update([
                'verification_status' => $verificationStatus,
                'case_status' => $caseStatus,
                'purchase_type' => $purchaseType,
            ]);
        }
        // Log changes if any
        if (!empty($rem)) {
            $commid = ChatHelper::get_commid(3, $booking->id, "Booking Created");
            ChatHelper::add_followup($commid, "Purchase Type Edited", implode(" , ", $rem), null, 1);
        }
        return redirect()->route('booking.exchange')->with('success', 'Exchange purchase details updated successfully!');
    }

    public function finEdit($id)
    {
        $booking = Booking::findOrFail($id);

        // Get communication / remarks history
        $comm = ChatHelper::get_communication(3, $id);

        // Backpack authenticated user
        $user = backpack_user();
        $uid  = $user?->id ?? null;

        // Prepare shared $data array
        $data = [];

        $data['branch']   = X_Branch::find($booking->branch_code)?->name ?? 'N/A';
        $data['location'] = $booking->location_code > 0
            ? (X_Location::find($booking->location_code)?->name ?? 'N/A')
            : ($booking->location_other ?? 'N/A');

        // Accessories
        $acc = explode(',', $booking->accessories ?? '');
        $accessoryNames = [];
        foreach ($acc as $a) {
            if ($a = trim($a)) {
                $accessory = Xessories::find($a);
                if ($accessory) {
                    $accessoryNames[] = $accessory->item;
                }
            }
        }
        $data['accessories'] = $accessoryNames ? implode(', ', $accessoryNames) : 'N/A';

        // Chassis
        $chassis = Stock::find($booking->chasis_no);
        $data['bchasis'] = $chassis?->chasis_no ?? 'N/A';

        // Helpers / lookups
        $data['segments']       = XpricingHelper::getSegments();
        $data['saleconsultants'] = XpricingHelper::selectfsc();
        $data['financiers']     = XlFinancier::select('id', 'name', 'short_name')->get()->toArray();
        $data['enum_master']    = EnumMaster::where('master_id', 94)->select('id', 'value')->get();

        // DSA name
        $drec = Xl_DSA_Master::find($booking->dsa_id);
        $dsaname = $drec ? $drec->name . ' - ' . $drec->mobile : 'N/A';

        // Collector name
        $collector = User::find($booking->col_by);
        $data['collector_name'] = $collector
            ? $collector->name . ' - ' . $collector->emp_code
            : 'N/A';

        // Remark permission (SALES / ACCOUNTS)
        $data['remark'] = 0;
        $depts = explode(',', $user->department ?? '');
        foreach ($depts as $deptId) {
            $deptName = commonhelper::enumValueById(trim($deptId));
            if ($deptName === 'SALES')   $data['remark'] = 1;
            if ($deptName === 'ACCOUNTS') $data['remark'] = 2;
        }

        // Make / OEM names
        $data['make1'] = Commonhelper::enumValueById($booking->exist_oem1) ?? 'N/A';
        $data['make2'] = Commonhelper::enumValueById($booking->exist_oem2) ?? 'N/A';

        // OEM IDs array
        $data['oem_ids'] = explode(',', $booking->exist_oem ?? '');

        // Fetch finance record (can be null)
        $finance = XFinance::where('bid', $id)->first();

        return view('booking.finance-edit', compact(
            'booking',
            'finance',
            'comm',
            'data',
            'dsaname',
            'uid',
            'user'
        ));
    }

    public function finUpdate(Request $request, $id)
    {
        // === MINIMAL VALIDATION ===
        $rules = [
            'fin_mode'          => 'required',
            'loan_status'       => 'nullable',
            'case_status'       => 'nullable',
            'instrument_type'   => 'nullable',
            'instrument_ref_no' => 'nullable',
            'loan_amount'       => 'nullable',
            'margin_money'      => 'nullable',
            'file_charge'       => 'nullable',
            'remark'            => 'required',
            'verification_status' => 'required',
            'case_lost_reason'  => 'nullable',
            'instrument_proof'  => 'nullable|file',
            'retail'            => 'nullable|in:1',
            'bid'               => 'required|integer'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return redirect()->back()
                ->withInput()
                ->withErrors($validator)
                ->with('error', $validator->messages()->first());
        }

        $booking = Booking::findOrFail($id);

        // firstOrNew → create if not exists
        $finance = XFinance::firstOrNew(['bid' => $id]);
        $isNew = !$finance->exists;

        // === STORE OLD VALUES FOR LOGGING ===
        $old = $finance->toArray();
        $changes = [];

        $labels = [
            'fin_mode'          => 'Finance Mode',
            'loan_status'       => 'Loan Status',
            'case_status'       => 'Case Status',
            'instrument_type'   => 'Instrument Type',
            'instrument_ref_no' => 'Reference No.',
            'loan_amount'       => 'Loan Amount',
            'margin'            => 'Margin Money',
            'file_charge'       => 'File Charge',
            'case_lost_reason'  => 'Case Lost Reason',
            'verification_status' => 'Verification Status',
            'remark'            => 'Remark',
        ];

        $instrumentTypes = [
            1 => 'Financier Payment',
            2 => 'Delivery Order',
            3 => 'Sanction Letter',
            4 => 'Mail Communication',
            5 => 'Whatsapp Communication'
        ];

        $caseLostReasons = [
            1 => 'Cash Purchase',
            2 => 'Customer Self Finance',
        ];

        $verifyLabels = [
            1 => 'Not Selected',
            2 => 'Verified (Match)',
            3 => 'Verified (Mismatch)',
            4 => 'Plan Cancelled',
        ];

        $format = function ($val, $field) use ($instrumentTypes, $caseLostReasons, $verifyLabels) {
            if (is_null($val)) return 'N/A';
            if ($field === 'instrument_type') return $instrumentTypes[$val] ?? $val;
            if ($field === 'case_lost_reason') return $caseLostReasons[$val] ?? 'Unknown';
            if ($field === 'verification_status') return $verifyLabels[$val] ?? $val;
            if (in_array($field, ['loan_amount', 'margin', 'file_charge'])) {
                return 'Rs. ' . number_format($val);
            }
            return $val;
        };

        $fields = [
            'fin_mode',
            'loan_status',
            'case_status',
            'instrument_type',
            'instrument_ref_no',
            'loan_amount',
            'margin',
            'file_charge',
            'case_lost_reason',
            'verification_status'
        ];

        foreach ($fields as $field) {
            $oldVal = $old[$field] ?? null;
            $inputKey = $field === 'margin' ? 'margin_money' : $field;
            $newVal = $request->input($inputKey);
            $oldVal = $oldVal === '' ? null : $oldVal;
            $newVal = $newVal === '' ? null : $newVal;

            if ($oldVal != $newVal) {
                $label = $labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
                $changes[] = "$label: '{$format($oldVal,$field)}' to '{$format($newVal,$field)}'";
            }
        }

        if ($isNew) {
            $changes[] = "New Finance Record Created for Booking ID {$id}";
        }

        // === HANDLE FILE UPLOAD & DELETION ===
        if ($request->hasFile('instrument_proof')) {
            // Naya file aaya hai → purana hamesha delete kar do (replace behavior)
            $finance->clearMediaCollection('instrument_proof');

            $finance->addMediaFromRequest('instrument_proof')
                ->usingFileName('instrument_proof_' . $id . '_' . time() . '.' . $request->file('instrument_proof')->extension())
                ->toMediaCollection('instrument_proof');

            $changes[] = "Instrument Proof: New file uploaded (replaced previous if any)";
        }

        if ($request->has('delete_instrument_proof') && $request->delete_instrument_proof == '1') {
            $finance->clearMediaCollection('instrument_proof');
            $changes[] = "Instrument Proof: File removed";
        }

        // vh_id set karna (har baar check karo agar missing ho)
        if ($isNew || empty($finance->vh_id)) {
            $finance->vh_id = $booking->vh_id ?? null;

            if (empty($finance->vh_id)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Cannot save finance: Booking is missing vh_id / vehicle head ID.');
            }
        }

        // === UPDATE/CREATE FINANCE MODEL ===
        $finance->fin_mode    = $request->fin_mode;
        $finance->loan_status = $request->loan_status;
        $finance->financier   = $request->financier;

        // Cash / Customer Self → clear finance fields
        if (in_array($request->fin_mode, ['Cash', 'Customer Self'])) {
            $finance->instrument_type   = null;
            $finance->instrument_ref_no = null;
            $finance->loan_amount       = null;
            $finance->margin            = null;
            $finance->file_charge       = null;
            $finance->case_lost_reason  = $request->fin_mode === 'Cash' ? 1 : 2;
            $finance->clearMediaCollection('instrument_proof');
        } else {
            $finance->instrument_type   = $request->instrument_type;
            $finance->instrument_ref_no = $request->instrument_ref_no;
            $finance->loan_amount       = $request->loan_amount;
            $finance->margin            = $request->margin_money;
            $finance->file_charge       = $request->file_charge;
        }

        // ────────────────────────────────────────────────
        // RETAIL STAGE SPECIAL HANDLING (sirf new record par)
        // ────────────────────────────────────────────────
        if ($isNew && $request->retail == 1) {
            $finance->verification_status = 2;  // Verified (Match)
            $finance->case_status         = 2;  // In House Finance Done

            // Optional: Retail mein In-house force karna chahiye to
            // $finance->fin_mode = 'In-house';

            // Agar remark khali hai to default daal do (logging ke liye helpful)
            if (trim($request->remark ?? '') === '') {
                $request->merge(['remark' => 'Retail booking finance auto-completed']);
            }
        }

        // New record ke liye defaults
        if ($isNew) {
            $finance->bid        = $id;
            $finance->created_by = backpack_auth()->id();

            // Agar form se verification_status nahi aaya to bhi default
            if (empty($finance->verification_status)) {
                $finance->verification_status = 2;
            }
        }

        // Common updates
        $finance->updated_by = backpack_auth()->id();
        $finance->status     = ($finance->fin_mode === 'In-house' && $finance->case_status == 2) ? 2 : 1;

        $finance->save();

        // === RETAIL LOGIC ===
        if ($request->retail == 1) {
            $booking->retail = 1;
            $booking->save();
            $changes[] = "Booking Retailed";
        }

        // === PAYOUT LOGIC ===
        if ($request->payout == 1) {
            $booking->payout = 1;
            $booking->save();
        }

        // === FINAL LOG – REMARK AS MAIN MESSAGE ===
        $remark = trim($request->remark ?? '');
        if (!empty($changes) || !empty($remark)) {
            $commid = ChatHelper::get_commid(3, $id, "Booking Created");

            if (!empty($remark)) {
                $mainMessage = $remark;
                if (!empty($changes)) {
                    $mainMessage .= "\n\nChanges: " . implode(' | ', $changes);
                }
            } else {
                $mainMessage = "Finance Updated: " . implode(' | ', $changes);
            }

            ChatHelper::add_followup(
                $commid,
                $mainMessage,
                "Updated by " . backpack_user()->name,
                null,
                1
            );
        }

        // === FINAL REDIRECT LOGIC ===
        $successMessage = 'Finance details updated successfully!';

        if ($request->query('from') === 'payout' || $request->input('from') === 'payout') {
            return redirect()
                ->route('finance.payoutedit', $id)
                ->with('success', $successMessage);
        }

        if ($request->filled('retail') && $request->retail == 1) {
            return redirect()
                ->route('finance.retail')
                ->with('success', $successMessage);
        }

        return redirect()
            ->route('booking.finance')
            ->with('success', $successMessage);
    }

    public function RetailEdit($id)
    {
        $booking = Booking::findOrFail($id);

        // Get communication / remarks history
        $comm = ChatHelper::get_communication(3, $id);

        // Backpack authenticated user
        $user = backpack_user();
        $uid  = $user?->id ?? null;

        // Prepare shared $data array
        $data = [];

        $data['branch']   = X_Branch::find($booking->branch_code)?->name ?? 'N/A';
        $data['location'] = $booking->location_code > 0
            ? (X_Location::find($booking->location_code)?->name ?? 'N/A')
            : ($booking->location_other ?? 'N/A');

        // Accessories
        $acc = explode(',', $booking->accessories ?? '');
        $accessoryNames = [];
        foreach ($acc as $a) {
            if ($a = trim($a)) {
                $accessory = Xessories::find($a);
                if ($accessory) {
                    $accessoryNames[] = $accessory->item;
                }
            }
        }
        $data['accessories'] = $accessoryNames ? implode(', ', $accessoryNames) : 'N/A';

        // Chassis
        $chassis = Stock::find($booking->chasis_no);
        $data['bchasis'] = $chassis?->chasis_no ?? 'N/A';

        // Helpers / lookups
        $data['segments']       = XpricingHelper::getSegments();
        $data['saleconsultants'] = XpricingHelper::selectfsc();
        $data['financiers']     = XlFinancier::select('id', 'name', 'short_name')->get()->toArray();
        $data['enum_master']    = EnumMaster::where('master_id', 94)->select('id', 'value')->get();

        // DSA name
        $drec = Xl_DSA_Master::find($booking->dsa_id);
        $dsaname = $drec ? $drec->name . ' - ' . $drec->mobile : 'N/A';

        // Collector name
        $collector = User::find($booking->col_by);
        $data['collector_name'] = $collector
            ? $collector->name . ' - ' . $collector->emp_code
            : 'N/A';

        // Remark permission (SALES / ACCOUNTS)
        $data['remark'] = 0;
        $depts = explode(',', $user->department ?? '');
        foreach ($depts as $deptId) {
            $deptName = commonhelper::enumValueById(trim($deptId));
            if ($deptName === 'SALES')   $data['remark'] = 1;
            if ($deptName === 'ACCOUNTS') $data['remark'] = 2;
        }

        // Make / OEM names
        $data['make1'] = Commonhelper::enumValueById($booking->exist_oem1) ?? 'N/A';
        $data['make2'] = Commonhelper::enumValueById($booking->exist_oem2) ?? 'N/A';

        // OEM IDs array
        $data['oem_ids'] = explode(',', $booking->exist_oem ?? '');

        // Fetch finance record (can be null)
        $finance = XFinance::where('bid', $id)->first();

        return view('booking.retail-edit', compact(
            'booking',
            'finance',
            'comm',
            'data',
            'dsaname',
            'uid',
            'user'
        ));
    }

    public function PayoutEdit($id)
    {
        $booking = Booking::findOrFail($id);

        // Get communication / remarks history
        $comm = ChatHelper::get_communication(3, $id);

        // Backpack authenticated user
        $user = backpack_user();
        $uid  = $user?->id ?? null;

        // Prepare shared $data array
        $data = [];

        $data['branch']   = X_Branch::find($booking->branch_code)?->name ?? 'N/A';
        $data['location'] = $booking->location_code > 0
            ? (X_Location::find($booking->location_code)?->name ?? 'N/A')
            : ($booking->location_other ?? 'N/A');

        // Accessories
        $acc = explode(',', $booking->accessories ?? '');
        $accessoryNames = [];
        foreach ($acc as $a) {
            if ($a = trim($a)) {
                $accessory = Xessories::find($a);
                if ($accessory) {
                    $accessoryNames[] = $accessory->item;
                }
            }
        }
        $data['accessories'] = $accessoryNames ? implode(', ', $accessoryNames) : 'N/A';

        // Chassis
        $chassis = Stock::find($booking->chasis_no);
        $data['bchasis'] = $chassis?->chasis_no ?? 'N/A';

        // Helpers / lookups
        $data['segments']       = XpricingHelper::getSegments();
        $data['saleconsultants'] = XpricingHelper::selectfsc();
        $data['financiers']     = XlFinancier::select('id', 'name', 'short_name')->get()->toArray();
        $data['enum_master']    = EnumMaster::where('master_id', 94)->select('id', 'value')->get();

        // DSA name
        $drec = Xl_DSA_Master::find($booking->dsa_id);
        $dsaname = $drec ? $drec->name . ' - ' . $drec->mobile : 'N/A';

        // Collector name
        $collector = User::find($booking->col_by);
        $data['collector_name'] = $collector
            ? $collector->name . ' - ' . $collector->emp_code
            : 'N/A';

        // Remark permission (SALES / ACCOUNTS)
        $data['remark'] = 0;
        $depts = explode(',', $user->department ?? '');
        foreach ($depts as $deptId) {
            $deptName = commonhelper::enumValueById(trim($deptId));
            if ($deptName === 'SALES')   $data['remark'] = 1;
            if ($deptName === 'ACCOUNTS') $data['remark'] = 2;
        }

        // Make / OEM names
        $data['make1'] = Commonhelper::enumValueById($booking->exist_oem1) ?? 'N/A';
        $data['make2'] = Commonhelper::enumValueById($booking->exist_oem2) ?? 'N/A';

        // OEM IDs array
        $data['oem_ids'] = explode(',', $booking->exist_oem ?? '');

        // Fetch finance record (can be null)
        $finance = XFinance::where('bid', $id)->first();

        return view('booking.payout-edit', compact(
            'booking',
            'finance',
            'comm',
            'data',
            'dsaname',
            'uid',
            'user'
        ));
    }

    public function PayoutUpdate(Request $request, $id)
    {
        // print_r($request->all());
        // die();

        // Fetch records
        $finance = XFinance::where('bid', $id)->firstOrFail();
        $booking = Booking::findOrFail($id);

        $old = $finance->toArray();
        $changes = [];

        // === VALIDATION (Only Payout Fields) ===
        $rules = [];
        $payout_category = $request->payout_category;

        if ($payout_category == 1) {
            // PAYOUT ACTIVE → Full validation
            $rules = [
                'loan_amount'           => 'required|numeric|min:0',                    // FROM FORM
                'do_number'             => 'nullable|string|max:50',
                'expected_payout_pct'   => 'required|numeric|min:0',
                'gst_included'          => 'required|in:0,0.5,1',
                'inv1_no'               => 'required|string|max:50',
                'inv1_name'             => 'required|string|max:100',
                'inv1_prov_gst'         => 'required|numeric|min:0',
                'inv2_no'               => 'nullable|string|max:50',
                'inv2_name'             => 'nullable|string|max:100',
                'inv2_prov_gst'         => 'nullable|numeric|min:0',
                'consideration_no_gst'  => 'required|numeric|min:0',
                'difference_no_gst'     => 'required|numeric',
                'payout_remarks'        => 'required|string',
            ];
        } else {
            // NO PAYOUT / CASH
            $rules = [
                'payout_category' => 'required|in:2,4',
                'payout_remarks'  => 'required|string',
            ];
            if ($payout_category == 2) {
                $rules['no_payout_reason'] = 'required|in:1,2,3,4,5,6';
            }
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return redirect()->back()
                ->withInput()
                ->withErrors($validator)
                ->with('error', $validator->messages()->first());
        }

        // === CLEAR PAYOUT FIELDS IF NOT PAYOUT (1) ===
        $payoutFields = [
            'do_number',
            'loan_amount_payout',
            'expected_payout_pct',
            'gst_included',
            'inv1_no',
            'inv1_name',
            'inv1_prov_gst',
            'inv2_no',
            'inv2_name',
            'inv2_prov_gst',
            'consideration_no_gst',
            'difference_no_gst'
        ];

        if ($payout_category != 1) {
            foreach ($payoutFields as $field) {
                $finance->$field = null;
            }
            if ($payout_category == 4) {
                $finance->no_payout_reason = null;
            }
        }

        // === SAVE PAYOUT FIELDS ===
        $finance->payout_category = $payout_category;

        if ($payout_category == 1) {
            $finance->loan_amount    = $request->loan_amount;        // FROM FORM
            $finance->instrument_ref_no             = $request->do_number;
            $finance->expected_payout_pct   = $request->expected_payout_pct;
            $finance->gst_included          = $request->gst_included;
            $finance->inv1_no               = $request->inv1_no;
            $finance->inv1_name             = $request->inv1_name;
            $finance->inv1_prov_gst         = $request->inv1_prov_gst;
            $finance->inv2_no               = $request->inv2_no;
            $finance->inv2_name             = $request->inv2_name;
            $finance->inv2_prov_gst         = $request->inv2_prov_gst;
            $finance->consideration_no_gst  = $request->consideration_no_gst;
            $finance->difference     = $request->difference_no_gst;

            $booking->payout = 2;
            $booking->save();
        } else {
            $finance->no_payout_reason = $request->no_payout_reason;
        }


        $finance->updated_by     = backpack_auth()->id();

        // === STATUS UPDATE ===
        if (in_array($finance->fin_mode, ['In-house', 'Customer_self']) && $finance->case_status == 2) {
            $finance->status = ($payout_category == 1) ? 3 : 2;
        }

        $finance->save();

        // === LOG CHANGES ===
        $fieldsToLog = [
            'payout_category',
            'no_payout_reason',
            'loan_amount_payout',           // ADDED
            'do_number',
            'expected_payout_pct',
            'gst_included',
            'inv1_no',
            'inv1_name',
            'inv1_prov_gst',
            'inv2_no',
            'inv2_name',
            'inv2_prov_gst',
            'consideration_no_gst',
            'difference_no_gst',
            'payout_remarks'
        ];

        $labels = [
            'payout_category'       => 'Payout Category',
            'no_payout_reason'      => 'No Payout Reason',
            'loan_amount_payout'    => 'Loan Amount (Payout)',          // ADDED
            'do_number'             => 'DO Number',
            'expected_payout_pct'   => 'Expected Payout %',
            'gst_included'          => 'GST Included',
            'inv1_no'               => '1st Invoice No.',
            'inv1_name'             => '1st Invoice Name',
            'inv1_prov_gst'         => '1st Provisioning (GST)',
            'inv2_no'               => '2nd Invoice No.',
            'inv2_name'             => '2nd Invoice Name',
            'inv2_prov_gst'         => '2nd Provisioning (GST)',
            'consideration_no_gst'  => 'Consideration (w/o GST)',
            'difference_no_gst'     => 'Difference (w/o GST)',
            'payout_remarks'        => 'Payout Remarks',
        ];

        $payoutCats = [1 => 'Payout', 2 => 'No Payout', 4 => 'Cash'];
        $noPayoutReasons = [
            1 => 'Low Interest Rate',
            2 => 'Low Tenure Funding',
            3 => 'Nil Payout Model',
            4 => 'Out Of Territory',
            5 => 'Financier Sourcing',
            6 => 'Other'
        ];
        $gstOpts = [0 => '0%', 0.5 => '50%', 1 => '100%'];

        // === CLEAN FORMATTER (PHP 8+) ===
        $formatValue = function ($value, $field) use ($payoutCats, $noPayoutReasons, $gstOpts) {
            if (is_null($value)) return 'N/A';

            return match ($field) {
                'payout_category' => $payoutCats[$value] ?? $value,
                'no_payout_reason' => $noPayoutReasons[$value] ?? $value,
                'gst_included' => $gstOpts[$value] ?? $value,
                'expected_payout_pct' => number_format($value, 4) . '%',
                'loan_amount_payout',
                'inv1_prov_gst',
                'inv2_prov_gst',
                'consideration_no_gst',
                'difference_no_gst' => '₹' . number_format($value, 2),
                default => $value,
            };
        };

        // === LOG LOOP ===
        foreach ($fieldsToLog as $field) {
            $oldVal = $old[$field] ?? null;
            $newVal = $finance->$field;

            if ($oldVal != $newVal) {
                $label = $labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
                $changes[] = "$label: '{$formatValue($oldVal,$field)}' to '{$formatValue($newVal,$field)}'";
            }
        }

        // === JOURNEY LOG ===
        $logMessage = $request->payout_remarks ?: "Payout updated";
        if (!empty($changes)) {
            $logMessage .= "\n\nChanges:\n" . implode("\n", $changes);
        }

        $commid = ChatHelper::get_commid(3, $id, "Booking Created");
        ChatHelper::add_followup(
            $commid,
            "Payout Updated\n" . $logMessage,
            "Updated by " . backpack_auth()->id(),
            null,
            1
        );

        return redirect()
            ->route('finance.payout')
            ->with('success', 'Payout details saved successfully!');
    }


    public function refundRequested(Request $request)
    {
        $this->crud->hasAccessOrFail('list');

        $this->data['crud'] = $this->crud;
        $this->data['title'] = 'Pending Refunds';

        // ────────────────────────────────────────────────
        // Query – getBaseQuery() se start karo
        // ────────────────────────────────────────────────
        $query = $this->getBaseQuery();

        // Refund Requested filter
        $query->where('bookings.status', 4);  // Refund Requested status

        // Optional status filter from dropdown
        $status_filter = $request->input('status_filter', '');
        if ($status_filter !== '' && $status_filter !== 'all') {
            $query->where('bookings.status', $status_filter);
        }

        $query->orderBy('bookings.id', 'DESC');

        $paginatedBookings = $query->paginate(50);

        // ────────────────────────────────────────────────
        // Preloads (tumhare lookups)
        // ────────────────────────────────────────────────
        $lookups = $this->getCommonLookups();
        extract($lookups);

        $saleConsultants = $lookups['saleConsultants'] ?? [];

        // ────────────────────────────────────────────────
        // Mapping – action button tumhara original
        // ────────────────────────────────────────────────
        $gridData = $paginatedBookings->map(function ($t, $index) use (
            $paginatedBookings,
            $segments,
            $saleConsultants
        ) {
            $row = $this->mapBookingForGrid($t);

            $row->serial_no = ($paginatedBookings->currentPage() - 1) * $paginatedBookings->perPage() + $index + 1;

            // Extra refund-specific fields (tumhare blade ke hisaab se)
            $row->refund_request_date = $t->refund_request_date
                ? Carbon::parse($t->refund_request_date)->format('d-M-Y')
                : 'N/A';

            // Action button (tumhara original – View Refund)
            $row->action = '
            <div class="text-center">
                <a href="' . backpack_url("booking/{$t->id}/refund-view") . '"
                   class="btn btn-primary btn-sm py-1 px-2">
                    Process
                </a>
            </div>';

            return $row;
        })->values();

        // ────────────────────────────────────────────────
        // Columns – sirf reusable wala call
        // ────────────────────────────────────────────────
        $columns = $this->getAgGridColumns();

        // Action column add agar missing hai (duplicate avoid)
        $hasAction = collect($columns)->contains('field', 'action');
        if (!$hasAction) {
            $columns[] = [
                'field'         => 'action',
                'headerName'    => 'Action',
                'width'         => 120,
                'pinned'        => 'right',
                'sortable'      => false,
                'filter'        => false,
                'cellRenderer'  => 'htmlRenderer',
                'cellClass'     => 'text-center p-0',
                'autoHeight'    => true,
            ];
        }

        $gridConfig = [
            'columns' => $columns,
            'data'    => $gridData,
        ];

        $this->data['gridConfig'] = $gridConfig;

        if ($gridData->isEmpty()) {
            session()->flash('info', 'No pending refund requests found.');
        }

        return view('booking.pending-refund', $this->data);
    }


    public function refundView($id)
    {
        $entry = Booking::findOrFail($id);
        $booking = $entry; // same object

        if ($entry->status != 4) {
            abort(404, 'This booking is not in refund requested status.');
        }

        $ref = Xl_Refunds::where('entity_type', 'booking')
            ->where('entity_id', $id)
            ->latest()           // latest wala better hai multiple records ke case mein
            ->first();

        $data = [];

        if ($ref) {
            // ------------------ Yahan change karo ------------------

            // Option A: Agar teeno files ek hi collection 'refund-documents' mein hain
            $docs = $ref->getMedia('refund-documents');

            $data['acc_proof']   = $docs[0]?->getUrl() ?? '';   // pehla file → account proof
            $data['aadhar']      = $docs[1]?->getUrl() ?? '';   // doosra → aadhaar
            $data['pan']         = $docs[2]?->getUrl() ?? '';   // teesra → pan

            // Option B: Agar order guaranteed nahi hai to filename se match kar sakte ho
            // (agar file names mein pattern hai jaise account.jpg, aadhaar.pdf etc)
            /*
            foreach ($ref->getMedia('refund-documents') as $media) {
                $name = strtolower($media->file_name);
                if (str_contains($name, 'account') || str_contains($name, 'bank')) {
                    $data['acc_proof'] = $media->getUrl();
                } elseif (str_contains($name, 'adhar') || str_contains($name, 'aadhar') || str_contains($name, 'aadhaar')) {
                    $data['aadhar'] = $media->getUrl();
                } elseif (str_contains($name, 'pan')) {
                    $data['pan'] = $media->getUrl();
                }
            }
            */

            $data['pay_proof'] = $ref->getFirstMediaUrl('pay-proof') ?: '';

            // Refund details
            $data['refund'] = [
                'remaining_amount'   => $ref->amount ?? 0,
                'bank_name'          => $ref->bank_name ?? 'N/A',
                'branch_name'        => $ref->branch_name ?? 'N/A',
                'account_type'       => $ref->account_type ?? 'N/A',
                'account_number'     => $ref->account_number ?? 'N/A',
                'holder_name'        => $ref->holder_name ?? 'N/A',
                'ifsc_code'          => $ref->ifsc_code ?? 'N/A',
                'details'            => $ref->details ?? 'N/A',
                'req_date'           => $ref->req_date ? Carbon::parse($ref->req_date)->format('d-M-Y') : 'N/A',
                'ref_date'           => $ref->ref_date ? Carbon::parse($ref->ref_date)->format('d-M-Y') : 'N/A',
                'mode'               => $ref->mode ?? 'N/A',
                'transaction_details' => $ref->transaction_details ?? 'N/A',
                'remark'             => $ref->remark ?? 'N/A',
            ];

            $data['amount']    = $entry->booking_amount ?? 0;
            $data['deduction'] = $data['amount'] - ($ref->amount ?? 0);
        }

        return view('booking.show', compact('entry', 'booking', 'data'));
    }


    public function rejected(Request $request)
    {
        $this->crud->hasAccessOrFail('list');

        $this->data['crud'] = $this->crud;
        $this->data['title'] = 'Refund Rejected Bookings';

        // ────────────────────────────────────────────────
        // Query – getBaseQuery() se start karo
        // ────────────────────────────────────────────────
        $query = $this->getBaseQuery();

        // Rejected filter
        $query->where('bookings.status', 7);  // Rejected status = 7

        // Optional status filter from dropdown
        $status_filter = $request->input('status_filter', '');
        if ($status_filter !== '' && $status_filter !== 'all') {
            $query->where('bookings.status', $status_filter);
        }

        $query->orderBy('bookings.id', 'DESC');

        $paginatedBookings = $query->paginate(50);

        // ────────────────────────────────────────────────
        // Preloads
        // ────────────────────────────────────────────────
        $lookups = $this->getCommonLookups();
        extract($lookups);

        $saleConsultants = $lookups['saleConsultants'] ?? [];

        // ────────────────────────────────────────────────
        // Mapping – action button tumhara original
        // ────────────────────────────────────────────────
        $gridData = $paginatedBookings->map(function ($t, $index) use (
            $paginatedBookings,
            $segments,
            $saleConsultants
        ) {
            $row = $this->mapBookingForGrid($t);

            $row->serial_no = ($paginatedBookings->currentPage() - 1) * $paginatedBookings->perPage() + $index + 1;

            // Rejected-specific fields
            $row->refund_request_date = $t->refund_request_date
                ? Carbon::parse($t->refund_request_date)->format('d-M-Y')
                : 'N/A';

            $row->refund_rejection_date = $t->refund_rejection_date
                ? Carbon::parse($t->refund_rejection_date)->format('d-M-Y')
                : 'N/A';

            // Action button (tumhara original – View Rejected Booking)
            $row->action = '
            <div class="text-center">
                <a href="' . route('rejected.view', $t->id) . '"
                   class="btn btn-primary btn-sm py-1 px-2"
                   >
                    <i class="fas fa-eye"></i> Process
                </a>
            </div>';

            return $row;
        })->values();

        // ────────────────────────────────────────────────
        // Columns – sirf reusable wala call
        // ────────────────────────────────────────────────
        $columns = $this->getAgGridColumns();

        // Action column add agar missing hai (duplicate avoid)
        $hasAction = collect($columns)->contains('field', 'action');
        if (!$hasAction) {
            $columns[] = [
                'field'         => 'action',
                'headerName'    => 'Action',
                'width'         => 120,
                'pinned'        => 'right',
                'sortable'      => false,
                'filter'        => false,
                'cellRenderer'  => 'htmlRenderer',
                'cellClass'     => 'text-center p-0',
                'autoHeight'    => true,
            ];
        }

        $gridConfig = [
            'columns' => $columns,
            'data'    => $gridData,
        ];

        $this->data['gridConfig'] = $gridConfig;

        // if ($gridData->isEmpty()) {
        //     session()->flash('info', 'No rejected refund bookings found.');
        // }

        return view('booking.rejected', $this->data);
    }

    // public function rejectedView($id)
    // {
    //     $entry = $booking = Booking::findOrFail($id);



    //     // Ensure it's refund requested
    //     if ($entry->status != 7) {
    //         abort(404, 'This booking is not in refund requested status.');
    //     }

    //     // Refund data fetch (tumhara code)
    //     $ref = Xl_Refunds::where('entity_type', 'booking')
    //         ->where('entity_id', $id)
    //         ->first();

    //     $data = []; // ya existing $data array

    //     if ($ref) {
    //         $data['acc_proof'] = $ref->getFirstMediaUrl('acc-proof') ?: '';
    //         $data['aadhar']    = $ref->getFirstMediaUrl('aadhar')    ?: '';
    //         $data['pan']       = $ref->getFirstMediaUrl('pan')       ?: '';
    //         $data['pay_proof'] = $ref->getFirstMediaUrl('pay-proof') ?: '';

    //         $refundDetails = Xl_Refunds::where('entity_id', $id)
    //             ->select(
    //                 'id',
    //                 'entity_type',
    //                 'entity_id',
    //                 'bank_name',
    //                 'branch_name',
    //                 'account_type',
    //                 'account_number',
    //                 'holder_name',
    //                 'ifsc_code',
    //                 'req_date',
    //                 'amount',
    //                 'details',
    //                 'ref_date',
    //                 'mode',
    //                 'transaction_details',
    //                 'remark'
    //             )
    //             ->first();

    //         if ($refundDetails) {
    //             $data['refund'] = $refundDetails->toArray();
    //             // Deduction = total amount - refunded amount
    //             $data['deduction'] = ($entry->booking_amount ?? 0) - ($refundDetails->amount ?? 0);
    //             $data['amount']    = $entry->booking_amount ?? 0; // original booking amount
    //         }
    //     }
    //     $receiptLogs = \App\Models\Bookingamount::where('bid', $entry->id)
    //         ->orderBy('date', 'desc') // or 'created_at'
    //         ->get();

    //     // Pass booking + refund data
    //     return view('booking.show', compact('entry', 'data', 'booking', 'receiptLogs'));
    // }
    public function rejectedView($id)
    {
        $booking = Booking::findOrFail($id);
        if ($booking->status != 7) {
            abort(404, 'This booking is not in Edit Refund status (status 7).');
        }

        // getFullBookingData से base data लो
        $view = $this->getFullBookingData($id, 'show');
        $data = $view->getData()['data'] ?? [];

        $refund = Xl_Refunds::where('entity_type', 'booking')
            ->where('entity_id', $id)
            ->latest('id')
            ->first();

        if ($refund) {
            // Multiple possible collection names try कर रहे हैं
            $data['acc_proof'] = $refund->getFirstMediaUrl('acc-proof')
                ?: $refund->getFirstMediaUrl('acc_proof')
                ?: $refund->getFirstMediaUrl('refund-documents')
                ?: '';

            $data['aadhar']    = $refund->getFirstMediaUrl('aadhar')
                ?: $refund->getFirstMediaUrl('aadhar_proof')
                ?: '';

            $data['pan']       = $refund->getFirstMediaUrl('pan')
                ?: $refund->getFirstMediaUrl('pan_proof')
                ?: '';

            $data['pay_proof'] = $refund->getFirstMediaUrl('pay-proof')
                ?: $refund->getFirstMediaUrl('pay_proof')
                ?: '';

            $data['deduction'] = ($booking->booking_amount ?? 0) - ($refund->amount ?? 0);
            $data['amount']    = $booking->booking_amount ?? 0;

            $data['refund'] = [
                'remaining_amount'    => $refund->amount ?? 0,
                'bank_name'           => $refund->bank_name ?? 'N/A',
                'branch_name'         => $refund->branch_name ?? 'N/A',
                'account_type'        => $refund->account_type ?? 'N/A',
                'account_number'      => $refund->account_number ?? 'N/A',
                'holder_name'         => $refund->holder_name ?? 'N/A',
                'ifsc_code'           => $refund->ifsc_code ?? 'N/A',
                'details'             => $refund->details ?? 'N/A',
                'req_date'            => $refund->req_date ? Carbon::parse($refund->req_date)->format('d-M-Y') : 'N/A',
                'ref_date'            => $refund->ref_date ? Carbon::parse($refund->ref_date)->format('d-M-Y') : 'N/A',
                'mode'                => $refund->mode ?? 'N/A',
                'transaction_details' => $refund->transaction_details ?? 'N/A',
                'remark'              => $refund->remark ?? 'N/A',
            ];
        }

        $receiptLogs = Bookingamount::where('bid', $booking->id)
            ->orderBy('date', 'desc')
            ->get();

        return view('booking.show', compact('booking', 'data', 'receiptLogs'));
    }

    public function refundUpdate(Request $request, $id)
    {
        // For debugging (remove later)
        // print_r($request->all());
        // die();

        // 1. Find the booking first (using the passed $id)
        $booking = Booking::findOrFail($id);

        // 2. Validation
        $validator = Validator::make($request->all(), [
            'ref_date'             => 'required|date',
            'mode'                 => 'required|string',
            'transaction_details'  => 'required|string',
            'remark'               => 'required|string',
            'pay_proof'            => 'required|file|mimes:jpeg,png,jpg,pdf|max:2048', // 2MB max
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Please fix the errors below.');
        }

        // 3. Find existing refund record for this booking
        $refund = Xl_Refunds::where('entity_type', 'booking')
            ->where('entity_id', $id)
            ->first();

        if (!$refund) {
            return redirect()->back()->with('error', 'Refund record not found for this booking.');
        }

        // 4. Update refund record
        $refund->update([
            'ref_date'            => $request->hidden_ref ?? $request->ref_date,  // use hidden or visible
            'ref_by'              => backpack_auth()->id(),                       // current admin user
            'mode'                => $request->mode,
            'transaction_details' => $request->transaction_details,
            'remark'              => $request->remark,
        ]);

        // 5. Handle proof upload (replace old if exists)
        if ($request->hasFile('pay_proof') && $request->file('pay_proof')->isValid()) {
            // Clear old proof if any
            $refund->clearMediaCollection('pay-proof');
            // Add new one
            $refund->addMedia($request->file('pay_proof'))
                ->toMediaCollection('pay-proof');
        }

        // 6. Update booking status to Refunded (5)
        $oldStatus = $booking->status;
        $newStatus = 5;

        $statusNames = [
            1 => 'Live',
            2 => 'Invoiced',
            3 => 'Cancelled',
            4 => 'Refund Queued',
            5 => 'Refunded',
            6 => 'On Hold',
            7 => 'Refund Rejected',
            8 => 'Pending',
        ];

        $oldName = $statusNames[$oldStatus] ?? 'Unknown';
        $newName = $statusNames[$newStatus] ?? 'Unknown';

        $statusRemark = ($oldStatus != $newStatus)
            ? "Booking status changed from {$oldName} to {$newName}"
            : null;

        $adminRemark = trim($request->remark) ?: 'Refund processed';

        // 7. Add followup log
        if ($statusRemark || $adminRemark) {
            $commid = ChatHelper::get_commid(3, $booking->id, "Booking Created");

            ChatHelper::add_followup(
                $commid,
                $statusRemark ?: $adminRemark,
                $statusRemark ? $adminRemark : null,
                null,
                1
            );
        }

        // 8. Final booking update
        $booking->update([
            'status'      => $newStatus,
            'refund_date' => now()->format('Y-m-d'),
        ]);

        // 9. Success redirect
        return redirect()->route('booking.refund.requested') // ya jo bhi route chahiye (e.g. 'booking.index', 'finance.payout')
            ->with('success', 'Refund details updated successfully and booking marked as Refunded.');
    }

    public function refunded(Request $request)
    {
        $this->crud->hasAccessOrFail('list');

        $this->data['crud'] = $this->crud;
        $this->data['title'] = 'Refunded Bookings';

        // ────────────────────────────────────────────────
        // Query – getBaseQuery() se start karo
        // ────────────────────────────────────────────────
        $query = $this->getBaseQuery();

        // Refunded filter
        $query->where('bookings.status', 5);  // Refunded status

        // Optional status filter from dropdown
        $status_filter = $request->input('status_filter', '');
        if ($status_filter !== '' && $status_filter !== 'all') {
            $query->where('bookings.status', $status_filter);
        }

        $query->orderBy('bookings.id', 'DESC');

        $paginatedBookings = $query->paginate(50);

        // ────────────────────────────────────────────────
        // Preloads
        // ────────────────────────────────────────────────
        $lookups = $this->getCommonLookups();
        extract($lookups);

        $saleConsultants = $lookups['saleConsultants'] ?? [];

        // ────────────────────────────────────────────────
        // Mapping – action button tumhara original
        // ────────────────────────────────────────────────
        $gridData = $paginatedBookings->map(function ($t, $index) use (
            $paginatedBookings,
            $segments,
            $saleConsultants
        ) {
            $row = $this->mapBookingForGrid($t);

            $row->serial_no = ($paginatedBookings->currentPage() - 1) * $paginatedBookings->perPage() + $index + 1;

            // Refund-specific fields
            $row->refund_date = $t->refund_date
                ? Carbon::parse($t->refund_date)->format('d-M-Y')
                : 'N/A';

            $row->refund_request_date = $t->refund_request_date
                ? Carbon::parse($t->refund_request_date)->format('d-M-Y')
                : 'N/A';

            // Action button (tumhara original – View Refunded Booking)
            $row->action = '
            <div class="text-center">
                <a href="' . route('booking.show', $t->id) . '"
                   class="btn btn-primary btn-sm py-1 px-2"
                   >
                    Process
                </a>
            </div>';

            return $row;
        })->values();

        // ────────────────────────────────────────────────
        // Columns – sirf reusable wala call
        // ────────────────────────────────────────────────
        $columns = $this->getAgGridColumns();

        // Action column add agar missing hai (duplicate avoid)
        $hasAction = collect($columns)->contains('field', 'action');
        if (!$hasAction) {
            $columns[] = [
                'field'         => 'action',
                'headerName'    => 'Action',
                'width'         => 120,
                'pinned'        => 'right',
                'sortable'      => false,
                'filter'        => false,
                'cellRenderer'  => 'htmlRenderer',
                'cellClass'     => 'text-center p-0',
                'autoHeight'    => true,
            ];
        }

        $gridConfig = [
            'columns' => $columns,
            'data'    => $gridData,
        ];

        $this->data['gridConfig'] = $gridConfig;

        if ($gridData->isEmpty()) {
            session()->flash('info', 'No refunded bookings found.');
        }

        return view('booking.refunded', $this->data);
    }
    /**
     * Update refund details for a booking that is already in "Refunded" status (status = 5)
     */
    public function refundedUpdate(Request $request, $id)
    {
        // 1. Find the booking (using xlr8_booking_master table)
        $booking = Booking::findOrFail($id);

        // 2. Security check: only allow if already refunded
        if ($booking->status != 5) {
            return redirect()->back()->with('error', 'This booking is not in Refunded status.');
        }

        // 3. Validate incoming data
        $validator = Validator::make($request->all(), [
            'ref_date'             => 'required|date',
            'mode'                 => 'required|in:Cash,Online,Cheque',
            'transaction_details'  => 'nullable|string|max:255',
            'remark'               => 'nullable|string|max:1000',
            'pay_proof'            => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048', // 2MB
            'booking_id'           => 'required|integer|exists:xlr8_booking_master,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', $validator->messages()->first());
        }

        // 4. Find the existing refund record
        $refund = Xl_Refunds::where('entity_type', 'booking')
            ->where('entity_id', $id)
            ->first();

        if (!$refund) {
            return redirect()->back()->with('error', 'Refund record not found for this booking.');
        }

        // 5. Track changes for logging
        $changes = [];

        // Refund Date
        $newRefDate = $request->hidden_ref ?? $request->ref_date;
        if ($refund->ref_date != $newRefDate) {
            $changes[] = "Refund Date changed from " .
                ($refund->ref_date ? Carbon::parse($refund->ref_date)->format('d-M-Y') : 'N/A') .
                " to " . Carbon::parse($newRefDate)->format('d-M-Y');
            $refund->ref_date = $newRefDate;
        }

        // Mode
        if ($refund->mode != $request->mode) {
            $changes[] = "Mode of Payment changed from {$refund->mode} to {$request->mode}";
            $refund->mode = $request->mode;
        }

        // Transaction Details
        if ($refund->transaction_details != $request->transaction_details) {
            $changes[] = "Transaction Details updated";
            $refund->transaction_details = $request->transaction_details;
        }

        // Remark
        if ($refund->remark != $request->remark) {
            $changes[] = "Remark updated";
            $refund->remark = $request->remark;
        }

        // 6. Handle payment proof upload (replace old one)
        if ($request->hasFile('pay_proof') && $request->file('pay_proof')->isValid()) {
            // Remove old proof if exists
            $refund->clearMediaCollection('pay-proof');

            // Add new proof
            $refund->addMedia($request->file('pay_proof'))
                ->toMediaCollection('pay-proof');

            $changes[] = "Payment Proof updated";
        }

        // 7. Update who last modified it
        $refund->ref_by = backpack_auth()->id(); // or Auth::id() if not Backpack

        // 8. Save refund changes
        $refund->save();

        // 9. Log changes in chat/followup
        if (!empty($changes)) {
            $commid = ChatHelper::get_commid(3, $booking->id, "Booking Created");

            $logMessage = "Refund Details Updated:\n" . implode("\n", $changes);

            ChatHelper::add_followup(
                $commid,
                $logMessage,
                "Updated by " . backpack_user()->name,
                null,
                1
            );
        }

        // 10. Success redirect (adjust route name as needed)
        return redirect()->route('bookings.refunded') // or 'booking.show', $id, etc.
            ->with('success', 'Refund details updated successfully!');
    }


    public function stockReport(Request $request)
    {
        $this->crud->hasAccessOrFail('list');

        $this->data['crud'] = $this->crud;
        $this->data['title'] = 'Stock Report';

        $now = Carbon::now();
        $py = $now->format('Y') - 1;
        $cy = $now->format('Y');
        $ovin = "STOCK VIN-" . $py;
        $cvin = "STOCK VIN-" . $cy;

        $locbr = DB::table('xlr8_us_location')
            ->whereNotNull('abbr')
            ->where('status', 1)
            ->pluck('abbr')
            ->unique()
            ->sort()
            ->values()
            ->toArray() ?: ['BKN', 'CHR'];


        $stocks = DB::table('xlr8_stock_master as stock')
            ->leftJoin('xlr8_vehicle_master as vm', 'stock.vh_id', '=', 'vm.id')

            // If vh_id join gives 0 rows, try this instead (uncomment and comment above line):
            // ->leftJoin('xlr8_vehicle_master as vm', 'stock.model_code', '=', 'vm.code')

            ->leftJoin('bmpl_enum_master as seg', 'vm.segment_id', '=', 'seg.id')
            ->leftJoin('xlr8_us_location as loc', 'stock.location_code', '=', 'loc.id')

            ->selectRaw("
                stock.id,
                stock.chasis_no,
                stock.oem_invoice_date,
                stock.damage,
                stock.v_status,
                COALESCE(seg.value, 'Unknown') as seg,
                COALESCE(vm.oem_model, 'Unknown') as mdl,          -- removed vm.model_name
                COALESCE(vm.oem_variant, 'Unknown') as vrnt,
                COALESCE(vm.color, 'Unknown') as clr,
                COALESCE(loc.abbr, 'UNK') as loc_abbr,
                vm.id as vh_id                                     -- added for better booking count later
        ")

            // Important filters (copied from your old working logic)
            ->whereNull('stock.inv_id')
            ->whereNull('stock.inv_date')
            ->where('stock.status', 1)
            ->whereIn('stock.v_status', ['Received', 'In Transit', 'Dealer Stock', 'Alloted'])  // added 'Alloted' just in case

            ->get();


        // Group by model
        // Group by the FULL vehicle identity (segment + model + variant + color)
        // This matches your screenshot / desired report style
        $grouped = $stocks->groupBy(function ($stock) {
            return implode('|', [
                $stock->seg   ?? 'Unknown',
                $stock->mdl   ?? 'Unknown',
                $stock->vrnt  ?? 'Unknown',
                $stock->clr   ?? 'Unknown',
            ]);
        });

        $data = [];
        $sno = 1;

        foreach ($grouped as $key => $groupStocks) {
            if ($groupStocks->isEmpty()) continue;

            [$seg, $mdl, $vrnt, $clr] = explode('|', $key);

            // Count per branch
            $branchCounts = $groupStocks->countBy('loc_abbr')->toArray();

            $row = [
                'sno'           => $sno++,
                'seg'           => $seg,
                'mdl'           => $mdl,
                'vrnt'          => $vrnt,
                'clr'           => $clr,
                'total'         => $groupStocks->count(),
                'bkn'           => $branchCounts['BKN'] ?? 0,
                'chr'           => $branchCounts['CHR'] ?? 0,
                'tst_max_age'   => '0 D',
                'stock_max_age' => '0 D',
                'stock_gt_60'   => 0,
                'bkng'          => (int) Booking::where('model', $mdl)->count(),
                'enq'           => (int) Booking::where('model', $mdl)->where('status', 1)->count(),
                'lordr'         => 0,
            ];

            // Reset per-year stats
            $ovin_stats = array_fill_keys($locbr, 0) + ['damage' => 0, 'dlr_transit' => 0, 'oem_transit' => 0];
            $cvin_stats = array_fill_keys($locbr, 0) + ['damage' => 0, 'dlr_transit' => 0, 'oem_transit' => 0];

            $tst_max_age = 0;
            $stock_max_age = 0;
            $stock_gt_60 = 0;

            foreach ($groupStocks as $stock) {
                if (empty($stock->oem_invoice_date) || empty($stock->chasis_no) || strlen($stock->chasis_no) < 10) {
                    continue;
                }

                $age = $now->diffInDays(Carbon::parse($stock->oem_invoice_date));
                $is_current_year = str_starts_with($stock->chasis_no, 'S'); // your logic

                $stats = $is_current_year ? $cvin_stats : $ovin_stats;

                $loc = $stock->loc_abbr ?? 'UNK';

                if (array_key_exists($loc, $stats)) {
                    $stats[$loc]++;
                }

                if ($stock->damage == 1) {
                    $stats['damage']++;
                }

                if (strtolower($stock->v_status) === 'in transit') {
                    $stats['oem_transit']++;
                    $tst_max_age = max($tst_max_age, $age);
                } else {
                    $stats['dlr_transit']++;
                    $stock_max_age = max($stock_max_age, $age);
                    if ($age >= 60) $stock_gt_60++;
                }
            }

            // Assign to row
            foreach ($locbr as $loc) {
                $row["ovin_" . strtolower($loc)] = $ovin_stats[$loc] ?? 0;
                $row["cvin_" . strtolower($loc)] = $cvin_stats[$loc] ?? 0;
            }

            $row['ovin_damage']      = $ovin_stats['damage'];
            $row['ovin_dlr_transit'] = $ovin_stats['dlr_transit'];
            $row['ovin_oem_transit'] = $ovin_stats['oem_transit'];
            $row['cvin_damage']      = $cvin_stats['damage'];
            $row['cvin_dlr_transit'] = $cvin_stats['dlr_transit'];
            $row['cvin_oem_transit'] = $cvin_stats['oem_transit'];

            $row['tst_max_age']   = $tst_max_age   ? $tst_max_age   . ' D' : '0 D';
            $row['stock_max_age'] = $stock_max_age ? $stock_max_age . ' D' : '0 D';
            $row['stock_gt_60']   = $stock_gt_60;

            $data[] = $row;
        }

        // Columns (tumhare original se copy-paste)
        $columns = [
            ['field' => 'sno', 'headerName' => 'S.No.', 'width' => 80, 'pinned' => 'left', 'filter' => false],

            [
                'headerName' => 'VEHICLE INFO',
                'children' => [
                    ['field' => 'seg',  'headerName' => 'SEGMENT', 'width' => 140],
                    ['field' => 'mdl',  'headerName' => 'MODEL',   'width' => 160],
                    ['field' => 'vrnt', 'headerName' => 'VARIANT', 'width' => 220],
                    ['field' => 'clr',  'headerName' => 'COLOR',   'width' => 130],
                ]
            ],

            [
                'headerName' => 'TOTAL STOCK',
                'children' => [
                    ['field' => 'total', 'headerName' => 'TOTAL', 'width' => 100, 'cellClass' => 'text-right'],
                    ['field' => 'bkn',   'headerName' => 'BKN',   'width' => 80,  'cellClass' => 'text-right'],
                    ['field' => 'chr',   'headerName' => 'CHR',   'width' => 80,  'cellClass' => 'text-right'],
                ]
            ],

            [
                'headerName' => $ovin,
                'children' => array_merge(
                    array_map(fn($loc) => ['field' => "ovin_" . strtolower($loc), 'headerName' => $loc, 'width' => 80, 'cellClass' => 'text-right'], $locbr),
                    [
                        ['field' => 'ovin_damage',      'headerName' => 'DAMAGE',     'width' => 100, 'cellClass' => 'text-right'],
                        ['field' => 'ovin_dlr_transit', 'headerName' => 'DLR TST',    'width' => 110, 'cellClass' => 'text-right'],
                        ['field' => 'ovin_oem_transit', 'headerName' => 'OEM TST',    'width' => 110, 'cellClass' => 'text-right'],
                    ]
                )
            ],

            [
                'headerName' => $cvin,
                'children' => array_merge(
                    array_map(fn($loc) => ['field' => "cvin_" . strtolower($loc), 'headerName' => $loc, 'width' => 80, 'cellClass' => 'text-right'], $locbr),
                    [
                        ['field' => 'cvin_damage',      'headerName' => 'DAMAGE',     'width' => 100, 'cellClass' => 'text-right'],
                        ['field' => 'cvin_dlr_transit', 'headerName' => 'DLR TST',    'width' => 110, 'cellClass' => 'text-right'],
                        ['field' => 'cvin_oem_transit', 'headerName' => 'OEM TST',    'width' => 110, 'cellClass' => 'text-right'],
                    ]
                )
            ],

            [
                'headerName' => 'GLOBAL DATA',
                'children' => [
                    ['field' => 'tst_max_age',  'headerName' => 'TST MAX AGE', 'width' => 140],
                    ['field' => 'stock_max_age', 'headerName' => 'PHY MAX AGE', 'width' => 140],
                    ['field' => 'stock_gt_60',  'headerName' => 'AGE > 60D',   'width' => 120, 'cellClass' => 'text-right'],
                    ['field' => 'bkng',         'headerName' => 'BOOKED',      'width' => 120, 'cellClass' => 'text-right'],
                    ['field' => 'enq',          'headerName' => 'HOT ENQ',     'width' => 120, 'cellClass' => 'text-right'],
                    ['field' => 'lordr',        'headerName' => 'LIVE ORDERS', 'width' => 130, 'cellClass' => 'text-right'],
                ]
            ],
        ];

        $gridConfig = [
            'columns' => $columns,
            'data'    => $data,
            'ovin'    => $ovin,
            'cvin'    => $cvin,
            'locbr'   => $locbr,
        ];

        $this->data['gridConfig'] = $gridConfig;

        return view('booking.stock', $this->data);
    }


    // Render the AG Grid view for live order report
    public function liveOrderReport(Request $request)
    {
        $this->crud->hasAccessOrFail('list');

        $this->data['crud'] = $this->crud;
        $this->data['title'] = 'Live Order Report';

        // Correct table + prevent duplicates with GROUP BY
        $vehicles = DB::table('xlr8_vehicle_master as vm')
            ->where('vm.lorder', '>', 0)
            ->leftJoin('xlr8_stock_master as stock', 'vm.id', '=', 'stock.vh_id')
            ->leftJoin('xlr8_us_location as loc', 'stock.location_code', '=', 'loc.id')
            ->select(
                'vm.segment_id',
                'vm.custom_model',
                'vm.custom_variant',
                'vm.color',
                'vm.lorder',
                DB::raw("COALESCE(MAX(loc.abbr), 'Not Allocated') as branch")  // ← MAX to avoid NULL if multiple
            )
            ->groupBy('vm.id', 'vm.segment_id', 'vm.custom_model', 'vm.custom_variant', 'vm.color', 'vm.lorder')  // ← Yeh line duplicates khatam karegi
            ->get();

        // Segments
        $segments = XpricingHelper::getSegments();

        // Grid data
        $gridData = $vehicles->map(function ($vh, $index) use ($segments) {
            $seg = CommonHelper::enumValueById($vh->segment_id) ?? 'N/A';

            return [
                'sno'    => $index + 1,
                'seg'    => $seg,
                'mdl'    => $vh->custom_model ?? 'N/A',
                'vrnt'   => $vh->custom_variant ?? 'N/A',
                'clr'    => $vh->color ?? 'N/A',
                'branch' => $vh->branch ?? 'Not Allocated',
                'lordr'  => $vh->lorder ?? 0,
            ];
        })->values();

        // Columns (same)
        $columns = [
            ['field' => 'sno',   'headerName' => 'S.No.', 'width' => 100,  'pinned' => 'left'],
            ['field' => 'seg',   'headerName' => 'Segment', 'width' => 200],
            ['field' => 'mdl',   'headerName' => 'Model',   'width' => 240],
            ['field' => 'vrnt',  'headerName' => 'Variant', 'width' => 280],
            ['field' => 'clr',   'headerName' => 'Color',   'width' => 250],
            ['field' => 'branch', 'headerName' => 'Branch',  'width' => 200],
            ['field' => 'lordr', 'headerName' => 'Live Orders', 'width' => 230, 'cellClass' => 'text-right'],
        ];

        $gridConfig = [
            'columns' => $columns,
            'data'    => $gridData,
        ];

        $this->data['gridConfig'] = $gridConfig;

        if ($gridData->isEmpty()) {
            session()->flash('info', 'No live orders found.');
        }

        return view('booking.live-order', $this->data);
    }

    public function fetchCbrData()
    {
        $now = Carbon::now();
        $mtdStart = $now->copy()->startOfMonth();
        $ytdStart = $now->copy()->startOfYear();

        // Cache the query for 1 hour
        $data = Cache::remember('cbr_data_' . $now->format('YmdH'), 3600, function () use ($mtdStart, $ytdStart, $now) {
            // Bulk fetch bookings
            $bookings = DB::table('xlr8_booking_master as bm')
                ->join('xlr8_vehicle_master as vm', 'bm.vh_id', '=', 'vm.id')
                ->join('bmpl_enum_master as em', 'vm.segment_id', '=', 'em.id')
                ->whereIn('bm.status', [1, 4, 6, 8])
                ->select(
                    'bm.id',
                    'bm.status',
                    'bm.b_type',
                    'bm.fin_mode',
                    'bm.buyer_type',
                    'bm.pending',
                    'bm.order',
                    'bm.dms_so',
                    'bm.booking_amount',
                    'bm.created_at',
                    DB::raw('CONCAT(em.value, "|", COALESCE(vm.oem_model, ""), "|", COALESCE(vm.oem_variant, ""), "|", COALESCE(vm.color, "")) as group_key'),
                    'em.value as seg',
                    'vm.oem_model as model',
                    'vm.oem_variant as variant',
                    'vm.color as clr',
                    'vm.code'
                )
                ->get()
                ->groupBy('group_key');

            // Bulk fetch booking amounts
            $bookingAmounts = DB::table('xlr8_booking_amount')
                ->where('status', 1)
                ->select('bid', DB::raw('SUM(amount) as total_amount'))
                ->groupBy('bid')
                ->pluck('total_amount', 'bid');

            // Bulk fetch live orders
            $liveOrders = DB::table('xlr8_vehicle_master as vm')
                ->join('bmpl_enum_master as em', 'vm.segment_id', '=', 'em.id')
                ->selectRaw('CONCAT(em.value, "|", COALESCE(vm.oem_model, ""), "|", COALESCE(vm.oem_variant, ""), "|", COALESCE(vm.color, "")) as group_key, SUM(vm.lorder) as lorder')
                ->groupBy('group_key')
                ->pluck('lorder', 'group_key');

            // Bulk fetch stock
            $stocksRaw = DB::table('xlr8_stock_master as sm')
                ->join('xlr8_vehicle_master as vm', 'sm.model_code', '=', 'vm.code')
                ->join('bmpl_enum_master as em', 'vm.segment_id', '=', 'em.id')
                ->join('xlr8_us_location as ul', 'sm.location_code', '=', 'ul.id')
                ->selectRaw('CONCAT(em.value, "|", COALESCE(vm.oem_model, ""), "|", COALESCE(vm.oem_variant, ""), "|", COALESCE(vm.color, "")) as group_key, ul.branch_code, COUNT(sm.id) as quantity')
                ->groupBy('group_key', 'ul.branch_code')
                ->get();

            $stocks = $stocksRaw->groupBy('group_key')->map(function ($group) {
                return $group->groupBy('branch_code')->map(fn($bg) => $bg->sum('quantity'));
            });

            // Bulk fetch exchange and scrappage pending statuses
            $exchanges = DB::table('xlr8_exchange')
                ->whereIn('verification_status', [0, null])
                ->select('bid', 'purchase_type')
                ->get()
                ->groupBy('bid')
                ->map(function ($group) {
                    return [
                        'exchange_pending' => $group->where('purchase_type', 'Exchange')->count() > 0 ? 1 : 0,
                        'scrappage_pending' => $group->where('purchase_type', 'Scrappage')->count() > 0 ? 1 : 0,
                    ];
                });

            // Bulk fetch finance pending statuses
            $finances = DB::table('xlr8_booking_finance')
                ->whereIn('verification_status', [0, null])
                ->pluck('bid')
                ->mapWithKeys(fn($bid) => [$bid => 1]);

            $data = collect();
            $index = 1;

            foreach ($bookings as $groupKey => $groupBookings) {
                [$seg, $model, $variant, $clr] = explode('|', $groupKey);

                $liveGroup = $groupBookings->whereIn('status', [1, 6, 8])->where('b_type', '!=', 'dummy');

                $total_bookings = $liveGroup->count();
                if ($total_bookings === 0) continue;

                $bkn_bookings = $liveGroup->where('b_type', 'Individual')->count();
                $chr_bookings = $liveGroup->where('b_type', 'Dealer')->count();

                $max_age_days = $liveGroup->max(
                    fn($booking) => abs(Carbon::parse($booking->created_at)->diffInDays(now()))
                );

                $age_gt_60 = $liveGroup->filter(fn($booking) => abs(Carbon::parse($booking->created_at)->diffInDays(now()))
                    > 60)->count();

                $live_orders = $liveOrders->get($groupKey, 0);

                $dummy_bookings = $groupBookings->whereIn('status', [1, 6, 8])->where('b_type', 'dummy')->count();

                $on_hold = $liveGroup->where('status', 6)->count();

                $verify = $liveGroup->where('order', 1)->count();

                $orders = $liveGroup->where('order', 2)->whereNull('dms_so')->count();

                $payments = $liveGroup->filter(function ($booking) use ($bookingAmounts) {
                    $total_amount = $bookingAmounts->get($booking->id, 0);
                    return $total_amount < $booking->booking_amount;
                })->count();

                $data_pending = $liveGroup->where('pending', '>', 0)->count();

                $refunds = $groupBookings->where('status', 4)->count();

                $cash = $liveGroup->where('fin_mode', 'Cash')->count();
                $cash_pct = $total_bookings > 0 ? round(($cash / $total_bookings) * 100, 2) : 0;

                $inhouse = $liveGroup->where('fin_mode', 'In-house')->count();
                $inhouse_pct = $total_bookings > 0 ? round(($inhouse / $total_bookings) * 100, 2) : 0;

                $self = $liveGroup->where('fin_mode', 'Customer-Self')->count();
                $self_pct = $total_bookings > 0 ? round(($self / $total_bookings) * 100, 2) : 0;

                $finance_pending = $liveGroup->filter(fn($booking) => $finances->get($booking->id, 0) > 0)->count();

                $mtd_live = $liveGroup->where('created_at', '>=', $mtdStart);
                $mtd_total = $mtd_live->count();
                $mtd_inhouse = $mtd_live->where('fin_mode', 'In-house')->count();
                $mtd_finance = $mtd_total > 0 ? round(($mtd_inhouse / $mtd_total) * 100, 2) : 0;

                $ytd_live = $liveGroup->where('created_at', '>=', $ytdStart);
                $ytd_total = $ytd_live->count();
                $ytd_inhouse = $ytd_live->where('fin_mode', 'In-house')->count();
                $ytd_finance = $ytd_total > 0 ? round(($ytd_inhouse / $ytd_total) * 100, 2) : 0;

                $exchange_inhouse = $liveGroup->where('buyer_type', 'Exchange')->count();
                $exchange_pct = $total_bookings > 0 ? round(($exchange_inhouse / $total_bookings) * 100, 2) : 0;
                $exchange_pending = $liveGroup->filter(fn($booking) => ($exchanges->get($booking->id)['exchange_pending'] ?? 0) > 0)->count();

                $mtd_exchange_inhouse = $mtd_live->where('buyer_type', 'Exchange')->count();
                $mtd_exchange = $mtd_total > 0 ? round(($mtd_exchange_inhouse / $mtd_total) * 100, 2) : 0;

                $ytd_exchange_inhouse = $ytd_live->where('buyer_type', 'Exchange')->count();
                $ytd_exchange = $ytd_total > 0 ? round(($ytd_exchange_inhouse / $ytd_total) * 100, 2) : 0;

                $scrappage_inhouse = $liveGroup->where('buyer_type', 'Scrappage')->count();
                $scrappage_pct = $total_bookings > 0 ? round(($scrappage_inhouse / $total_bookings) * 100, 2) : 0;
                $scrappage_pending = $liveGroup->filter(fn($booking) => ($exchanges->get($booking->id)['scrappage_pending'] ?? 0) > 0)->count();

                $mtd_scrappage_inhouse = $mtd_live->where('buyer_type', 'Scrappage')->count();
                $mtd_scrappage = $mtd_total > 0 ? round(($mtd_scrappage_inhouse / $mtd_total) * 100, 2) : 0;

                $ytd_scrappage_inhouse = $ytd_live->where('buyer_type', 'Scrappage')->count();
                $ytd_scrappage = $ytd_total > 0 ? round(($ytd_scrappage_inhouse / $ytd_total) * 100, 2) : 0;

                $stock_group = $stocks->get($groupKey, collect());
                $stock_total = $stock_group->values()->sum();
                $stock_bkn = $stock_group->get(1, 0);
                $stock_chr = $stock_group->get(2, 0);

                $data->push([
                    'sno' => $index++,
                    'seg' => $seg,
                    'model' => $model,
                    'variant' => $variant,
                    'clr' => $clr,
                    'stock_total' => $stock_total,
                    'stock_bkn' => $stock_bkn,
                    'stock_chr' => $stock_chr,
                    'total_bookings' => $total_bookings,
                    'bkn_bookings' => $bkn_bookings,
                    'chr_bookings' => $chr_bookings,
                    // 'max_age' => $max_age_days ? $max_age_days . ' D' : '',
                    'max_age' => $max_age_days ? ceil($max_age_days) . ' D' : '0 D',
                    'age_gt_60d' => $age_gt_60,
                    'live_orders' => $live_orders,
                    'dummy_bookings' => $dummy_bookings,
                    'on_hold' => $on_hold,
                    'verify' => $verify,
                    'orders' => $orders,
                    'payments' => $payments,
                    'data' => $data_pending,
                    'refund' => $refunds,
                    'cash' => $cash,
                    'cash_pct' => number_format($cash_pct, 2) . '%',
                    'inhouse' => $inhouse,
                    'inhouse_pct' => number_format($inhouse_pct, 2) . '%',
                    'self' => $self,
                    'self_pct' => number_format($self_pct, 2) . '%',
                    'finance_pending' => $finance_pending,
                    'mtd' => number_format($mtd_finance, 2) . '%',
                    'ytd' => number_format($ytd_finance, 2) . '%',
                    'exchange_inhouse' => $exchange_inhouse,
                    'exchange_inhouse_pct' => number_format($exchange_pct, 2) . '%',
                    'exchange_pending' => $exchange_pending,
                    'exchange_mtd' => number_format($mtd_exchange, 2) . '%',
                    'exchange_ytd' => number_format($ytd_exchange, 2) . '%',
                    'scrappage_inhouse' => $scrappage_inhouse,
                    'scrappage_inhouse_pct' => number_format($scrappage_pct, 2) . '%',
                    'scrappage_pending' => $scrappage_pending,
                    'scrappage_mtd' => number_format($mtd_scrappage, 2) . '%',
                    'scrappage_ytd' => number_format($ytd_scrappage, 2) . '%',
                ]);
            }

            return $data;
        });

        $tbr = [
            'seg' => 'Total',
            'total_bookings' => $data->sum('total_bookings'),
            'bkn_bookings' => $data->sum('bkn_bookings'),
            'chr_bookings' => $data->sum('chr_bookings'),
            'stock_total' => $data->sum('stock_total'),
            'stock_bkn' => $data->sum('stock_bkn'),
            'stock_chr' => $data->sum('stock_chr'),
            'max_age' => $data->max('max_age') ? str_replace(' D', '', $data->max('max_age')) . ' D' : '',
            'age_gt_60d' => $data->sum('age_gt_60d'),
            'live_orders' => $data->sum('live_orders'),
            'dummy_bookings' => $data->sum('dummy_bookings'),
            'on_hold' => $data->sum('on_hold'),
            'verify' => $data->sum('verify'),
            'orders' => $data->sum('orders'),
            'payments' => $data->sum('payments'),
            'data' => $data->sum('data'),
            'refund' => $data->sum('refund'),
            'cash' => $data->sum('cash'),
            'cash_pct' => $data->count() > 0 ? number_format($data->avg(fn($r) => (float) str_replace('%', '', $r['cash_pct'])), 2) . '%' : '0.00%',
            'inhouse' => $data->sum('inhouse'),
            'inhouse_pct' => $data->count() > 0 ? number_format($data->avg(fn($r) => (float) str_replace('%', '', $r['inhouse_pct'])), 2) . '%' : '0.00%',
            'self' => $data->sum('self'),
            'self_pct' => $data->count() > 0 ? number_format($data->avg(fn($r) => (float) str_replace('%', '', $r['self_pct'])), 2) . '%' : '0.00%',
            'finance_pending' => $data->sum('finance_pending'),
            'mtd' => $data->count() > 0 ? number_format($data->avg(fn($r) => (float) str_replace('%', '', $r['mtd'])), 2) . '%' : '0.00%',
            'ytd' => $data->count() > 0 ? number_format($data->avg(fn($r) => (float) str_replace('%', '', $r['ytd'])), 2) . '%' : '0.00%',
            'exchange_inhouse' => $data->sum('exchange_inhouse'),
            'exchange_inhouse_pct' => $data->count() > 0 ? number_format($data->avg(fn($r) => (float) str_replace('%', '', $r['exchange_inhouse_pct'])), 2) . '%' : '0.00%',
            'exchange_pending' => $data->sum('exchange_pending'),
            'exchange_mtd' => $data->count() > 0 ? number_format($data->avg(fn($r) => (float) str_replace('%', '', $r['exchange_mtd'])), 2) . '%' : '0.00%',
            'exchange_ytd' => $data->count() > 0 ? number_format($data->avg(fn($r) => (float) str_replace('%', '', $r['exchange_ytd'])), 2) . '%' : '0.00%',
            'scrappage_inhouse' => $data->sum('scrappage_inhouse'),
            'scrappage_inhouse_pct' => $data->count() > 0 ? number_format($data->avg(fn($r) => (float) str_replace('%', '', $r['scrappage_inhouse_pct'])), 2) . '%' : '0.00%',
            'scrappage_pending' => $data->sum('scrappage_pending'),
            'scrappage_mtd' => $data->count() > 0 ? number_format($data->avg(fn($r) => (float) str_replace('%', '', $r['scrappage_mtd'])), 2) . '%' : '0.00%',
            'scrappage_ytd' => $data->count() > 0 ? number_format($data->avg(fn($r) => (float) str_replace('%', '', $r['scrappage_ytd'])), 2) . '%' : '0.00%',
        ];

        $stkbr = [
            'stock_total' => DB::table('xlr8_stock_master')->count('id'),
            'stock_bkn' => DB::table('xlr8_stock_master')
                ->join('xlr8_us_location', 'xlr8_stock_master.location_code', '=', 'xlr8_us_location.id')
                ->where('xlr8_us_location.branch_code', 1)
                ->count('xlr8_stock_master.id'),
            'stock_chr' => DB::table('xlr8_stock_master')
                ->join('xlr8_us_location', 'xlr8_stock_master.location_code', '=', 'xlr8_us_location.id')
                ->where('xlr8_us_location.branch_code', 2)
                ->count('xlr8_stock_master.id'),
        ];

        $title = 'Consolidated Booking Report';
        $filename = 'CnsldtBkngRprt_' . $now->format('Y-m-d-H-i-s') . '.xlsx';

        $header = [
            ['title' => 'S.No.', 'field' => 'sno', 'hozAlign' => 'center', 'formatter' => 'plaintext'],
            [
                'title' => 'Vehicle Info',
                'columns' => [
                    ['title' => 'Segment', 'field' => 'seg', 'headerFilter' => 'select'],
                    ['title' => 'Model', 'field' => 'model', 'headerFilter' => 'select'],
                    ['title' => 'Variant', 'field' => 'variant', 'headerFilter' => 'select'],
                    ['title' => 'Color', 'field' => 'clr', 'headerFilter' => 'select'],
                ]
            ],
            [
                'title' => 'Stock',
                'columns' => [
                    ['title' => 'Total', 'field' => 'stock_total', 'bottomCalc' => 'sum'],
                    ['title' => 'BKN', 'field' => 'stock_bkn', 'bottomCalc' => 'sum'],
                    ['title' => 'CHR', 'field' => 'stock_chr', 'bottomCalc' => 'sum'],
                ]
            ],
            [
                'title' => 'Bookings',
                'columns' => [
                    ['title' => 'Total', 'field' => 'total_bookings', 'bottomCalc' => 'sum'],
                    ['title' => 'BKN', 'field' => 'bkn_bookings', 'bottomCalc' => 'sum'],
                    ['title' => 'CHR', 'field' => 'chr_bookings', 'bottomCalc' => 'sum'],
                ]
            ],
            [
                'title' => 'Global Info',
                'columns' => [
                    ['title' => 'Max Age', 'field' => 'max_age', 'bottomCalc' => function ($values) {
                        $max = collect($values)->map(fn($val) => (int) str_replace(' D', '', $val))->max();
                        return $max . ' D';
                    }],
                    ['title' => 'Age > 60D', 'field' => 'age_gt_60d', 'bottomCalc' => function ($values) {
                        $max = collect($values)->map(fn($val) => (int) str_replace(' D', '', $val))->max();
                        return $max . ' D';
                    }],
                    ['title' => 'Live Orders', 'field' => 'live_orders', 'bottomCalc' => 'sum'],
                    ['title' => 'Dummy Bookings', 'field' => 'dummy_bookings', 'bottomCalc' => 'sum'],
                    ['title' => 'On Hold', 'field' => 'on_hold', 'bottomCalc' => 'sum'],
                ]
            ],
            [
                'title' => 'Pending Actions',
                'columns' => [
                    ['title' => 'Verify', 'field' => 'verify', 'bottomCalc' => 'sum'],
                    ['title' => 'Orders', 'field' => 'orders', 'bottomCalc' => 'sum'],
                    ['title' => 'Payments', 'field' => 'payments', 'bottomCalc' => 'sum'],
                    ['title' => 'Data', 'field' => 'data', 'bottomCalc' => 'sum'],
                    ['title' => 'Refund', 'field' => 'refund', 'bottomCalc' => 'sum'],
                ]
            ],
            [
                'title' => 'Finance',
                'columns' => [
                    ['title' => 'Cash', 'field' => 'cash'],
                    ['title' => 'Cash %', 'field' => 'cash_pct', 'bottomCalc' => function ($values) {
                        $avg = collect($values)->map(fn($val) => (float) str_replace('%', '', $val))->avg();
                        return number_format($avg, 2) . '%';
                    }],
                    ['title' => 'In-house', 'field' => 'inhouse'],
                    ['title' => 'In-house %', 'field' => 'inhouse_pct', 'bottomCalc' => function ($values) {
                        $avg = collect($values)->map(fn($val) => (float) str_replace('%', '', $val))->avg();
                        return number_format($avg, 2) . '%';
                    }],
                    ['title' => 'Self', 'field' => 'self'],
                    ['title' => 'Self %', 'field' => 'self_pct', 'bottomCalc' => function ($values) {
                        $avg = collect($values)->map(fn($val) => (float) str_replace('%', '', $val))->avg();
                        return number_format($avg, 2) . '%';
                    }],
                    ['title' => 'Pending', 'field' => 'finance_pending'],
                    ['title' => 'MTD', 'field' => 'mtd', 'bottomCalc' => function ($values) {
                        $avg = collect($values)->map(fn($val) => (float) str_replace('%', '', $val))->avg();
                        return number_format($avg, 2) . '%';
                    }],
                    ['title' => 'YTD', 'field' => 'ytd', 'bottomCalc' => function ($values) {
                        $avg = collect($values)->map(fn($val) => (float) str_replace('%', '', $val))->avg();
                        return number_format($avg, 2) . '%';
                    }],
                ]
            ],
            [
                'title' => 'Exchange',
                'columns' => [
                    ['title' => 'In-house', 'field' => 'exchange_inhouse'],
                    ['title' => 'In-house %', 'field' => 'exchange_inhouse_pct', 'bottomCalc' => function ($values) {
                        $avg = collect($values)->map(fn($val) => (float) str_replace('%', '', $val))->avg();
                        return number_format($avg, 2) . '%';
                    }],
                    ['title' => 'Pending', 'field' => 'exchange_pending'],
                    ['title' => 'MTD', 'field' => 'exchange_mtd', 'bottomCalc' => function ($values) {
                        $avg = collect($values)->map(fn($val) => (float) str_replace('%', '', $val))->avg();
                        return number_format($avg, 2) . '%';
                    }],
                    ['title' => 'YTD', 'field' => 'exchange_ytd', 'bottomCalc' => function ($values) {
                        $avg = collect($values)->map(fn($val) => (float) str_replace('%', '', $val))->avg();
                        return number_format($avg, 2) . '%';
                    }],
                ]
            ],
            [
                'title' => 'Scrappage',
                'columns' => [
                    ['title' => 'In-house', 'field' => 'scrappage_inhouse'],
                    ['title' => 'In-house %', 'field' => 'scrappage_inhouse_pct'],
                    ['title' => 'Pending', 'field' => 'scrappage_pending'],
                    ['title' => 'MTD', 'field' => 'scrappage_mtd'],
                    ['title' => 'YTD', 'field' => 'scrappage_ytd'],
                ]
            ],
        ];

        return [$header, $data, $tbr, $stkbr, $filename, $title];
    }

    public function consolidatedBookingReport(Request $request)
    {
        $this->crud->hasAccessOrFail('list');

        $this->data['crud'] = $this->crud;
        $this->data['title'] = 'Consolidated Booking Report';

        $now = Carbon::now();

        // ────────────────────────────────────────────────
        // Exact same complex logic jo photo mein data de raha tha
        // ────────────────────────────────────────────────
        $bookings = DB::table('xlr8_booking_master as bm')
            ->join('xlr8_vehicle_master as vm', 'bm.vh_id', '=', 'vm.id')
            ->join('bmpl_enum_master as em', 'vm.segment_id', '=', 'em.id')
            ->whereIn('bm.status', [1, 4, 6, 8])
            ->select(
                'bm.id',
                'bm.status',
                'bm.b_type',
                'bm.fin_mode',
                'bm.buyer_type',
                'bm.pending',
                'bm.order',
                'bm.dms_so',
                'bm.booking_amount',
                'bm.created_at',
                DB::raw('CONCAT(em.value, "|", COALESCE(vm.oem_model, ""), "|", COALESCE(vm.oem_variant, ""), "|", COALESCE(vm.color, "")) as group_key'),
                'em.value as seg',
                'vm.oem_model as model',
                'vm.oem_variant as variant',
                'vm.color as clr',
                'vm.code'
            )
            ->get()
            ->groupBy('group_key');

        $bookingAmounts = DB::table('xlr8_booking_amount')
            ->where('status', 1)
            ->select('bid', DB::raw('SUM(amount) as total_amount'))
            ->groupBy('bid')
            ->pluck('total_amount', 'bid');

        $liveOrders = DB::table('xlr8_vehicle_master as vm')
            ->join('bmpl_enum_master as em', 'vm.segment_id', '=', 'em.id')
            ->selectRaw('CONCAT(em.value, "|", COALESCE(vm.oem_model, ""), "|", COALESCE(vm.oem_variant, ""), "|", COALESCE(vm.color, "")) as group_key, SUM(vm.lorder) as lorder')
            ->groupBy('group_key')
            ->pluck('lorder', 'group_key');

        $stocksRaw = DB::table('xlr8_stock_master as sm')
            ->join('xlr8_vehicle_master as vm', 'sm.model_code', '=', 'vm.code')
            ->join('bmpl_enum_master as em', 'vm.segment_id', '=', 'em.id')
            ->join('xlr8_us_location as ul', 'sm.location_code', '=', 'ul.id')
            ->selectRaw('CONCAT(em.value, "|", COALESCE(vm.oem_model, ""), "|", COALESCE(vm.oem_variant, ""), "|", COALESCE(vm.color, "")) as group_key, ul.branch_code, COUNT(sm.id) as quantity')
            ->groupBy('group_key', 'ul.branch_code')
            ->get();

        $stocks = $stocksRaw->groupBy('group_key')->map(function ($group) {
            return $group->groupBy('branch_code')->map(fn($bg) => $bg->sum('quantity'));
        });

        $exchanges = DB::table('xlr8_exchange')
            ->whereIn('verification_status', [0, null])
            ->select('bid', 'purchase_type')
            ->get()
            ->groupBy('bid')
            ->map(function ($group) {
                return [
                    'exchange_pending' => $group->where('purchase_type', 'Exchange')->count() > 0 ? 1 : 0,
                    'scrappage_pending' => $group->where('purchase_type', 'Scrappage')->count() > 0 ? 1 : 0,
                ];
            });

        $finances = DB::table('xlr8_booking_finance')
            ->whereIn('verification_status', [0, null])
            ->pluck('bid')
            ->mapWithKeys(fn($bid) => [$bid => 1]);

        // ────────────────────────────────────────────────
        // Data Processing (exact same as your old fetchCbrData)
        // ────────────────────────────────────────────────
        $gridData = [];
        $sno = 1;

        foreach ($bookings as $groupKey => $groupBookings) {
            [$seg, $model, $variant, $clr] = explode('|', $groupKey);

            $liveGroup = $groupBookings->whereIn('status', [1, 6, 8])->where('b_type', '!=', 'dummy');

            $total_bookings = $liveGroup->count();
            if ($total_bookings === 0) continue;

            $bkn_bookings = $liveGroup->where('b_type', 'Individual')->count();
            $churu_bookings = $liveGroup->where('b_type', 'Dealer')->count();

            $max_age_days = $liveGroup->max(fn($b) => abs(Carbon::parse($b->created_at)->diffInDays($now)));

            $age_gt_60 = $liveGroup->filter(fn($b) => abs(Carbon::parse($b->created_at)->diffInDays($now)) > 60)->count();

            $live_orders = $liveOrders->get($groupKey, 0);

            $dummy_bookings = $groupBookings->whereIn('status', [1, 6, 8])->where('b_type', 'dummy')->count();

            $on_hold = $liveGroup->where('status', 6)->count();

            $verify = $liveGroup->where('order', 1)->count();

            $orders = $liveGroup->where('order', 2)->whereNull('dms_so')->count();

            $payments = $liveGroup->filter(function ($b) use ($bookingAmounts) {
                return ($bookingAmounts->get($b->id, 0) ?? 0) < $b->booking_amount;
            })->count();

            $data_pending = $liveGroup->where('pending', '>', 0)->count();

            $refund = $groupBookings->where('status', 4)->count();

            $cash = $liveGroup->where('fin_mode', 'Cash')->count();
            $cash_pct = $total_bookings ? round($cash / $total_bookings * 100, 2) : 0;

            $inhouse = $liveGroup->where('fin_mode', 'In-house')->count();
            $inhouse_pct = $total_bookings ? round($inhouse / $total_bookings * 100, 2) : 0;

            $self = $liveGroup->where('fin_mode', 'Customer-Self')->count();
            $self_pct = $total_bookings ? round($self / $total_bookings * 100, 2) : 0;

            $finance_pending = $liveGroup->filter(fn($b) => $finances->get($b->id, 0))->count();

            $stock_group = $stocks->get($groupKey, collect());
            $stock_total = $stock_group->values()->sum();
            $stock_bkn = $stock_group->get(1, 0); // BIKANER
            $stock_churu = $stock_group->get(2, 0); // CHURU

            $gridData[] = [
                'sno' => $sno++,
                'seg' => $seg,
                'model' => $model,
                'variant' => $variant,
                'clr' => $clr,
                'stock_total' => $stock_total,
                'stock_bikaner' => $stock_bkn,
                'stock_churu' => $stock_churu,
                'booking_total' => $total_bookings,
                'booking_bikaner' => $bkn_bookings,
                'booking_churu' => $churu_bookings,
                'hot_enq_total' => 0, // agar hot enquiry hai to add kar dena
                'hot_enq_bikaner' => 0,
                'hot_enq_churu' => 0,
                'finance_total' => $cash + $inhouse + $self,
                'finance_bikaner' => 0,
                'finance_churu' => 0,
                'finance_pending' => $finance_pending,
                'exchange_total' => $liveGroup->where('buyer_type', 'Exchange')->count(),
                'exchange_bikaner' => 0,
                'exchange_churu' => 0,
                'exchange_pending' => $liveGroup->filter(fn($b) => $exchanges->get($b->id)['exchange_pending'] ?? 0)->count(),
                'max_age' => $max_age_days ? ceil($max_age_days) . ' D' : '0 D',
                // 'max_age' => $max_age_days ? $max_age_days . ' D' : '0 D',
                'age_gt_60d' => $age_gt_60,
                'live_orders' => $live_orders,
                'dummy_bookings' => $dummy_bookings,
                'on_hold' => $on_hold,
                'order_verification' => $verify,
                'order_creation' => $orders,
                'booking_creation' => $total_bookings,
                'customer_data' => $data_pending,
                'book_canc' => 0,
                'refund' => $refund,
            ];
        }

        // ────────────────────────────────────────────────
        // Columns – exact photo jaisa
        // ────────────────────────────────────────────────
        $columns = [
            [
                'field'   => 'sno',
                'headerName' => 'S.No.',
                'width'   => 80,
                'pinned'  => 'left',
                'filter'  => false,
                'sortable' => false,     // optional but good for serial number
            ],

            [
                'headerName' => 'Vehicle Info',
                'headerClass' => 'group-vehicle-info',
                'children' => [
                    ['field' => 'seg', 'headerName' => 'Segment', 'width' => 140],
                    ['field' => 'model', 'headerName' => 'Model', 'width' => 180],
                    ['field' => 'variant', 'headerName' => 'Variant', 'width' => 240],
                    ['field' => 'clr', 'headerName' => 'Color', 'width' => 140],
                ]
            ],

            [
                'headerName' => 'STOCK',
                'headerClass' => 'group-stock',
                'children' => [
                    ['field' => 'stock_total', 'headerName' => 'TOTAL', 'width' => 90, 'cellClass' => 'text-right'],
                    ['field' => 'stock_bikaner', 'headerName' => 'BIKANER', 'width' => 90, 'cellClass' => 'text-right'],
                    ['field' => 'stock_churu', 'headerName' => 'CHURU', 'width' => 90, 'cellClass' => 'text-right'],
                ]
            ],

            [
                'headerName' => 'BOOKING',
                'headerClass' => 'group-booking',
                'children' => [
                    ['field' => 'booking_total', 'headerName' => 'TOTAL', 'width' => 90, 'cellClass' => 'text-right'],
                    ['field' => 'booking_bikaner', 'headerName' => 'BIKANER', 'width' => 90, 'cellClass' => 'text-right'],
                    ['field' => 'booking_churu', 'headerName' => 'CHURU', 'width' => 90, 'cellClass' => 'text-right'],
                ]
            ],

            [
                'headerName' => 'HOT ENQ',
                'headerClass' => 'group-hot-enq',
                'children' => [
                    ['field' => 'hot_enq_total', 'headerName' => 'TOTAL', 'width' => 90, 'cellClass' => 'text-right'],
                    ['field' => 'hot_enq_bikaner', 'headerName' => 'BIKANER', 'width' => 90, 'cellClass' => 'text-right'],
                    ['field' => 'hot_enq_churu', 'headerName' => 'CHURU', 'width' => 90, 'cellClass' => 'text-right'],
                ]
            ],

            [
                'headerName' => 'INT IN FINANCE',
                'headerClass' => 'group-finance',
                'children' => [
                    ['field' => 'finance_total', 'headerName' => 'TOTAL', 'width' => 90, 'cellClass' => 'text-right'],
                    ['field' => 'finance_bikaner', 'headerName' => 'BIKANER', 'width' => 90, 'cellClass' => 'text-right'],
                    ['field' => 'finance_churu', 'headerName' => 'CHURU', 'width' => 90, 'cellClass' => 'text-right'],
                    ['field' => 'finance_pending', 'headerName' => 'PENDING', 'width' => 110, 'cellClass' => 'text-right fw-bold'],
                ]
            ],

            [
                'headerName' => 'INT IN EXCHANGE',
                'headerClass' => 'group-exchange',
                'children' => [
                    ['field' => 'exchange_total', 'headerName' => 'TOTAL', 'width' => 90, 'cellClass' => 'text-right'],
                    ['field' => 'exchange_bikaner', 'headerName' => 'BIKANER', 'width' => 90, 'cellClass' => 'text-right'],
                    ['field' => 'exchange_churu', 'headerName' => 'CHURU', 'width' => 90, 'cellClass' => 'text-right'],
                    ['field' => 'exchange_pending', 'headerName' => 'PENDING', 'width' => 110, 'cellClass' => 'text-right fw-bold'],
                ]
            ],

            [
                'headerName' => 'GLOBAL INFO',
                'headerClass' => 'group-global',
                'children' => [
                    ['field' => 'max_age', 'headerName' => 'MAX AGE', 'width' => 100],
                    ['field' => 'age_gt_60d', 'headerName' => 'AGE > 60D', 'width' => 110, 'cellClass' => 'text-right'],
                    ['field' => 'live_orders', 'headerName' => 'LIVE ORDERS', 'width' => 120, 'cellClass' => 'text-right'],
                    ['field' => 'dummy_bookings', 'headerName' => 'DUMMY BOOKINGS', 'width' => 140, 'cellClass' => 'text-right'],
                    ['field' => 'on_hold', 'headerName' => 'ON HOLD', 'width' => 100, 'cellClass' => 'text-right'],
                    ['field' => 'order_verification', 'headerName' => 'ORDER VERIFICATION', 'width' => 140, 'cellClass' => 'text-right'],
                    ['field' => 'order_creation', 'headerName' => 'ORDER CREATION', 'width' => 140, 'cellClass' => 'text-right'],
                    ['field' => 'booking_creation', 'headerName' => 'BOOKING CREATION', 'width' => 140, 'cellClass' => 'text-right'],
                    ['field' => 'customer_data', 'headerName' => 'CUSTOMER DATA', 'width' => 140, 'cellClass' => 'text-right'],
                    ['field' => 'book_canc', 'headerName' => 'BOOK CANC', 'width' => 120, 'cellClass' => 'text-right'],
                    ['field' => 'refund', 'headerName' => 'REFUND', 'width' => 100, 'cellClass' => 'text-right'],
                ]
            ],
        ];

        $gridConfig = [
            'columns' => $columns,
            'data' => $gridData,
        ];

        $this->data['gridConfig'] = $gridConfig;

        if (empty($gridData)) {
            session()->flash('info', 'No consolidated booking data found.');
        }

        return view('booking.consolidated-booking', $this->data);
    }

    public function branchBookingReport(Request $request)
    {
        $this->crud->hasAccessOrFail('list');

        $this->data['crud'] = $this->crud;
        $this->data['title'] = 'Branch Booking Report';

        $now = Carbon::now();

        // ────────────────────────────────────────────────
        // Grouped bookings by vehicle
        // ────────────────────────────────────────────────
        $bookings = DB::table('xlr8_booking_master as bm')
            ->join('xlr8_vehicle_master as vm', 'bm.vh_id', '=', 'vm.id')
            ->join('bmpl_enum_master as em', 'vm.segment_id', '=', 'em.id')
            ->whereIn('bm.status', [1, 4, 6, 8])
            ->select(
                'bm.id',
                'bm.status',
                'bm.b_type',
                'bm.created_at',
                'bm.pending',
                'bm.order',
                'bm.dms_so',
                'bm.booking_amount',
                DB::raw('CONCAT(em.value, "|", COALESCE(vm.oem_model, ""), "|", COALESCE(vm.oem_variant, ""), "|", COALESCE(vm.color, "")) as group_key'),
                'em.value as seg',
                'vm.oem_model as model',
                'vm.oem_variant as variant',
                'vm.color as clr'
            )
            ->get()
            ->groupBy('group_key');

        // ────────────────────────────────────────────────
        // Branch-wise stock (multiple branches as per photo)
        // ────────────────────────────────────────────────
        $stocksRaw = DB::table('xlr8_stock_master as sm')
            ->join('xlr8_vehicle_master as vm', 'sm.model_code', '=', 'vm.code')
            ->join('bmpl_enum_master as em', 'vm.segment_id', '=', 'em.id')
            ->join('xlr8_us_location as ul', 'sm.location_code', '=', 'ul.id')
            ->selectRaw('CONCAT(em.value, "|", COALESCE(vm.oem_model, ""), "|", COALESCE(vm.oem_variant, ""), "|", COALESCE(vm.color, "")) as group_key, ul.name as branch_name, COUNT(sm.id) as quantity')
            ->groupBy('group_key', 'ul.name')
            ->get();

        $stocks = $stocksRaw->groupBy('group_key')->map(fn($g) => $g->pluck('quantity', 'branch_name')->toArray());

        // ────────────────────────────────────────────────
        // Grid Data – photo ke exact fields
        // ────────────────────────────────────────────────
        $gridData = [];
        $sno = 1;

        foreach ($bookings as $groupKey => $groupBookings) {
            [$seg, $model, $variant, $clr] = explode('|', $groupKey);

            $liveGroup = $groupBookings->whereIn('status', [1, 6, 8])->where('b_type', '!=', 'dummy');

            $total_bookings = $liveGroup->count();
            if ($total_bookings === 0) continue;

            $bkn_bookings = $liveGroup->where('b_type', 'Individual')->count();
            $churu_bookings = $liveGroup->where('b_type', 'Dealer')->count();

            $max_age_days = $liveGroup->max(fn($b) => abs(Carbon::parse($b->created_at)->diffInDays($now)));

            $age_gt_60 = $liveGroup->filter(fn($b) => abs(Carbon::parse($b->created_at)->diffInDays($now)) > 60)->count();

            $on_hold = $liveGroup->where('status', 6)->count();

            $verify = $liveGroup->where('order', 1)->count();

            $orders = $liveGroup->where('order', 2)->whereNull('dms_so')->count();

            $payments = $liveGroup->filter(function ($b) {
                $paid = DB::table('xlr8_booking_amount')
                    ->where('bid', $b->id)
                    ->where('status', 1)
                    ->sum('amount');
                return $paid < ($b->booking_amount ?? 0);
            })->count();

            $customer_data = $liveGroup->where('pending', '>', 0)->count();

            $refund = $groupBookings->where('status', 4)->count();

            $dummy_bookings = $groupBookings->where('b_type', 'dummy')->count();

            $stock_group = $stocks->get($groupKey, []);

            $gridData[] = [
                'sno'                 => $sno++,
                'seg'                 => $seg,
                'model'               => $model,
                'variant'             => $variant,
                'clr'                 => $clr,

                // STOCK – yeh already perfect hai
                'stock_total'         => array_sum($stock_group),
                'stock_bikaner'       => $stock_group['BIKANER'] ?? 0,
                'stock_churu'         => $stock_group['CHURU'] ?? 0,
                'stock_khajuwala'     => $stock_group['KHAJUWALA'] ?? 0,
                'stock_kolayat'       => $stock_group['KOLAYAT'] ?? 0,
                'stock_lunkaransar'   => $stock_group['LUNKARANSAR'] ?? 0,
                'stock_other'         => array_sum(array_diff_key($stock_group, array_flip(['BIKANER', 'CHURU', 'KHAJUWALA', 'KOLAYAT', 'LUNKARANSAR']))),

                // Bookings count – yeh bhi sahi hai
                'total_bookings'      => $total_bookings,

                // GLOBAL INFO – yeh sab sahi se aa rahe hain
                'max_age' => $max_age_days ? ceil($max_age_days) . ' D' : '0 D',
                'age_gt_60d'          => $age_gt_60,
                'dummy_bookings'      => $dummy_bookings,
                'on_hold'             => $on_hold,
                'refund'              => $refund,

                // PENDING ACTIONS – yeh bhi sahi hai
                'order_verification'  => $verify,
                'order_creation'      => $orders,
                'booking_creation'    => $total_bookings,
                'customer_payment'    => $payments,
                'customer_data'       => $customer_data,
                'book_canc'           => 0,  // agar cancellation status alag se track hota hai to yahan add kar dena

                // Abhi ke liye zero – real data ke liye comment daal raha hoon
                'hot_enq_total'       => 0,     // future: enquiry_master se hot/high intent enquiries count karna
                'hot_enq_bikaner'     => 0,
                'hot_enq_churu'       => 0,

                'finance_total'       => 0,     // future: finance table join ya finance_status flag se count
                'finance_bikaner'     => 0,
                'finance_churu'       => 0,
                'finance_pending'     => $payments,   // abhi payment pending ko finance pending maan rahe hain (temporary)

                'exchange_total'      => 0,     // future: exchange table ya exchange_status se count
                'exchange_bikaner'    => 0,
                'exchange_churu'      => 0,
                'exchange_pending'    => 0,

                'lie_orders'          => 0,     // future: lost interest enquiry ya cancelled due to interest loss
            ];
        }

        // ────────────────────────────────────────────────
        // Columns – EXACTLY photo jaisa (sab groups + sub-columns)
        // ────────────────────────────────────────────────
        $columns = [
            ['field' => 'sno', 'headerName' => 'S.No.', 'width' => 70, 'pinned' => 'left'],

            [
                'headerName' => 'Vehicle Info',
                'headerClass' => 'group-vehicle-info',
                'children' => [
                    ['field' => 'seg', 'headerName' => 'Segment', 'width' => 140],
                    ['field' => 'model', 'headerName' => 'Model', 'width' => 180],
                    ['field' => 'variant', 'headerName' => 'Variant', 'width' => 240],
                    ['field' => 'clr', 'headerName' => 'Color', 'width' => 140],
                ]
            ],

            [
                'headerName' => 'STOCK',
                'headerClass' => 'group-stock',
                'children' => [
                    ['field' => 'stock_total', 'headerName' => 'TOTAL', 'width' => 90, 'cellClass' => 'text-right'],
                    ['field' => 'stock_bikaner', 'headerName' => 'BIKANER', 'width' => 90, 'cellClass' => 'text-right'],
                    ['field' => 'stock_churu', 'headerName' => 'CHURU', 'width' => 90, 'cellClass' => 'text-right'],
                ]
            ],

            [
                'headerName' => 'SELECTED BRANCH LOCATIONS',
                'headerClass' => 'group-selected-branch',
                'children' => [
                    ['field' => 'stock_bikaner', 'headerName' => 'BIKANER', 'width' => 90, 'cellClass' => 'text-right'],
                    ['field' => 'stock_khajuwala', 'headerName' => 'KHAJUWALA', 'width' => 100, 'cellClass' => 'text-right'],
                    ['field' => 'stock_kolayat', 'headerName' => 'KOLAYAT', 'width' => 90, 'cellClass' => 'text-right'],
                    ['field' => 'stock_lunkaransar', 'headerName' => 'LUNKARANSAR', 'width' => 110, 'cellClass' => 'text-right'],
                    ['field' => 'stock_other', 'headerName' => 'OTHER', 'width' => 90, 'cellClass' => 'text-right'],
                    ['field' => 'stock_churu', 'headerName' => 'CHURU', 'width' => 90, 'cellClass' => 'text-right'],
                ]
            ],

            [
                'headerName' => 'HOT ENQ',
                'headerClass' => 'group-hot-enq',
                'children' => [
                    ['field' => 'hot_enq_total',   'headerName' => 'TOTAL',    'width' => 90, 'cellClass' => 'text-right'],
                    ['field' => 'hot_enq_bikaner', 'headerName' => 'BIKANER',  'width' => 90, 'cellClass' => 'text-right'],
                    ['field' => 'hot_enq_churu',   'headerName' => 'CHURU',    'width' => 90, 'cellClass' => 'text-right'],
                ],
            ],

            [
                'headerName' => 'INT IN FINANCE',
                'headerClass' => 'group-finance',
                'children' => [
                    ['field' => 'finance_total',     'headerName' => 'TOTAL',         'width' => 90, 'cellClass' => 'text-right'],
                    ['field' => 'finance_bikaner',   'headerName' => 'BIKANER',       'width' => 90, 'cellClass' => 'text-right'],
                    ['field' => 'finance_churu',     'headerName' => 'CHURU',         'width' => 90, 'cellClass' => 'text-right'],
                    ['field' => 'finance_pending',   'headerName' => 'PENDING ACTION', 'width' => 130, 'cellClass' => 'text-right fw-bold'],
                ],
            ],

            [
                'headerName' => 'INT IN EXCH',
                'headerClass' => 'group-exchange',
                'children' => [
                    ['field' => 'exchange_total',    'headerName' => 'TOTAL',         'width' => 90, 'cellClass' => 'text-right'],
                    ['field' => 'exchange_bikaner',  'headerName' => 'BIKANER',       'width' => 90, 'cellClass' => 'text-right'],
                    ['field' => 'exchange_churu',    'headerName' => 'CHURU',         'width' => 90, 'cellClass' => 'text-right'],
                    ['field' => 'exchange_pending',  'headerName' => 'PENDING ACTION', 'width' => 130, 'cellClass' => 'text-right fw-bold'],
                ],
            ],

            [
                'headerName' => 'GLOBAL INFO',
                'headerClass' => 'group-global',
                'children' => [
                    ['field' => 'max_age', 'headerName' => 'MAX AGE', 'width' => 100],
                    ['field' => 'age_gt_60d', 'headerName' => 'AGE > 60D', 'width' => 110, 'cellClass' => 'text-right'],
                    ['field' => 'lie_orders', 'headerName' => 'LIE ORDERS', 'width' => 100, 'cellClass' => 'text-right'],
                    ['field' => 'dummy_bookings', 'headerName' => 'DUMMY BOOKINGS', 'width' => 140, 'cellClass' => 'text-right'],
                    ['field' => 'on_hold', 'headerName' => 'ON HOLD', 'width' => 100, 'cellClass' => 'text-right'],
                    ['field' => 'refund', 'headerName' => 'REFUND', 'width' => 90, 'cellClass' => 'text-right'],
                ]
            ],

            [
                'headerName' => 'PENDING ACTIONS',
                'headerClass' => 'group-pending',
                'children' => [
                    ['field' => 'order_verification', 'headerName' => 'ORDER VERIFICATION', 'width' => 140, 'cellClass' => 'text-right'],
                    ['field' => 'order_creation', 'headerName' => 'ORDER CREATION', 'width' => 140, 'cellClass' => 'text-right'],
                    ['field' => 'booking_creation', 'headerName' => 'BOOKING CREATION', 'width' => 140, 'cellClass' => 'text-right'],
                    ['field' => 'customer_payment', 'headerName' => 'CUSTOMER PAMENT', 'width' => 140, 'cellClass' => 'text-right'],
                    ['field' => 'customer_data', 'headerName' => 'CUSTOMER DATA', 'width' => 140, 'cellClass' => 'text-right'],
                    ['field' => 'book_canc', 'headerName' => 'BOOK CANC.', 'width' => 120, 'cellClass' => 'text-right'],
                ]
            ],
        ];

        $gridConfig = [
            'columns' => $columns,
            'data' => $gridData,
        ];

        $this->data['gridConfig'] = $gridConfig;

        if (empty($gridData)) {
            session()->flash('info', 'No branch booking data found.');
        }

        return view('booking.branch-booking', $this->data);
    }

    public function pendingActionsReport(Request $request)
    {
        $this->crud->hasAccessOrFail('list');

        $this->data['crud'] = $this->crud;
        $this->data['title'] = 'Pending Actions Report';

        // Quick check if any pending records exist
        $pendingCount = DB::table('xlr8_booking_master')->whereIn('status', [1, 6, 8])->count();
        if ($pendingCount === 0) {
            session()->flash('info', 'No pending bookings found (status 1,6,8 not present in table).');
        }

        $query = DB::table('xlr8_booking_master as b')
            ->leftJoin('xlr8_vehicle_master as vm', 'b.vh_id', '=', 'vm.id')
            ->leftJoin('bmpl_enum_master as em', 'vm.segment_id', '=', 'em.id')
            ->leftJoin('xlr8_us_location as loc', 'b.location_code', '=', 'loc.id')
            ->whereIn('b.status', [1, 6, 8])
            ->selectRaw('
            COALESCE(em.value, "Unknown") as segment,
            COALESCE(vm.oem_model, "Unknown") as model,
            COALESCE(vm.oem_variant, "Unknown") as variant,
            COALESCE(vm.color, "Unknown") as color,
            COUNT(b.id) as total_bookings,
            SUM(CASE WHEN b.order = 1 THEN 1 ELSE 0 END) as order_verif,
            SUM(CASE WHEN b.order = 2 AND b.dms_so IS NULL THEN 1 ELSE 0 END) as order_creation,
            SUM(CASE WHEN b.pending > 0 THEN 1 ELSE 0 END) as customer_data,
            SUM(CASE WHEN b.status = 4 THEN 1 ELSE 0 END) as refund,
            SUM(CASE WHEN b.status = 4 THEN 1 ELSE 0 END) as book_canc,
            loc.abbr as branch_abbr
        ')
            ->groupByRaw('em.value, vm.oem_model, vm.oem_variant, vm.color, loc.abbr');

        $pendings = $query->get();

        // ────────────────────────────────────────────────
        // Group by vehicle + branch counts
        // ────────────────────────────────────────────────
        $gridData = [];
        $grouped = $pendings->groupBy(function ($item) {
            return $item->segment . '|' . $item->model . '|' . $item->variant . '|' . $item->color;
        });

        $sno = 1;

        foreach ($grouped as $groupKey => $rows) {
            [$seg, $model, $variant, $clr] = explode('|', $groupKey);

            $total_bookings = $rows->sum('total_bookings');

            if ($total_bookings == 0) continue;

            $bkn = $rows->where('branch_abbr', 'BKN')->sum('total_bookings');
            $chr = $rows->where('branch_abbr', 'CHR')->sum('total_bookings');

            $gridData[] = [
                'sno' => $sno++,
                'segment' => $seg,
                'model' => $model,
                'variant' => $variant,
                'color' => $clr,
                'total_bookings' => (int)$total_bookings,
                'bkn' => (int)$bkn,
                'chr' => (int)$chr,
                'exchange' => 0,
                'finance' => 0,
                'order_verif' => (int)$rows->sum('order_verif'),
                'order_creation' => (int)$rows->sum('order_creation'),
                'booking_creation' => (int)$total_bookings,
                'customer_payment' => 0,
                'kyc_data' => (int)$rows->sum('customer_data'),
                'book_canc' => (int)$rows->sum('book_canc'),
                'refund' => (int)$rows->sum('refund'),
            ];
        }

        // ────────────────────────────────────────────────
        // Columns (your blade already expects these)
        // ────────────────────────────────────────────────
        $columns = [
            ['field' => 'sno', 'headerName' => 'S.No.', 'width' => 70, 'pinned' => 'left'],

            [
                'headerName' => 'Vehicle Info',
                'headerClass' => 'group-vehicle-info',
                'children' => [
                    ['field' => 'segment', 'headerName' => 'Segment', 'width' => 140],
                    ['field' => 'model', 'headerName' => 'Model', 'width' => 180],
                    ['field' => 'variant', 'headerName' => 'Variant', 'width' => 240],
                    ['field' => 'color', 'headerName' => 'Color', 'width' => 140],
                ]
            ],

            [
                'headerName' => 'Bookings',
                'headerClass' => 'group-booking',
                'children' => [
                    ['field' => 'total_bookings', 'headerName' => 'Total', 'width' => 90, 'cellClass' => 'text-right'],
                    ['field' => 'bkn', 'headerName' => 'BKN', 'width' => 70, 'cellClass' => 'text-right'],
                    ['field' => 'chr', 'headerName' => 'CHR', 'width' => 70, 'cellClass' => 'text-right'],
                    ['field' => 'exchange', 'headerName' => 'EXCHANGE', 'width' => 110, 'cellClass' => 'text-right'],
                    ['field' => 'finance', 'headerName' => 'FINANCE', 'width' => 110, 'cellClass' => 'text-right'],
                ]
            ],

            [
                'headerName' => 'PENDING ACTIONS',
                'headerClass' => 'group-pending',
                'children' => [
                    ['field' => 'order_verif', 'headerName' => 'ORDER VERIFICATION', 'width' => 160, 'cellClass' => 'text-right'],
                    ['field' => 'order_creation', 'headerName' => 'ORDER CREATION', 'width' => 140, 'cellClass' => 'text-right'],
                    ['field' => 'booking_creation', 'headerName' => 'BOOKING CREATION', 'width' => 160, 'cellClass' => 'text-right'],
                    ['field' => 'customer_payment', 'headerName' => 'CUSTOMER PAYMENT', 'width' => 160, 'cellClass' => 'text-right'],
                    ['field' => 'kyc_data', 'headerName' => 'KYC DATA', 'width' => 110, 'cellClass' => 'text-right'],
                    ['field' => 'book_canc', 'headerName' => 'BOOK CANC.', 'width' => 110, 'cellClass' => 'text-right'],
                    ['field' => 'refund', 'headerName' => 'REFUND', 'width' => 110, 'cellClass' => 'text-right'],
                ]
            ],
        ];

        $gridConfig = [
            'columns' => $columns,
            'data' => $gridData,
        ];

        $this->data['gridConfig'] = $gridConfig;

        return view('booking.pending-actions', $this->data);
    }
    public function checkFieldPayment($id)
    {
        $booking = Booking::findOrFail($id);

        if (!in_array($booking->col_type, [2, 3])) {
            return response()->json(['success' => true]); // not field collection → always allow
        }

        $totalPaid = Bookingamount::where('bid', $booking->id)->sum('amount') ?? 0;

        if ($booking->booking_amount > $totalPaid) {
            return response()->json([
                'success'    => false,
                'total_paid' => (float) $totalPaid,
                'message'    => 'Insufficient payment for field collection booking'
            ]);
        }

        return response()->json(['success' => true]);
    }
}
