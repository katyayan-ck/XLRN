<?php

namespace App\Models\Core;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use CrudTrait;
    use HasFactory;
    // Add relations
    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function process()
    {
        return $this->belongsTo(Process::class);
    }
}
