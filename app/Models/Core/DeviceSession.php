<?php

namespace App\Models\Core;

use App\Models\BaseModel;

class DeviceSession extends BaseModel
{
    protected $fillable = [
        'user_id',
        'device_id',
        'device_name',
        'platform',
        'last_active_at',
    ];
}
