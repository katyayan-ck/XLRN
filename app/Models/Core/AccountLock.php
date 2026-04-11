<?php

namespace App\Models\Core;

use App\Models\BaseModel;

class AccountLock extends BaseModel
{
    protected $fillable = [
        'user_id',
        'locked_until',
        'reason',
    ];
}
