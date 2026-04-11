<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermissionTo('manage_bookings');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // ============================================================
            // PAYMENT DETAILS VALIDATION
            // ============================================================
            'customer_type' => 'required|in:Active,Dummy',
            'customer_cat' => 'required|in:Individual,CSD,Firm',
            'booking_date' => 'required|date_format:d-m-Y|before_or_equal:today',
            'hidden_booking_date' => 'nullable',
            'col_type' => 'required|in:1,2,3,4',
            'booking_amount' => 'required|numeric|min:0',
            'receipt_no' => 'required|string|max:50',
            'receipt_date' => 'required|date_format:d-m-Y|before_or_equal:today',
            'hidden_receipt_date' => 'nullable',
            'amount_proof' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',

            // ============================================================
            // CUSTOMER DETAILS VALIDATION
            // ============================================================
            'name' => 'required|string|max:100',
            'mobile' => 'required|digits:10|unique:bookings,mobile',
            'alt_mobile' => 'nullable|digits:10',
            'customer_dob' => 'nullable|date_format:d-m-Y',
            'hidden_customer_dob' => 'nullable',
            'customer_age' => 'nullable|numeric|min:18|max:100',
            'gender' => 'nullable|in:Male,Female,Other',
            'occupation' => 'nullable|string|max:50',
            'pan_no' => 'nullable|string|regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/i|unique:bookings,pan_no',
            'aadhar_no' => 'nullable|digits:12|unique:bookings,aadhar_no',
            'gstn' => 'nullable|string|regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/',
            'not_required_gst' => 'nullable|boolean',
            'care_of' => 'nullable|in:Father,Mother,Spouse,Other',
            'care_of_name' => 'nullable|string|max:100',

            // ============================================================
            // REFERRED BY DETAILS VALIDATION
            // ============================================================
            'referred_by' => 'nullable|in:Existing Customer,DSA,Walk In,Other',
            'ref_customer_name' => 'nullable|string|max:100',
            'ref_mobile_no' => 'nullable|digits:10',
            'ref_existing_model' => 'nullable|string|max:100',
            'ref_variant' => 'nullable|string|max:100',
            'ref_chassis_reg_no' => 'nullable|string|max:100',
            'dsa_details' => 'nullable|exists:xl_dsa_masters,id',

            // ============================================================
            // PURCHASE TYPE DETAILS VALIDATION
            // ============================================================
            'booking_mode' => 'required|in:New,Exchange,Used Car Purchase',
            'segment_id' => 'required_if:booking_mode,New|nullable|exists:segments,id',
            'model' => 'required_if:booking_mode,New|nullable|exists:models,id',
            'variant' => 'required_if:booking_mode,New|nullable|exists:variants,id',
            'color' => 'nullable|exists:colors,id',
            'seating' => 'nullable|numeric|min:1',
            'manufacturing_year' => 'nullable|string|max:10',
            'accessories' => 'nullable|array',
            'accessories.*' => 'nullable|exists:accessories,id',
            'apack_amount' => 'nullable|numeric|min:0',
            'expected_price' => 'required_if:booking_mode,New|nullable|numeric|min:0',
            'offered_price' => 'nullable|numeric|min:0',
            'difference' => 'nullable|numeric',
            'exchange_bonus' => 'nullable|numeric|min:0',
            'make_order' => 'nullable|boolean',
            'expected_del_date' => 'nullable|date_format:d-m-Y',
            'hidden_expected_del_date' => 'nullable',

            // ---- Exchange / Used Car Purchase Fields ----
            'vh_id' => 'nullable|string|max:50',
            'registration_no' => 'nullable|string|max:50|unique:bookings,registration_no',
            'chassis' => 'nullable|string|max:50|unique:bookings,chassis',
            'odometer_reading' => 'nullable|numeric|min:0',

            // ============================================================
            // FINANCE DETAILS VALIDATION
            // ============================================================
            'fin_mode' => 'required|in:Cash,Finance',
            'financier' => 'required_if:fin_mode,Finance|nullable|exists:xl_financiers,id',
            'financier_short_name' => 'nullable|string|max:20',
            'loan_status' => 'nullable|in:Pending,Approved,Rejected',

            // ============================================================
            // BOOKING TYPE & SOURCE VALIDATION
            // ============================================================
            'booking_source' => 'nullable|in:Showroom,Call,Website,Social Media,Walk In,Other',
            'location' => 'required|exists:branches,id',
            'location_other' => 'nullable|string|max:100',
            'delivery_type' => 'nullable|in:Home Delivery,Showroom Pickup,Customer Pickup',
            'saleconsultant' => 'nullable|exists:users,id',
            'user' => 'nullable|exists:users,id',

            // ============================================================
            // ADDITIONAL DETAILS VALIDATION
            // ============================================================
            'buyer_type' => 'nullable|in:Individual,Corporate',
            'refrence_no' => 'nullable|string|max:50|unique:bookings,refrence_no',
            'details' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Customer name is required',
            'mobile.required' => 'Mobile number is required',
            'mobile.digits' => 'Mobile number must be 10 digits',
            'mobile.unique' => 'This mobile number is already registered',
            'booking_date.required' => 'Booking date is required',
            'booking_date.date_format' => 'Booking date format should be dd-mmm-yyyy',
            'booking_amount.required' => 'Booking amount is required',
            'receipt_no.required' => 'Receipt/Voucher number is required',
            'receipt_date.required' => 'Receipt date is required',
            'col_type.required' => 'Collection type is required',
            'customer_type.required' => 'Customer type is required',
            'customer_cat.required' => 'Customer category is required',
            'booking_mode.required' => 'Booking mode is required',
            'fin_mode.required' => 'Finance mode is required',
            'location.required' => 'Branch/Location is required',
            'segment_id.required_if' => 'Segment is required for new vehicle booking',
            'model.required_if' => 'Model is required for new vehicle booking',
            'variant.required_if' => 'Variant is required for new vehicle booking',
            'expected_price.required_if' => 'Expected price is required for new vehicle booking',
            'financier.required_if' => 'Financier is required for finance mode',
            'pan_no.regex' => 'PAN number format is invalid',
            'aadhar_no.digits' => 'Aadhar number must be 12 digits',
            'gstn.regex' => 'GST number format is invalid',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert date formats if needed
        if ($this->has('booking_date') && $this->booking_date) {
            $this->merge([
                'booking_date' => $this->formatDateForValidation($this->booking_date),
            ]);
        }

        if ($this->has('receipt_date') && $this->receipt_date) {
            $this->merge([
                'receipt_date' => $this->formatDateForValidation($this->receipt_date),
            ]);
        }

        if ($this->has('customer_dob') && $this->customer_dob) {
            $this->merge([
                'customer_dob' => $this->formatDateForValidation($this->customer_dob),
            ]);
        }

        if ($this->has('expected_del_date') && $this->expected_del_date) {
            $this->merge([
                'expected_del_date' => $this->formatDateForValidation($this->expected_del_date),
            ]);
        }
    }

    /**
     * Format date from frontend format to validation format
     */
    private function formatDateForValidation($date)
    {
        // Assuming frontend sends in dd-mmm-yyyy format
        // Convert to d-m-Y for validation
        if ($date && strlen($date) > 0) {
            return \Carbon\Carbon::createFromFormat('d-m-Y', $date)->format('d-m-Y');
        }
        return $date;
    }
}
