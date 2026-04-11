<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * User Data Scope Model
 * 
 * Represents the scope of data a user can access
 * 
 * Example:
 * user_id: 1, scope_type: 'branch', scope_value: 5
 * = User 1 can access Branch 5
 * 
 * user_id: 1, scope_type: 'branch', scope_value: null
 * = User 1 can access ALL branches (wildcard)
 */
class ExportLog extends Model
{
    use SoftDeletes;

    protected $table = 'export_logs';
}
