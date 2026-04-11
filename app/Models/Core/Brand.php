<?php

namespace App\Models\Core;

use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Brand extends BaseModel
{
    use CrudTrait;
    use HasFactory;
    protected $fillable = ['name', 'code', 'description', 'is_active'];
    protected string $scopeType = 'brand';
    //public $translatable = ['name', 'description'];

    protected $columnTransformations = [
        'code' => 'uppercase_alphanumeric_dash',
    ];

    public function segments()
    {
        return $this->hasMany(Segment::class);
    }
}
