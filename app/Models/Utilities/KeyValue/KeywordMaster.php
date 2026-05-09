<?php

namespace App\Models\Utilities\KeyValue;

use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class KeywordMaster extends BaseModel
{
    use CrudTrait;
    use HasFactory;

    protected $table = 'xlr8_utils_keyword_master';

    protected $fillable = [
        'code', 'keyword', 'details', 'extra_data', 'status', 'is_active'
    ];

    protected $casts = [
        'extra_data' => 'array',
        'status'     => 'integer',
        'is_active'  => 'boolean',
    ];

    public function keyvalues()
    {
        return $this->hasMany(Keyvalue::class, 'keyword_code', 'code');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
