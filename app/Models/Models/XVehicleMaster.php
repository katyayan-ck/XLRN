<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class XVehicleMaster extends BaseModel
{
    use SoftDeletes;

    protected $table = 'xcelr8_vehicle_master';
    protected $fillable = [];
    protected $guarded = ['id'];
    protected $transformations = [
        'oem_model' => 'strtoupper',
        'custom_model' => 'strtoupper',
        'oem_variant' => 'strtoupper',
        'display_name' => 'strtoupper',
        'ins_zone' => 'strtoupper',
        'cc_range' => 'strtoupper',
        'custom_variant' => 'strtoupper',
        'color' => 'strtoupper',
        'color_code' => 'strtoupper',
        'code' => 'strtoupper'
    ];
    protected $validations = [];

    public function segment()
    {
        return $this->belongsTo(EnumMaster::class, 'segment_id', 'id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'vh_id', 'id')
            ->orWhere('model_code', $this->code);
    }

    public function stock()
    {
        return $this->hasMany(XVehicleStock::class, 'model_code', 'code');
    }
}
