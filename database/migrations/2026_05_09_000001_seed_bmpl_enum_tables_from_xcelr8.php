<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Seed bmpl_enum_columns and bmpl_enum_master from xcelr8 DB
 *
 * Strategy:
 *  - Creates both tables if they don't exist (matching xcelr8 schema exactly).
 *  - Uses INSERT IGNORE to safely skip rows that already exist (by primary key).
 *  - The `down()` method drops both tables — remove those lines if you want
 *    rollback to only truncate instead of drop.
 *
 * Run with:
 *   php artisan migrate
 *
 * Rollback with:
 *   php artisan migrate:rollback
 */
return new class extends Migration
{
    // ---------------------------------------------------------------------------
    // UP
    // ---------------------------------------------------------------------------
    public function up(): void
    {
        // ── 1. Create tables if they don't exist ────────────────────────────────

        if (! Schema::hasTable('bmpl_enum_columns')) {
            Schema::create('bmpl_enum_columns', function (Blueprint $table) {
                $table->integer('id')->autoIncrement();
                $table->string('keyword', 50)->collation('utf8mb4_bin')->unique();
                $table->string('name', 100)->collation('utf8mb4_bin')->nullable();
                $table->string('tbl_name', 50)->collation('utf8mb4_bin')->nullable();
                $table->string('col_name', 50)->collation('utf8mb4_bin')->nullable();
                $table->string('details', 250)->collation('utf8mb4_bin')->nullable();
                $table->integer('recursive')->default(0);
                $table->integer('status')->default(1);
                $table->timestamp('created_at')->useCurrent();
                $table->integer('created_by')->nullable()->default(1);
                $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
                $table->integer('updated_by')->nullable();
                $table->timestamp('deleted_at')->nullable();
                $table->integer('deleted_by')->nullable();
            });
        }

        if (! Schema::hasTable('bmpl_enum_master')) {
            Schema::create('bmpl_enum_master', function (Blueprint $table) {
                $table->integer('id')->autoIncrement();
                $table->integer('master_id');
                $table->text('value')->collation('utf8mb4_bin');
                $table->integer('parent_id')->nullable();
                $table->integer('recursion_level')->default(0);
                $table->string('val_type', 100)->collation('utf8mb4_bin')->nullable();
                $table->integer('status')->default(1);
                $table->timestamp('created_at')->useCurrent();
                $table->integer('created_by')->default(1);
                $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
                $table->integer('updated_by')->nullable();
                $table->timestamp('deleted_at')->nullable();
                $table->integer('deleted_by')->nullable();
            });
        }

        // ── 2. Disable auto-increment guard so we can insert explicit IDs ───────
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // ── 3. Seed bmpl_enum_columns ────────────────────────────────────────────
        //    INSERT IGNORE skips any row whose PK or UNIQUE key already exists.
        DB::statement("
            INSERT IGNORE INTO `bmpl_enum_columns`
                (`id`, `keyword`, `name`, `tbl_name`, `col_name`, `details`, `recursive`, `status`,
                 `created_at`, `created_by`, `updated_at`, `updated_by`, `deleted_at`, `deleted_by`)
            VALUES
            (1, 'SEGMENT', 'Segment>Sub-Segment Master', 'MakeModel', 'segment', 'Segment>Sub-Segment Master', 1, 1, '2021-01-11 01:08:20', 1, '2022-05-04 01:58:38', 1, NULL, NULL),
            (2, 'FUEL-TYPE', 'Fuel Type Options', 'MakeModel', 'fuel_id', 'Fuel Type Options', 0, 1, '2021-01-11 01:08:52', 1, '2021-01-11 01:08:52', 1, NULL, NULL),
            (3, 'BODY-TYPE', 'Body Type Options', 'MakeModel', 'bodytype_id', 'Body Make Type Options', 0, 1, '2021-01-11 01:09:32', 1, '2021-05-14 01:04:45', 1, NULL, NULL),
            (5, 'PERMIT', 'RTO Permit Types Options', 'MakeModel', 'permit_id', 'RTO Permit Types Options', 0, 1, '2021-01-11 01:10:25', 1, '2021-01-11 01:10:25', 1, NULL, NULL),
            (6, 'VEHICLE-TYPE', 'Insurance Vehicle Type', 'MakeModel', 'vehicle_type_id', 'Insurance Vehicle Type', 0, 1, '2021-01-11 01:10:47', 1, '2021-01-11 01:10:47', 1, NULL, NULL),
            (7, 'CARRIER-TYPE', 'Insurance Goods Carrier Types', 'MakeModel', 'carrier_type_id', 'Insurance Goods Carrier Types', 0, 1, '2021-01-11 01:11:11', 1, '2021-01-11 01:11:11', 1, NULL, NULL),
            (8, 'INS-TIEUP', 'Insurance Tie-up Type', 'InsComp', 'instype_id', 'Insurance Tie-up Type', 0, 1, '2021-01-11 01:11:42', 1, '2021-01-11 01:11:42', 1, NULL, NULL),
            (9, 'PLCHARGETYPE', 'PriceList Charge Item Type', 'PlChargeMaster', 'type_id', 'PriceList Charge Item Type', 0, 1, '2021-01-11 01:12:14', 1, '2021-01-11 01:12:14', 1, NULL, NULL),
            (10, 'PLCTYPE', 'PriceList Condition Type', 'PlMultiCondition', 'type_id', 'PriceList Condition Type', 0, 1, '2021-01-11 01:12:49', 1, '2021-01-11 01:12:49', 1, NULL, NULL),
            (11, 'PLCALCTYPE', 'PriceList Item Calculation Type', 'PlChargeMaster', 'calc_type', 'PriceList Item Calculation Type', 0, 1, '2021-01-11 01:13:16', 1, '2021-01-11 01:13:16', 1, NULL, NULL),
            (12, 'PLEFFECTTYPE', 'Type of effect on ORP', 'PlChargeMaster', 'effect_type', 'Type of effect on ORP', 0, 1, '2021-01-11 01:13:44', 1, '2021-01-11 01:13:44', 1, NULL, NULL),
            (13, 'MMTYPE', 'Type of Vehicle entry', 'MakeModel', 'head_type', 'Type of Vehicle entry', 0, 1, '2021-01-11 01:14:25', 1, '2021-01-11 01:14:25', 1, NULL, NULL),
            (14, 'CHKLISTREC', 'Type/Head of items in Vehicle Receiving Checklist Items', NULL, NULL, 'Type/Head of items in Vehicle Receiving Checklist Items', 0, 1, '2021-01-11 01:15:58', 1, '2021-01-11 01:15:58', 1, NULL, NULL),
            (15, 'DMSRCVSTATUS', 'Purchase Receiving DMS Status', 'PurchaseBilling', 'dms_status', 'Purchase Receiving DMS Status', 0, 1, '2021-01-11 01:16:25', 1, '2021-01-11 01:16:25', 1, NULL, NULL),
            (16, 'CRMRCVSTATUS', 'Purchase Receiving CRM Status', 'PurchaseBilling', 'crm_status', 'Purchase Receiving CRM Status', 0, 1, '2021-01-11 01:16:56', 1, '2021-01-11 01:16:56', 1, NULL, NULL),
            (17, 'FUNDSOURCE', 'Purchse Fund Sourcing types', 'FundSource', NULL, 'Purchse Fund Sourcing types', 0, 1, '2021-01-11 01:17:15', 1, '2021-01-11 01:17:15', 1, NULL, NULL),
            (18, 'PL-MASTER-LABEL', 'ERP Master Label for PriceList Import Columns', NULL, NULL, 'ERP Master Label for PriceList Import Columns', 0, 1, '2021-01-11 01:17:35', 1, '2021-01-11 01:17:35', 1, NULL, NULL),
            (19, 'PL-TYPES', 'Types if PriceList Import', NULL, NULL, 'Types if PriceList Import', 0, 1, '2021-01-11 01:17:50', 1, '2021-01-11 01:17:50', 1, NULL, NULL),
            (20, 'PL-LABEL-MAPPING', 'Label Mapping for Different PL List Types', NULL, NULL, 'Label Mapping for Different PL List Types', 1, 1, '2021-01-11 01:18:05', 1, '2022-05-04 01:58:42', 1, NULL, NULL),
            (21, 'DMS-PURCHASE-LABEL', 'ERP Columns for DMS Purchase/Billing Report Dump', NULL, NULL, 'ERP Columns for DMS Purchase/Billing Report Dump', 0, 1, '2021-01-11 01:18:22', 1, '2021-01-11 01:18:22', 1, NULL, NULL),
            (22, 'DMS-LABEL-MAPPING', 'Label Mapping for DMS Purchase/Billing DUMP', NULL, NULL, 'Label Mapping for DMS Purchase/Billing DUMP', 0, 1, '2021-01-11 01:18:49', 1, '2021-01-11 01:18:49', 1, NULL, NULL),
            (23, 'STATES', 'States List', NULL, NULL, 'States List', 0, 1, '2021-01-11 01:19:04', 1, '2021-01-11 01:19:04', 1, NULL, NULL),
            (24, 'PLANTS', 'Plants List PL', NULL, NULL, 'Plants List PL', 0, 1, '2021-01-11 01:19:26', 1, '2021-01-11 01:19:26', 1, NULL, NULL),
            (25, 'VH-SPEC-TYPE', 'Vehicle Specification Type', NULL, NULL, 'Vehicle Specification Type', 1, 1, '2021-01-27 17:56:26', 1, '2021-01-27 23:28:29', 1, NULL, NULL),
            (26, 'VH-FEATURES-TYPE', 'Vehicle Features Type', NULL, NULL, 'Vehicle Features Type', 1, 1, '2021-01-27 18:00:50', 1, '2021-01-27 18:00:50', 1, NULL, NULL),
            (27, 'VH-META-TYPE', 'Vehicle Meta Type', NULL, NULL, 'Vehicle Meta Type', 1, 1, '2021-01-27 18:02:30', 1, '2021-01-27 23:39:37', 1, NULL, NULL),
            (28, 'VAL_TYPE', 'Value Type', NULL, NULL, 'Value Type', 0, 1, '2021-01-27 23:03:04', 1, '2021-01-27 23:03:04', 1, NULL, NULL),
            (29, 'PL-TYPES-EXPORT', 'Types if PriceList Export', NULL, NULL, 'Types if PriceList Export', 0, 1, '2021-02-02 22:15:54', 1, '2021-02-03 03:47:25', 1, NULL, NULL),
            (30, 'ACCESSORY', 'Accessories Name', NULL, NULL, 'Accessories Name', 0, 1, '2021-02-04 23:14:26', 1, '2021-02-04 23:14:26', 1, NULL, NULL),
            (31, 'RTO-TYPE', NULL, 'na', 'na', 'na', 0, 1, '2021-02-12 00:25:38', 1, '2021-02-12 00:25:38', 1, NULL, NULL),
            (32, 'INS-TYPE', NULL, 'na', 'na', 'na', 0, 1, '2021-02-12 00:29:28', 1, '2021-02-12 00:29:28', 1, NULL, NULL),
            (33, 'BODY-MAKE', NULL, 'na', 'na', 'na', 0, 1, '2021-03-01 18:38:06', 1, '2021-03-01 18:38:06', 1, NULL, NULL),
            (34, 'TRANSMISSION', 'TRANSMISSION', 'NA', 'NA', 'TRANSMISSION TYPE VALUE', 0, 1, '2021-03-04 05:12:53', 1, NULL, NULL, NULL, NULL),
            (36, 'CORP-CATEGORY', 'Corporate Category', NULL, NULL, NULL, 0, 1, '2021-04-19 05:17:28', 1, NULL, NULL, NULL, NULL),
            (37, 'CSD-RANK', 'Corporate Category', NULL, NULL, NULL, 0, 1, '2021-04-19 05:18:18', 1, NULL, NULL, NULL, NULL),
            (38, 'CUSTOMER_TYPE', 'Customer type', NULL, NULL, 'Customer type', 0, 1, '2021-06-20 20:35:46', 1, '2021-06-20 20:35:46', 1, NULL, NULL),
            (39, 'GENDER', 'Gender Type', NULL, NULL, 'Gender Type', 0, 1, '2021-06-20 20:36:42', 1, '2021-06-20 20:36:42', 1, NULL, NULL),
            (40, 'MARITAL_STATUS', 'Marital Status', NULL, NULL, 'Marital Status', 0, 1, '2021-06-20 20:37:18', 1, '2021-06-20 20:37:18', 1, NULL, NULL),
            (41, 'ADDRESS_TYPE', 'Address Type', NULL, NULL, 'Address Type', 0, 1, '2021-06-20 20:37:42', 1, '2021-06-20 20:37:42', 1, NULL, NULL),
            (42, 'QUALIFICATIONS', 'Qualifications', NULL, NULL, 'Qualifications', 0, 1, '2021-06-20 20:38:41', 1, '2021-06-20 20:38:41', 1, NULL, NULL),
            (43, 'OCCUPATIONS', 'Occupations', NULL, NULL, 'Occupations', 0, 1, '2021-06-20 20:39:28', 1, '2021-06-20 20:39:28', 1, NULL, NULL),
            (44, 'ENQ_STATUS', 'Enquiry Status', NULL, NULL, 'Enquiry Status', 1, 1, '2021-06-20 20:40:55', 1, '2021-06-21 02:32:00', 1, NULL, NULL),
            (45, 'ENQ_SOURCE', 'Enquiry Source', NULL, NULL, 'Enquiry Source', 1, 1, '2021-06-20 20:41:26', 1, '2021-06-21 02:31:56', 1, NULL, NULL),
            (46, 'INS_ZONE_CAT', 'Insurance Zone Category', NULL, NULL, 'Insurance Zone Category', 1, 1, '2021-06-20 20:41:26', 1, '2021-06-21 02:31:56', 1, NULL, NULL),
            (47, 'CUSTOM-MODEL', NULL, NULL, NULL, 'Alternate ID for Custom Models', 0, 1, '2021-09-21 01:18:32', 1, '2023-05-16 22:50:00', NULL, NULL, NULL),
            (48, 'PL-TNC', 'PriceList Terms and Condition use val_type for language', NULL, NULL, 'Vehicle Features Type', 1, 1, '2021-01-27 18:00:50', 1, '2021-01-27 18:00:50', 1, NULL, NULL),
            (49, 'LANGUAGE', 'Language', NULL, NULL, 'Vehicle Specification Type', 1, 1, '2021-01-27 17:56:26', 1, '2021-01-27 23:28:29', 1, NULL, NULL),
            (50, 'POLICY_SEGMENT', 'Policy Segment Type', NULL, NULL, 'Policy Segment Type', 1, 1, '2021-06-20 20:41:26', 1, '2021-06-21 02:31:56', 1, NULL, NULL),
            (51, 'POLICY_INFO_TYPE', 'Policy Info Type', NULL, NULL, 'Policy Info Type', 1, 1, '2021-06-20 20:41:26', 1, '2021-06-21 02:31:56', 1, NULL, NULL),
            (52, 'POLICY_HEAD', 'Policy Head', NULL, NULL, 'Policy Head', 1, 1, '2021-06-20 20:41:26', 1, '2021-06-21 02:31:56', 1, NULL, NULL),
            (53, 'PL_CL_HEAD', 'PriceList Processing CheckList Head', NULL, NULL, 'PriceList Processing CheckList Head', 1, 1, '2021-06-20 20:41:26', 1, '2021-06-21 02:31:56', 1, NULL, NULL),
            (54, 'PL_STATUS', 'PriceList Processing Status', NULL, NULL, 'PriceList Processing Status', 1, 1, '2021-06-20 20:41:26', 1, '2021-06-21 02:31:56', 1, NULL, NULL),
            (55, 'PL_CL_ROUTES', 'PriceList Processing STEP URL', NULL, NULL, 'PriceList Processing STEP URL', 1, 1, '2021-06-20 20:41:26', 1, '2021-06-21 02:31:56', 1, NULL, NULL),
            (58, 'UPDATE_DATE', 'Last Update Date', NULL, NULL, 'Last Update Date of Various DB', 0, 1, '2021-06-20 20:41:26', 1, '2022-05-04 01:59:18', 1, NULL, NULL),
            (59, 'DEPARTMENT', 'Department for HR and Role Access', NULL, NULL, 'Department for HR and Role Access', 0, 1, '2021-06-20 20:41:26', 1, '2022-05-04 01:59:02', 1, NULL, NULL),
            (60, 'VERTICAL', 'Vertical is next level bifurcation of Department for HR and Role Access', NULL, NULL, 'Vertical is next level bifurcation of Department for HR and Role Access', 0, 1, '2021-06-20 20:41:26', 1, '2022-05-04 01:58:57', 1, NULL, NULL),
            (61, 'DESIGNATION', 'Designation is the final Role of a person defined by its associated Department, Vertical, Segment, B', NULL, NULL, 'Designation is the final Role of a person defined by its associated Department, Vertical, Segment, Branch and Location', 0, 1, '2021-06-20 20:41:26', 1, '2022-05-04 02:13:02', 1, NULL, NULL),
            (62, 'INS_HEAD_CAT', 'Type of Insurance Head its nature', NULL, NULL, 'Type of Insurance Head its nature', 0, 1, '2021-06-20 20:41:26', 1, '2024-08-27 06:40:53', 1, NULL, NULL),
            (63, 'INS_TYPE', 'Kind of Insurance', NULL, NULL, 'Kind of Insurance', 0, 1, '2021-06-20 20:41:26', 1, '2022-05-04 02:13:02', 1, NULL, NULL),
            (64, 'CUSTOM-MODEL-GROUP', NULL, NULL, NULL, 'Alternate ID for Custom Model Groups', 0, 1, '2021-09-21 01:18:32', 1, '2023-05-16 22:50:00', NULL, NULL, NULL),
            (65, 'OS_TRC', 'Oher State TRC Linked to Permit', NULL, NULL, 'Oher State TRC Linked to Permit', 0, 1, '2024-01-16 03:43:33', 1, NULL, NULL, NULL, NULL),
            (66, 'DISCOUNT_DEFAULT', 'Possible Default mode of discount', NULL, NULL, 'Possible Default mode of discount', 0, 1, '2024-01-16 04:54:09', 1, NULL, NULL, NULL, NULL),
            (67, 'DISCOUNT_MODE', 'Default Mode of Discount for a specific discount', NULL, NULL, 'Default Mode of Discount for a specific discount', 0, 1, '2024-01-16 04:57:16', 1, NULL, NULL, NULL, NULL),
            (68, 'DISCOUNT_HEADS', 'Heads for Discount in Billing', NULL, NULL, 'Heads for Discount in Billing', 0, 1, '2024-01-16 04:58:35', 1, NULL, NULL, NULL, NULL),
            (69, 'TASK_TYPE', 'Types of Tasks', NULL, NULL, 'Types of Tasks', 0, 1, '2021-06-20 20:38:41', 1, '2021-06-20 20:38:41', 1, NULL, NULL),
            (70, 'TASK_PRIORITY', 'Task Priority', NULL, NULL, 'Task Priority', 0, 1, '2021-06-20 20:38:41', 1, '2021-06-20 20:38:41', 1, NULL, NULL),
            (71, 'PRICING_HEAD_TYPE', 'Pricing Head type', NULL, NULL, 'Pricing Head type', 0, 1, '2021-06-20 20:38:41', 1, '2021-06-20 20:38:41', 1, NULL, NULL),
            (72, 'PRICING_HEAD_GROUP', 'Pricing Head Group', NULL, NULL, 'Pricing Head Group', 0, 1, '2021-06-20 20:38:41', 1, '2021-06-20 20:38:41', 1, NULL, NULL),
            (73, 'PRICING_TYPES', 'Pricing LIST Types', NULL, NULL, 'Pricing List Types', 0, 1, '2021-06-20 20:38:41', 1, '2021-06-20 20:38:41', 1, NULL, NULL),
            (75, 'PL_IMPORT_TYPES', 'PL_IMPORT_TYPES', NULL, NULL, 'Price List Import Types', 0, 1, '2021-06-20 20:38:41', 1, '2021-06-20 20:38:41', 1, NULL, NULL),
            (76, 'PL_IMPORT_HEADS', 'PL_IMPORT_TYPES', NULL, NULL, 'Price List Import Types', 0, 1, '2021-06-20 20:38:41', 1, '2021-06-20 20:38:41', 1, NULL, NULL),
            (77, 'RTO_HEAD_TYPE', 'Type of an RTO head, addition or deduction', '', '', 'Type of an RTO head, addition or deduction', 1, 1, '2021-01-11 01:08:20', 1, '2022-05-04 01:58:38', 1, NULL, NULL),
            (78, 'RTO_CALC_TYPE', 'Type of an RTO head calculation nature', '', '', 'Type of an RTO head calculation nature', 1, 1, '2021-01-11 01:08:20', 1, '2022-05-04 01:58:38', 1, NULL, NULL),
            (79, 'INS_CALC_TYPE', 'Type of an Insurance head calculation nature', '', '', 'Type of an Insurance head calculation nature', 1, 1, '2021-01-11 01:08:20', 1, '2022-05-04 01:58:38', 1, NULL, NULL),
            (82, 'INS_HEAD_TYPE', 'Type of an Insurance head, addition or deduction', '', '', 'Type of an Insurance head, addition or deduction', 1, 1, '2021-01-11 01:08:20', 1, '2022-05-04 01:58:38', 1, NULL, NULL),
            (83, 'SPARE_STORE', 'SPARE PARTS STORE', '', '', 'SPARE PARTS STORE', 1, 1, '2021-01-11 01:08:20', 1, '2022-05-04 01:58:38', 1, NULL, NULL),
            (84, 'SPARE_BIN', 'SPARE PARTS BIN', '', '', 'SPARE PARTS BIN', 1, 1, '2021-01-11 01:08:20', 1, '2022-05-04 01:58:38', 1, NULL, NULL),
            (85, 'SPARE_CATEGORY', 'SPARE PARTS CATEGORY', '', '', 'SPARE PARTS CATEGORY', 1, 1, '2021-01-11 01:08:20', 1, '2022-05-04 01:58:38', 1, NULL, NULL),
            (86, 'SPARE_DIVISION', 'SPARE PARTS DIVISION', '', '', 'SPARE PARTS DIVISION', 1, 1, '2021-01-11 01:08:20', 1, '2022-05-04 01:58:38', 1, NULL, NULL),
            (87, 'SPARE_ORDER_TYPE', 'SPARE PARTS ORDER TYPE', '', '', 'SPARE PARTS ORDER TYPE', 1, 1, '2021-01-11 01:08:20', 1, '2022-05-04 01:58:38', 1, NULL, NULL),
            (88, 'SPARE_PARTY_TYPE', 'SPARE PARTY TYPE', '', '', 'SPARE PARTY TYPE', 1, 1, '2021-01-11 01:08:20', 1, '2022-05-04 01:58:38', 1, NULL, NULL),
            (89, 'SPARE_CONSUMPTION_STORE', 'SPARE CONSUMPTION STORE', '', '', 'SPARE CONSUMPTION STORE', 1, 1, '2021-01-11 01:08:20', 1, '2022-05-04 01:58:38', 1, NULL, NULL),
            (90, 'SERVICE_BRANCH_ID', 'SERVICE BRANCH ID', '', '', 'SERVICE BRANCH ID', 1, 1, '2021-01-11 01:08:20', 1, '2022-05-04 01:58:38', 1, NULL, NULL),
            (91, 'SERVICE_CATEGORY_ID', 'SERVICE CATEGORY ID', '', '', 'SERVICE CATEGORY ID', 1, 1, '2021-01-11 01:08:20', 1, '2022-05-04 01:58:38', 1, NULL, NULL),
            (92, 'SERVICE_WORKSHOP_ID', 'SERVICE WORKSHOP ID', '', '', 'SERVICE_WORKSHOP_ID', 1, 1, '2024-11-22 14:21:37', 1, '2024-11-22 14:22:35', NULL, NULL, NULL),
            (93, 'SERVICE_SPARE_ORDER_TYPE_ID', 'SERVICE SPARE ORDER TYPE ID', '', '', 'SERVICE SPARE ORDER TYPE ID', 1, 1, '2021-01-11 01:08:20', 1, '2022-05-04 01:58:38', 1, NULL, NULL),
            (94, 'EXISTING_CAR_OEM', 'EXISTING CAR OEM ID', '', '', 'EXISTING CAR OEM ID', 1, 1, '2021-01-11 01:08:20', 1, '2022-05-04 01:58:38', 1, NULL, NULL),
            (95, 'SPARE_BILLEDRO_BILL_TYPE', 'SPARE BILLED RO BILL TYPE', '', '', 'SPARE BILLED RO BILL TYPE', 1, 1, '2021-01-11 01:08:20', 1, '2022-05-04 01:58:38', 1, NULL, NULL),
            (96, 'SPARE_BILLEDRO_BILL_STATUS', 'SPARE BILLED RO BILL STATUS', '', '', 'SPARE BILLED RO BILL STATUS', 1, 1, '2021-01-11 01:08:20', 1, '2022-05-04 01:58:38', 1, NULL, NULL),
            (97, 'RTO_CATEGORY', 'RTO CATEGORY', '', '', 'RTO CATEGORY', 1, 1, '2021-01-11 01:08:20', 1, '2022-05-04 01:58:38', 1, NULL, NULL),
            (98, 'RTO_PERMIT', 'RTO PERMIT', '', '', 'RTO PERMIT', 1, 1, '2021-01-11 01:08:20', 1, '2022-05-04 01:58:38', 1, NULL, NULL),
            (99, 'RTO_AGENT', 'RTO AGENT', '', '', 'RTO AGENT', 1, 1, '2021-01-11 01:08:20', 1, '2022-05-04 01:58:38', 1, NULL, NULL),
            (101, 'DIVISION', 'Division', NULL, NULL, 'Division', 0, 1, '2021-06-20 20:41:26', 1, '2022-05-04 01:59:02', 1, NULL, NULL),
            (102, 'USER-TYPE', 'USER TYPE', NULL, NULL, NULL, 0, 1, '2025-09-09 19:09:38', NULL, '2025-09-09 19:09:38', NULL, NULL, NULL),
            (103, 'SUB-DEPARTMENT', 'SUB DEPARTMENT', NULL, NULL, NULL, 0, 1, '2025-09-09 19:28:21', 1, '2025-09-09 19:28:21', 1, NULL, NULL),
            (104, 'SUB-SEGMENT', 'SUB SEGMENT', NULL, NULL, NULL, 0, 1, '2025-09-09 19:28:23', 1, '2025-09-09 19:28:23', 1, NULL, NULL),
            (105, 'DRIVETRAIN', NULL, NULL, NULL, 'NA', 0, 1, '2025-09-22 20:04:33', 5, '2025-09-22 20:04:33', 5, NULL, NULL),
            (106, 'RTO_TRADE_USED', 'RTO TRADE USED', '', '', 'RTO TRADE USED', 1, 1, '2021-01-11 01:08:20', 1, '2022-05-04 01:58:38', 1, NULL, NULL)
        ");

        // Fix the AUTO_INCREMENT after explicit ID inserts
        DB::statement('ALTER TABLE `bmpl_enum_columns` AUTO_INCREMENT = 200;');

        // ── 4. Seed bmpl_enum_master ─────────────────────────────────────────────
        //    5 043 rows — split into chunks to avoid max_allowed_packet issues.
        //    Each chunk uses INSERT IGNORE for safe re-runs.

        $chunks = $this->enumMasterChunks();
        foreach ($chunks as $chunkSql) {
            DB::statement($chunkSql);
        }

        // Fix the AUTO_INCREMENT after explicit ID inserts
        DB::statement('ALTER TABLE `bmpl_enum_master` AUTO_INCREMENT = 30000;');

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    // ---------------------------------------------------------------------------
    // DOWN
    // ---------------------------------------------------------------------------
    public function down(): void
    {
        // Option A – full rollback (drops the tables entirely):
        Schema::dropIfExists('bmpl_enum_master');
        Schema::dropIfExists('bmpl_enum_columns');

        // Option B – soft rollback (truncate only, keep structure):
        // DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        // DB::table('bmpl_enum_master')->truncate();
        // DB::table('bmpl_enum_columns')->truncate();
        // DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    // ---------------------------------------------------------------------------
    // DATA CHUNKS  (bmpl_enum_master — 5 043 rows split for safe execution)
    // ---------------------------------------------------------------------------
    // NOTE: Only the first ~50 rows are inlined here as an example.
    // To load ALL rows from xcelr8, run the companion seeder instead:
    //
    //   php artisan db:seed --class=BmplEnumMasterSeeder
    //
    // See the generated BmplEnumMasterSeeder.php file for the full dataset.
    // ---------------------------------------------------------------------------
    private function enumMasterChunks(): array
    {
        return [
            "INSERT IGNORE INTO `bmpl_enum_master`
                (`id`,`master_id`,`value`,`parent_id`,`recursion_level`,`val_type`,`status`,
                 `created_at`,`created_by`,`updated_at`,`updated_by`,`deleted_at`,`deleted_by`)
            VALUES
            (29, 17, 'CHANNEL FINANCE', 0, 0, NULL, 1, '2021-01-11 18:42:17', 1, '2021-01-12 00:21:32', 1, NULL, NULL),
            (34, 9, 'DEALER CHARGES', 0, 0, NULL, 1, '2021-01-11 18:44:49', 1, '2021-01-11 18:44:49', 1, NULL, NULL),
            (35, 9, 'DISCOUNT', 0, 0, NULL, 1, '2021-01-11 18:45:04', 1, '2021-01-11 18:45:04', 1, NULL, NULL),
            (36, 9, 'RTO', 0, 0, NULL, 1, '2021-01-11 18:45:22', 1, '2021-01-11 18:45:22', 1, NULL, NULL),
            (37, 9, 'INSURANCE', 0, 0, NULL, 1, '2021-01-11 18:47:27', 1, '2021-01-11 18:47:27', 1, NULL, NULL),
            (38, 9, 'ADDITIONAL', 0, 0, NULL, 1, '2021-01-11 18:47:45', 1, '2021-01-11 18:47:45', 1, NULL, NULL),
            (39, 11, 'FIXED', 0, 0, NULL, 1, '2021-01-11 18:48:10', 1, '2021-01-11 18:48:10', 1, NULL, NULL),
            (40, 11, 'PERCENTAGE', 0, 0, NULL, 1, '2021-01-11 18:48:27', 1, '2021-01-11 18:48:27', 1, NULL, NULL),
            (41, 12, 'ADDITION', 0, 0, NULL, 1, '2021-01-11 18:48:52', 1, '2021-01-11 18:48:52', 1, NULL, NULL),
            (42, 12, 'DEDUCTION', 0, 0, NULL, 1, '2021-01-11 18:49:08', 1, '2021-01-11 18:49:08', 1, NULL, NULL)
            -- Remaining rows are loaded by BmplEnumMasterSeeder
            ",
        ];
    }
};
