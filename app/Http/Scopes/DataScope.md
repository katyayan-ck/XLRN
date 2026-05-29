# ✅ Data Scoping Developer Guide (Final Version)

This is a complete, practical, and developer-friendly guide for implementing and using the Data Scoping system in the application.

---

# 1. Overview

The Data Scoping system automatically restricts data visibility based on the logged-in user's access rights stored in:

```text
xlr8_admin_user_scopes
```

Instead of manually writing repetitive `WHERE` conditions throughout the application, the system automatically filters queries using the user's assigned scopes such as:

- Branch
- Location
- Department
- Division
- Vertical
- Segment
- Model
- etc.

---

# Key Features

- Automatically applies both Primary + Addon scopes
- Supports multiple scope dimensions on a single model
- Easy to completely disable scoping
- Easy to apply only selected scopes
- Fully respects `bypass_data_scoping`
- Centralized and reusable architecture
- Cleaner controllers and services
- Safer default behavior

---

# 2. Core Components

| Component             | Purpose                                  | Location                              |
| --------------------- | ---------------------------------------- | ------------------------------------- |
| `ScopedQuery` trait   | Enables automatic data scoping on models | `app/Models/Traits/ScopedQuery.php`   |
| `DataScopeFilter`     | Global query scope that filters records  | `app/Http/Scopes/DataScopeFilter.php` |
| `DataScopeService`    | Fetches allowed scope codes for users    | `app/Services/DataScopeService.php`   |
| `bypass_data_scoping` | User-level bypass flag                   | `users` table                         |

---

# 3. Enabling Automatic Data Scoping

To enable automatic data filtering on a model:

1. Add the `ScopedQuery` trait
2. Define the `$dataScopes` mapping array

---

## Example Model Configuration

```php
<?php

namespace App\Models\Admin;

use App\Models\BaseModel;
use App\Models\Traits\ScopedQuery;

class Employee extends BaseModel
{
    use ScopedQuery;

    /**
     * Key   = scope_type stored in user scopes table
     * Value = database column used for filtering
     */
    public array $dataScopes = [

        'branch'     => 'primary_branch_code',

        'location'   => 'primary_loc_code',

        'department' => 'primary_dept_code',

        'division'   => 'primary_div_code',

        'vertical'   => 'vertical_code',

        'segment'    => 'segment_code',

        'model'      => 'model_code',
    ];
}
```

---

# Important Note

Each model can define:

- Only the scopes it needs
- Any number of scopes
- Different database columns per scope type

This makes the system highly flexible.

---

# 4. Basic Usage (Automatic Filtering)

Once the trait is added, all queries are automatically scoped.

---

## Simple Query

```php
$employees = Employee::where(
    'is_active',
    true
)->get();
```

---

## Relationship Query

```php
$employees = Employee::with(
    'person',
    'designation'
)->get();
```

---

## Pagination

```php
$employees = Employee::paginate(20);
```

---

# Result

All records returned are automatically filtered according to the logged-in user's allowed scopes.

---

# 5. Disabling Data Scoping

---

## A. Disable Scoping for a Single Query

This is the most common bypass approach.

```php
$allEmployees = Employee::withoutDataScope()->get();
```

---

## Common Use Cases

- Reports
- Exports
- Analytics
- Admin dashboards
- Background jobs

---

## Example

```php
$report = Sale::withoutDataScope()
    ->where('status', 'completed')
    ->get();
```

---

## B. Disable for Super Admins

```php
public function index()
{
    $user = backpack_user();

    if (
        $user->bypass_data_scoping
        ||
        $user->isSuperAdmin()
    ) {

        return Employee::withoutDataScope()
            ->paginate(20);
    }

    return Employee::paginate(20);
}
```

---

# 6. Selective / Partial Scoping

You can apply only specific scope dimensions instead of all configured scopes.

---

## Example: Branch + Division Only

```php
$employees = Employee::applyDataScopes([
    'branch',
    'division'
])->get();
```

---

## Example: Segment Only

```php
$vehicles = Vehicle::applyDataScopes([
    'segment'
])->get();
```

---

## Example: Multiple Custom Filters

```php
$data = Sale::applyDataScopes([
    'branch',
    'department'
])->get();
```

---

# 7. Using DataScopeService Directly

