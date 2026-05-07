<?php
namespace App\Models\Vehicle;

use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class SubSegment extends BaseModel
{
    use CrudTrait;

    protected $table = 'xlr8_vehicle_subsegment';

    protected $fillable = ['brand_code', 'segment_code', 'code', 'name', 'description', 'is_active', 'created_by', 'updated_by', 'deleted_by'];

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

    public function vehicleModels()
    {
        return $this->hasMany(VehicleModel::class, 'sub_segment_code', 'code');
    }

    public function variants()
    {
        return $this->hasMany(Variant::class, 'sub_segment_code', 'code');
    }

    /**
     * XUV → XUV | NON XUV → NXUV
     */
    public static function generateCode(string $name): string
    {
        $map = [
            'XUV'     => 'XUV',
            'NON XUV' => 'NXUV',
            'NON-XUV' => 'NXUV',
        ];
        $upper = strtoupper(trim($name));
        return $map[$upper] ?? strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 5));
    }
}
