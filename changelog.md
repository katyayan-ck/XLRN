## [Sprint 1] — 2026-05-02

### Added

- Extended `xlr8_iam_roles` with 14 Post columns (`post_code`, `display_name`,
  `is_post`, `branch_code`, `loc_code`, `dept_code`, `div_code`, `desig_code`,
  `tree_code`, `seq_no`, `max_occupants`, `is_active`, `metadata`) + 4 audit
  columns (`deleted_at`, `created_by`, `updated_by`, `deleted_by`) + 10 indexes
- New table: `xlr8_admin_desig_dept_tree` — canonical default reporting structure
- New table: `xlr8_iam_post_org_scopes` — per-post org data scope (branch/loc/dept/div/vertical)
- New table: `xlr8_iam_post_vehicle_scopes` — per-post vehicle scope (brand/segment/model/etc.)
- New table: `xlr8_admin_emp_post_assignments` — full HR journey log (replaces pivot)
- New table: `xlr8_iam_post_reporting` — temporal post-to-post reporting lines by topic
- New model: `Post` extends `Role` extends `SpatieRole` — discriminated by `is_post=true`
- New model: `DesigDeptTree` — default reporting fallback tree
- New model: `PostOrgScope` — org scope rows per post
- New model: `PostVehicleScope` — vehicle scope rows per post
- New model: `EmpPostAssignment` — HR journey with composable Eloquent scopes
- New model: `PostReporting` — temporal reporting lines with priority resolution scopes
- Updated: `Role` — added `scopePosts()`, `scopeSystemRoles()`
- Updated: `Designation` — added `rank`, `rank_label`, `is_top_mgmt`, `desig_code`
- Updated: `RoleCrudController` — system roles list now excludes Posts via scope
- Unit tests: `PostModelTest`, `EmpPostAssignmentTest`, `PostReportingTest`

### Removed (Legacy Tables)

- `xlr8_iam_post` — data migrated to `xlr8_iam_roles` with `is_post=true`
- `userdatascopes` / `user_data_scopes` — replaced by PostOrgScope + PostVehicleScope
- `graph_nodes`, `graph_edges` — removed (ReportingService replaces GraphTraversal)
- `reporting_hierarchies` — replaced by `xlr8_iam_post_reporting`
- `xlr8_admin_emp_vehicle_scope` — replaced by PostVehicleScope

### Architecture Decisions

- **Post-as-Role**: Posts stored in `xlr8_iam_roles` (is_post=true). Spatie upgrade-safe.
- **No SQL FKs**: All 7 migrations use indexes only. Eloquent enforces integrity.
- **6 audit columns**: Every new table has created/updated/deleted \_at/\_by.
- **Pure Eloquent**: All HR journey and reporting queries use named scopes — no raw SQL.

DB Fix:

- Drop all SQL FK-named indexes (project rule: no SQL FKs)
- Add project-standard idx\_ prefixed indexes to all org tables
- Add missing UNIQUE constraints (post_code, post_org_scopes, post_vehicle_scopes)
- Complete 6 audit columns on all pivot tables
- Add branch_code/dept_code/div_code/vert_code short-code columns
- Fix scope_value bigint → varchar(20) on xlr8_iam_user_data_scopes
- Drop xlr8_iam_emp_post_pivot (replaced by emp_post_assignments)
- Drop xlr8_iam_process (empty skeleton table)
- Back-fill short code columns from existing code values

Services:

- PostService: CRUD, scope sync, vacancy check, tree resolution
- HRJourneyService: onboard, transfer, promote, demote, additional charge, separate
- DataScopeService: org/vehicle scope resolution with wildcard handling
- ReportingService: 4-level priority resolution, chain traversal, line management