Sometimes manual access checking is required.

Use `DataScopeService` directly in such cases.

---

## Initialize Service

```php
$service = app(
    \App\Services\DataScopeService::class
);

$user = auth()->user();
```

---

## Get Allowed Codes

### Single Scope Type

```php
$branchCodes = $service->getCodes(
    $user,
    'branch'
);
```

---

### Multiple Scope Types

```php
$codes = $service->getMultipleCodes(
    $user,
    [
        'branch',
        'department',
        'division'
    ]
);
```

---

## Check Access

```php
if (
    $service->hasAccess(
        $user,
        'branch',
        'BKN'
    )
) {

    // User has access
}
```

---

# 8. Real-World Use Cases

---

## Use Case 1: Standard CRUD Listing

```php
protected function setupListOperation()
{
    // No extra code needed.
    // Automatic scoping is applied.
}
```

---

## Use Case 2: Reports & Exports

```php
public function exportMonthlyReport()
{
    $data = Sale::withoutDataScope()
        ->whereBetween(
            'created_at',
            [$fromDate, $toDate]
        )
        ->get();

    // Generate Excel...
}
```

---

## Use Case 3: Dashboard with Partial Scoping

```php
public function dashboard()
{
    $employees = Employee::applyDataScopes([
            'branch',
            'department'
        ])
        ->with('designation')
        ->get();

    return view(
        'dashboard',
        compact('employees')
    );
}
```

---

## Use Case 4: Manual Access Validation

```php
public function approveLeave($leaveId)
{
    $leave = Leave::findOrFail($leaveId);

    $user = auth()->user();

    $service = app(
        DataScopeService::class
    );

    if (
        !$service->hasAccess(
            $user,
            'branch',
            $leave->branch_code
        )
    ) {

        abort(
            403,
            'You are not authorized to approve this leave.'
        );
    }

    // Proceed with approval
}
```

---

## Use Case 5: Super Admin Listing

```php
public function adminEmployeeList()
{
    $user = backpack_user();

    if (
        $user->bypass_data_scoping
        ||
        $user->isSuperAdmin()
    ) {

        return Employee::withoutDataScope()
            ->paginate(50);
    }

    return Employee::paginate(50);
}
```

---

# 9. Best Practices

| Situation         | Recommended Approach     | Reason                    |
| ----------------- | ------------------------ | ------------------------- |
| Normal CRUD       | Use model directly       | Automatic filtering       |
| Reports / Exports | Use `withoutDataScope()` | Need complete data        |
| Super Admin views | Check bypass flag        | Full visibility           |
| Partial filtering | Use `applyDataScopes()`  | Explicit control          |
| Heavy queries     | Cache allowed codes      | Better performance        |
| New models        | Add `ScopedQuery` trait  | Consistent implementation |

---

# 10. Quick Reference Cheat Sheet

---

## Automatic Scoping

```php
Employee::all();

Employee::where(
    'is_active',
    true
)->get();
```

---

## Disable All Scoping

```php
Employee::withoutDataScope()->get();
```

---

## Apply Specific Scopes

```php
Employee::applyDataScopes([
    'branch',
    'division'
])->get();
```

---

## Get Allowed Codes

```php
$service->getCodes(
    $user,
    'branch'
);

$service->getMultipleCodes(
    $user,
    [
        'branch',
        'department'
    ]
);
```

---

## Check Access

```php
$service->hasAccess(
    $user,
    'branch',
    'BKN'
);
```

---

## User Bypass Flag

```php
$user->bypass_data_scoping = true;
```

---

# 11. Summary Table

| Feature                   | Usage                       |
| ------------------------- | --------------------------- |
| Enable automatic scoping  | Add `use ScopedQuery;`      |
| Define scope columns      | Configure `$dataScopes`     |
| Disable scoping           | `Model::withoutDataScope()` |
| Apply selected scopes     | `->applyDataScopes([...])`  |
| Super admin bypass        | `bypass_data_scoping`       |
| Multiple scopes per model | Fully supported             |

---

# Final Summary

The Data Scoping system provides:

- Strong security by default
- Flexible filtering
- Clean architecture
- Centralized access control
- Minimal repetitive query logic
- Easy scalability for future modules

All new models and modules should use this standardized approach wherever data access restrictions are required.
