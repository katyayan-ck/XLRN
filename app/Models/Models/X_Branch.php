<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class X_Branch extends BaseModel
{
    use SoftDeletes;

    protected $table = 'xcelr8_us_branch';
    protected $fillable = [];
    protected $guarded = ['id'];

    public function locations()
    {
        return $this->hasMany(Location::class, 'branch_id', 'id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'branch_id', 'id');
    }

    public function stock()
    {
        return $this->hasMany(XVehicleStock::class, 'branch_id', 'id');
    }

    public function subbranches()
    {
        return $this->hasMany(Subbranch::class, 'branch_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'xcore_user_branches', 'branch_id', 'user_id');
    }
}
