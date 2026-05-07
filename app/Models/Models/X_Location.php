<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class X_Location extends BaseModel
{
    use SoftDeletes;

    protected $table = 'xcelr8_us_location';
    protected $fillable = [
        'branch_id',
        'name',
        'abbr',
        'demibranch',
        'stock_location',
        'd_order',
        'service_branch',
        'spare_consumption',
        'spare_store',
        'spare_warehouse',
        'status',
        'created_by',
        'updated_by',
        'deleted_by'
    ];
    protected $guarded = ['id'];

    public function branch()
    {
        return $this->belongsTo(Branches::class, 'branch_id', 'id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'location_id', 'id');
    }

    public function stock()
    {
        return $this->hasMany(XVehicleStock::class, 'location_id', 'id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'xcore_user_locations', 'location_id', 'user_id');
    }
}
