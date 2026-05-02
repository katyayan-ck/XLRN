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
