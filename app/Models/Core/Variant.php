<?php

namespace App\Models\Core;

use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Helpers\KeywordHelper;

class Variant extends BaseModel
{
    use CrudTrait;
    use HasFactory;
    protected $fillable = [
        'brand_id',
        'segment_id',
        'sub_segment_id',
        'vehicle_model_id',
        'name',
        'custom_name',
        'oem_code',
        'description',
        'permit_id',
        'fuel_type_id',
        'seating_capacity',
        'wheels',
        'gvw',
        'cc_capacity',
        'body_type_id',
        'body_make_id',
        'is_csd',
        'csd_index',
        'status_id',
        'is_active'
    ];

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

    public function vehicleModel()
    {
        return $this->belongsTo(VehicleModel::class);
    }

    public function colors()
    {
        return $this->belongsToMany(Color::class, 'variant_colors');
    }
    public function permit()
    {
        return $this->belongsTo(Keyvalue::class, 'permit_id');
    }

    public function fuelType()
    {
        return $this->belongsTo(Keyvalue::class, 'fuel_type_id');
    }

    public function bodyType()
    {
        return $this->belongsTo(Keyvalue::class, 'body_type_id');
    }

    public function bodyMake()
    {
        return $this->belongsTo(Keyvalue::class, 'body_make_id');
    }

    public function statusKkv()
    {
        return $this->belongsTo(Keyvalue::class, 'status_id');
    }
    // Get options from helper
    public static function getPermitOptions(): array
    {
        return KeywordHelper::options('permit');
    }

    public static function getFuelTypeOptions(): array
    {
        return KeywordHelper::options('fuel_type');
    }

    public static function getBodyTypeOptions(): array
    {
        return KeywordHelper::options('body_type');
    }

    public static function getBodyMakeOptions(): array
    {
        return KeywordHelper::options('body_make');
    }

    public static function getStatusOptions(): array
    {
        return KeywordHelper::options('vehicle_status');
    }
}
