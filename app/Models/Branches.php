<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BaseModel;
use App\Models\Admin\Branch;
use App\Models\Admin\Location;
use App\Models\Module\Booking\Booking;
use App\Models\Admin\Subbranch;
use App\Models\Admin\XVehicleStock;
use App\Models\Admin\User;

class Branches extends BaseModel
{
	use SoftDeletes;

	protected $table = ' 	xlr8_admin_branch';
	protected $fillable = [];
	protected $guarded = ['code'];

	public function locations()
	{
		return $this->hasMany(Location::class, 'branch_code', 'code');
	}

	public function bookings()
	{
		return $this->hasMany(Booking::class, 'branch_code', 'code');
	}

	public function stock()
	{
		return $this->hasMany(XVehicleStock::class, 'branch_code', 'code');
	}

	public function subbranches()
	{
		return $this->hasMany(Subbranch::class, 'branch_code');
	}

	public function users()
	{
		return $this->belongsToMany(User::class, 'xcore_user_branches', 'branch_code', 'user_code');
	}
}