Tests: PostServiceTest, HRJourneyServiceTest, ReportingServiceTest
Changelog: updated"
New Tables Added

    xlr8admindesigdepttree — Designation-Department-Division tree with treecode, reportstocode, desigcode, deptcode, divcode, level, full audit columns and indexes

    xlr8adminemppostassignments — Full post assignment history with relievingtype, fromdate, todate, assignmenttype

    xlr8iampostreporting — Post-wise topic reporting with frompostcode, topostcode, topic, paramtype, paramvalue, priority

    xlr8iampostorgscopes — Org-level scoping per post with postcode, scopetype, scopevalue unique constraint

    xlr8iampostvehiclescopes — Vehicle-level scoping per post with postcode, scopetype, scopevalue

    xlr8iamuserdatascopes — User-wise data scoping with scopetype enum (branch, location, dept, division, vertical, brand, segment, subsegment, vehiclemodel, variant, color)

    xlr8iamuserrolepivot — User-role assignment with fromdate and composite unique key

    xlr8iamuserdivisionpivot — User-division assignment pivot

    xlr8iamemppostpivot — Employee-post link pivot with iscurrent, fromdate, todate

    variantcolors — Vehicle variant-color pivot with full audit columns

Existing Tables Modified (Sprint 3 refactors)

    xlr8adminbranch — Added branchcode (short code, varchar10), indexes updated

    xlr8admindesignation — Added desigcode, rank, category columns

    xlr8admindivision — Added divcode (varchar10)

    xlr8adminlocation — Now references branchcode instead of branchid

    xlr8adminemployee — Added primaryloccode, verticalcode, segmentcode, subsegmentcode, oemid, expanded statutory fields (PF, ESI, PT, LWF), shift fields, reportingempcode

    xlr8iamroles — Added postcode (unique), ispost, branchcode, loccode, desigcode, treecode, deptcode, divcode — Post-as-Role pattern fully implemented

    xlr8iampermissions — Added moduleid and processid FK columns

🧩 Features & Modules

Admin — Foundation Module

    Added ScopedCrud trait for Backpack controllers with applyDataScope(), wildcard handling, and hierarchy-based filtering

    All CRUD routes registered: approval-hierarchy, branch, department, designation, division, employee, all pivot assignments (branch, dept, location, post, vertical), location, vertical

IAM — Post & Scoping System

    Post-as-Role pattern complete: xlr8iamroles extended with postcode, org-level and vehicle-level scope tables created

    xlr8iamuserdatascopes fully structured with indexed query paths for scope-type + user lookups

    xlr8iampostreporting and xlr8iampostorgscopes in place for topic-wise reporting assignment

Vehicle System

    New normalized vehicle tables live: xlr8vehiclebrand, xlr8vehiclesegment, xlr8vehiclesubsegment, xlr8vehiclemodel, xlr8vehiclevariant, xlr8vehiclecolor, variantcolors

    CRUD routes registered for: brand, color, segment, sub-segment, variant, vehicle-model

    VehicleDefinitionImport pipeline referenced (via existing vehicledefinition enum in importlogs.importtype)

User Import/Export

    UserImportExportController with showImportForm, import, importHistory, downloadTemplate, showExportForm, export, exportHistory fully implemented

    importlogs and exportlogs tables tracking status, startedat, completedat, errors, warnings, importedcount, skippedcount

    Excel template auto-generator using PhpSpreadsheet

    Routes registered under admin/users prefix

System Settings API

    importSettings and exportSettings API endpoints added to SystemSettingApiController with sanctum guard

    Audit log written on every setting change via logAudit

🛣️ Routes Added (Sprint 3)
Route Name Method Controller
users.import GET/POST UserImportExportController
users.import.history GET UserImportExportController
users.import.template GET UserImportExportController
users.export GET/POST UserImportExportController
users.export.history GET UserImportExportController
api.settings.import.json POST SystemSettingApiController
api.settings.export.json GET SystemSettingApiController
Employee pivots (branch, dept, location, post, vertical) CRUD Multiple Controllers

🐛 Known Issues / Carry-Forward Items

    xlr8iamprocess table exists but has no columns beyond id and timestamps — process definition is incomplete

    xlr8iampost in old schema uses integer FKs (branchid, locationid), but new system uses postcode string pattern — migration reconciliation needed

    graphnodes and graphedges tables exist but no routes or services are wired to them

    xlr8adminbranch branchcode column added in Sprint 3 but old schema still uses plain code — backward compat check needed

