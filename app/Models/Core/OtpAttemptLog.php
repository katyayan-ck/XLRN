<?php

namespace App\Models\Core;

use App\Models\BaseModel;

class OtpAttemptLog extends BaseModel
{
    protected $fillable = [
        'user_id',
        'mobile',
        'action',
        'ip_address',
        'user_agent',
        'reason',
    ];
}
