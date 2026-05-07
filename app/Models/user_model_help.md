**✅ User Model Reference Guide (Full Documentation)**

```markdown
# User Model - Complete Reference & Usage Guide

**File:** `app/Models/User.php`

This model is the central authentication and RBAC entry point. It links to `person` and `employee` via natural keys.

---

### 1. Core Relations

| Method                  | Returns                  | Usage Example                                      | Description |
|-------------------------|--------------------------|----------------------------------------------------|-------------|
| `person()`             | `BelongsTo`              | `$user->person` or `$user->person()`              | Linked person record |
| `employee()`           | `BelongsTo`              | `$user->employee` or `$user->employee()`          | Linked employee record (null for non-Emp users) |

---

### 2. Primary Organisation Codes (Single Value)

| Method                        | Returns     | Usage Example                          | Description |
|-------------------------------|-------------|----------------------------------------|-------------|
| `primaryBranchCode()`        | `?string`   | `$user->primaryBranchCode()`           | Primary branch code |
| `primaryLocationCode()`      | `?string`   | `$user->primaryLocationCode()`         | Primary location code |
| `primaryDepartmentCode()`    | `?string`   | `$user->primaryDepartmentCode()`       | Primary department code |
| `primaryDivisionCode()`      | `?string`   | `$user->primaryDivisionCode()`         | Primary division code |

**Accessor shortcuts (magic properties):**
```php
$user->primary_branch_code
$user->primary_location_code
$user->primary_department_code
$user->primary_division_code
```

---

### 3. All Assigned Collections (Primary + Additional)

| Method               | Returns              | Usage Example                              | Description |
|----------------------|----------------------|--------------------------------------------|-------------|
| `branches()`        | `BelongsToMany`      | `$user->branches()->pluck('branch_code')` | All branches |
| `locations()`       | `BelongsToMany`      | `$user->locations()->pluck('code')`       | All locations |
| `departments()`     | `BelongsToMany`      | `$user->departments()->pluck('code')`     | All departments |
| `divisions()`       | `BelongsToMany`      | `$user->divisions()->pluck('code')`       | All divisions |

**Accessor shortcuts:**
```php
$user->all_branches      // Collection of branch_codes
$user->all_locations     // Collection of location codes
$user->all_departments   // Collection of dept codes
$user->all_divisions     // Collection of division codes
```

---

### 4. Post & Role Assignments

| Method               | Returns              | Usage Example                              | Description |
|----------------------|----------------------|--------------------------------------------|-------------|
| `posts()`           | `HasMany`            | `$user->posts()`                           | All currently assigned posts |
| `primaryPost()`     | `?string`            | `$user->primaryPost()`                     | Primary post_code (string) |
| `post()`            | `?Post`              | `$user->post()`                            | First active Post model |

---

### 5. Data Scopes (RBAC Scoping)

| Method                    | Returns     | Usage Example                                      | Description |
|---------------------------|-------------|----------------------------------------------------|-------------|
| `dataScopes()`           | `HasMany`   | `$user->dataScopes()`                              | Raw scopes |
| `getActiveScopes()`      | `array`     | `$user->getActiveScopes()`                         | Grouped active scopes |
| `hasScope($type, $value)`| `bool`      | `$user->hasScope('branch', 'BKN')`                 | Check access (true if wildcard or match) |

**Common usage:**
```php
// Check if user can access a specific branch
if ($user->hasScope('branch', 'BKN')) { ... }

// Get all accessible branches
$branches = $user->getActiveScopes()['branch'] ?? [];
```

---

### 6. Accessors (Magic Properties)

| Accessor                    | Returns     | Usage Example                     | Description |
|-----------------------------|-------------|-----------------------------------|-------------|
| `display_name`             | `string`    | `$user->display_name`            | Person display name or username |
| `primary_email`            | `?string`   | `$user->primary_email`           | Primary email from person |
| `primary_mobile`           | `?string`   | `$user->primary_mobile`          | Primary mobile from person |
| `official_email`           | `?string`   | `$user->official_email`          | Official email from employee |
| `official_mobile`          | `?string`   | `$user->official_mobile`         | Official mobile from employee |

---

### 7. Helper Methods

| Method                  | Returns     | Usage Example                          | Description |
|-------------------------|-------------|----------------------------------------|-------------|
| `isSuperAdmin()`       | `bool`      | `if ($user->isSuperAdmin())`          | Has super admin role |
| `isEmployee()`         | `bool`      | `if ($user->isEmployee())`            | Is Emp type with employee_code |

---

### 8. Query Scopes

| Scope                     | Usage Example                              | Description |
|---------------------------|--------------------------------------------|-------------|
| `active()`               | `User::active()->get()`                   | Active + not deleted |
| `employees()`            | `User::employees()->get()`                | Only Emp users |
| `search($term)`          | `User::search('ashraf')->get()`           | Search by username / person name |

---

### Quick Reference Examples

```php
$user = Auth::user();

// Basic info
echo $user->display_name;
echo $user->primary_email;

// Organisation
echo $user->primaryBranchCode();
$allBranches = $user->branches()->pluck('name');

// Posts
$primaryPost = $user->primaryPost();
$allPosts = $user->posts();

// Scopes
if ($user->hasScope('branch', 'BKN')) {
    // can access Bikaner data
}

// Full active scopes
$scopes = $user->getActiveScopes();
```

**All methods are chainable where appropriate and respect soft deletes.**

You can now safely use `$user->primary_branch`, `$user->post()`, `$user->hasScope(...)`, etc. in your controllers, policies, and views.

