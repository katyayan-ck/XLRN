<?php

namespace App\Models\Core;

use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubSegment extends BaseModel
{
    use CrudTrait;
    use HasFactory;
    protected $fillable = ['segment_id', 'name', 'code', 'description', 'is_active'];

    public $translatable = ['name', 'description'];

    protected $columnTransformations = [
        'code' => 'uppercase_alphanumeric_dash',
    ];

    public function segment()
    {
        return $this->belongsTo(Segment::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function vehicleModels()
    {
        return $this->hasMany(VehicleModel::class);
    }

    public function variants()
    {
        return $this->hasMany(Variant::class);
    }
}
