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
        'code', 'keyword', 'description', 'is_active', 'is_recursive'
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'is_recursive' => 'boolean',
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
