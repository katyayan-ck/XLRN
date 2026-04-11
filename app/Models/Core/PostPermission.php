<?php

namespace App\Models\Core;

use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User; 

/**
 * PostPermission Model
 * 
 * Links permissions to posts for RBAC
 */
class PostPermission extends BaseModel
{
    use CrudTrait;
    use HasFactory;
    protected $table = 'post_permissions';

    protected $fillable = [
        'post_id',
        'permission_id',
        'granted_by',
        'granted_at',
    ];

    protected $casts = [
        'granted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship: Post
     */
    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Relationship: Permission
     */
    public function permission()
    {
        return $this->belongsTo(\Spatie\Permission\Models\Permission::class);
    }

    /**
     * Relationship: User who granted
     */
    public function grantedBy()
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}
