<?php

namespace App\Models\Core;

use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Color extends BaseModel
{
    use CrudTrait;
    use HasFactory;
    protected $fillable = ['brand_id', 'segment_id', 'sub_segment_id', 'vehicle_model_id', 'name', 'code', 'hex_code', 'image', 'description', 'is_active'];
    protected string $scopeType = 'color';
    //public $translatable = ['name', 'description'];

    protected $columnTransformations = [
        'code' => 'uppercase_alphanumeric_dash',
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function segment()
    {
        return $this->belongsTo(Segment::class);
    }

    public function subSegment()
    {
        return $this->belongsTo(SubSegment::class);
    }

    public function vehicleModel()
    {
        return $this->belongsTo(VehicleModel::class);
    }

    public function variants()
    {
        return $this->belongsToMany(Variant::class, 'variant_colors');
    }
}