Sprint 4 — Proposed Scope

Based on what's built, what's incomplete, and the original FRS order of implementation, Sprint 4 should focus on three tightly related layers.
🎯 Sprint 4 Goals

S4.1 — Complete IAM: Post, Permission & RBAC wiring

    Complete xlr8iamprocess table with name, code, module_id, description, is_active and link permissions properly

    Wire PostPermission → auto-assign permissions on Employee Post Assignment

    Build PostCrudController and PostPermissionCrudController routes (already registered but controllers need body)

    Implement auto-create Post for Branch Manager when Branch is created (Observer pattern)

    Implement auto-create Division General when Department is created

S4.2 — Data Scoping Enforcement

    Implement ScopedCrud full hierarchy support (branch → location cascade)

    Wire xlr8iamuserdatascopes auto-population from Post assignment

    Middleware/Policy to enforce scoping on API controllers

    SuperAdmin wildcard bypass (isSuperAdmin() check already stubbed in ScopedCrud)

S4.3 — Vehicle Definition Import (MaatWebsite Excel)

    Build VehicleDefinitionImport class using the existing vehicleinfo.csv format

    Wire into importlogs with importtype = vehicledefinition

    Auto-create Brand → Segment → SubSegment → Model → Variant → Color chain if not exists

    Add import route + form + history view (mirror User import pattern already built)

S4.4 — Accessory Import Service (carry forward from Sprint 3 discussion)

    Build AccessoryImportService + VehicleAccessoriesImport class

    Add vehicleaccessory to importtype enum via migration

    Wire controller, request, route, and CLI command

S4.5 — Schema Cleanup Migrations

    Reconcile xlr8iampost integer FK → postcode string pattern

    Add missing columns to xlr8iamprocess

    Backfill branchcode short-code on existing xlr8adminbranch rows

## [Sprint 5] — 2026-05-09

### Added / Enhanced

**Standalone Users Import (`StandaloneUsersImport.php`)**

- Full Person → Employee → User creation from `users_import` sheet
- Name splitting: `first_name`, `middle_name`, `last_name` from `Employee Name*`
- Creates/updates `xlr8_admin_person_addresses` and `xlr8_admin_person_banking_details`
- Creates/updates primary mobile + email in `xlr8_admin_person_contacts`
- Post assignment with automatic sequencing (`SLS_CNS_BKN` → `SLS_CNS_BKN_002` etc.)
- Full per-row console + log output with success/fail status

**Keyword / KeyValue System – Complete Modernization**

- Natural-key architecture: `KeywordMaster.code` + `Keyvalue.keyword_code + code`
- All codes forced to UPPERCASE on save and lookup
- Full `BaseModel` compliance (audit fields, soft deletes, `is_active`)
- Retained `KeywordMaster` for metadata (`is_active`, description, extra_data, tree settings)
- Updated `Keyvalue` with `HasTreeStructure` support
- `KeywordValueService` – backward compatible + new clean `getCode()` / `getByCode()` API

**Model Accessor Enhancements**

- `Person.php`: `first_name`, `middle_name`, `last_name`, `full_name`, `all_emails`, `all_mobiles`, `all_addresses`, `all_banking`
- `Employee.php`: Proxy accessors for primary/all emails, mobiles, addresses, banking
- `User.php`: Proxy accessors for primary/all emails, mobiles, addresses, banking
- Consistent access: `$user->primary_email`, `$employee->all_addresses`, `$person->primary_bank` etc.

**Unit Tests**

- `RBACPersonEmployeeUserTest.php` – comprehensive test suite covering KeywordValueService, Person, Employee, User models and all new accessors
- Uses `DatabaseTransactions` (safe on existing DB)

### Commands Executed

```bash
php artisan migrate
php artisan optimize:clear
php artisan test --filter=RBACPersonEmployeeUserTest
```
