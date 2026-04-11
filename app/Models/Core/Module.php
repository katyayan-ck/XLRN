<?php

namespace App\Models\Core;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;

class Module extends BaseModel
{
    use CrudTrait;
    use HasFactory;
    protected $fillable = ['name', 'code', 'description', 'is_active'];

    public function processes()
    {
        return $this->hasMany(Process::class);
    }
}
