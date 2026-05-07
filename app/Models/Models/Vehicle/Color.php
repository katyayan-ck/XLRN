<?php
namespace App\Models\Vehicle;

use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class Color extends BaseModel
{
    use CrudTrait;

    protected $table = 'xlr8_vehicle_color';

    protected $fillable = [
        'brand_code', 'segment_code', 'sub_segment_code', 'model_code',
        'code', 'name', 'hex_code', 'image', 'description',
        'is_active', 'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_code', 'code');
    }

    public function segment()
    {
        return $this->belongsTo(Segment::class, 'segment_code', 'code');
    }

    public function subSegment()
    {
        return $this->belongsTo(SubSegment::class, 'sub_segment_code', 'code');
    }

    public function vehicleModel()
    {
        return $this->belongsTo(VehicleModel::class, 'model_code', 'code');
    }

    public function variants()
    {
        return $this->belongsToMany(Variant::class, 'variant_colors');
    }

    /**
     * Extract color.code from vehicle_info.model_code (last 2 chars)
     * "BM12AH515MB01D00JD" → "JD"
     */
    public static function codeFromModelCode(string $vehicleInfoModelCode): string
    {
        return strtoupper(substr(trim($vehicleInfoModelCode), -2));
    }
}
