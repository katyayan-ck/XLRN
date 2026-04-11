<?php

namespace App\Models\Core;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Process extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $fillable = ['module_id', 'name', 'code', 'description', 'is_active'];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }
}
