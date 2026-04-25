<?php

namespace App\Models\IAM;

use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Post extends BaseModel
{
    use CrudTrait;
    use HasFactory;
    protected $table = 'xlr8_iam_post';
}
