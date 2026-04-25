<?php

namespace App\Models\Utilities\KeyValue;

use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KeywordMaster extends BaseModel
{
    use CrudTrait;
    use HasFactory;
    protected $table = 'xlr8_utils_keyword_master';

    protected $fillable = ['keyword', 'details', 'extra_data', 'status'];
    

    protected $casts = [
        'extra_data' => 'array',
        'status' => 'integer',
    ];

    public function keyvalues()
    {
        return $this->hasMany(Keyvalue::class);
    }

    public function scopeByKeyword($query, string $keyword)
    {
        return $query->where('keyword', $keyword);
    }

    // DO NOT use HasTreeStructure here!
}
