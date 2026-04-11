<?php

namespace App\Models\Core;

use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VehicleModel extends BaseModel
{
    use CrudTrait;
    use HasFactory;
    protected $fillable = ['brand_id', 'segment_id', 'sub_segment_id', 'name', 'custom_name', 'oem_code', 'description', 'is_active'];

    // public $translatable = ['name', 'custom_name', 'description'];

    protected $columnTransformations = [
        'oem_code' => 'uppercase_alphanumeric_dash',
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

    public function variants()
    {
        return $this->hasMany(Variant::class);
    }
}
