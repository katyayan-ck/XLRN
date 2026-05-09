<?php

namespace App\Models\Utilities\KeyValue;

use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Traits\HasTreeStructure;
use Illuminate\Database\Eloquent\Builder;

class Keyvalue extends BaseModel
{
    use CrudTrait;
    use HasFactory;
    use HasTreeStructure;

    protected $table = 'xlr8_utils_keyvalue';

    protected $fillable = [
        'keyword_code',
        'code',
        'key', 'value', 'details',
        'parent_id', 'level', 'path',
        'extra_data', 'status', 'is_active'
    ];

    protected $casts = [
        'extra_data' => 'array',
        'level'      => 'integer',
        'status'     => 'integer',
        'is_active'  => 'boolean',
    ];

    protected static function booted()
    {
        parent::booted();

        static::saving(function ($model) {
            $model->keyword_code = strtoupper(trim($model->keyword_code ?? ''));
            $model->code         = strtoupper(trim($model->code ?? $model->key ?? ''));
            $model->key          = strtoupper(trim($model->key ?? ''));
        });
    }

    public function keywordMaster()
    {
        return $this->belongsTo(KeywordMaster::class, 'keyword_code', 'code');
    }

    public function parent()
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(static::class, 'parent_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForKeyword(Builder $query, string $keywordCode): Builder
    {
        return $query->where('keyword_code', strtoupper($keywordCode));
    }
}
