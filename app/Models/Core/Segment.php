<?php

namespace App\Models\Core;

use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Segment extends BaseModel
{
    use CrudTrait;
    use HasFactory;
    protected $fillable = ['brand_id', 'name', 'code', 'description', 'is_active'];

    public $translatable = ['name', 'description'];

    protected $columnTransformations = [
        'code' => 'uppercase_alphanumeric_dash',
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function subSegments()
    {
        return $this->hasMany(SubSegment::class);
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
