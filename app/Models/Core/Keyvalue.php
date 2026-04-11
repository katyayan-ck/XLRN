<?php

namespace App\Models\Core;

use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Traits\HasTreeStructure;

class Keyvalue extends BaseModel
{
    use CrudTrait;
    use HasFactory;
    use HasTreeStructure;
    protected $fillable = [
        'keyword_master_id',
        'key',
        'value',
        'details',
        'parent_id',
        'level',
        'extra_data',
        'status'
    ];

    protected $casts = [
        'extra_data' => 'array',
        'level' => 'integer',
        'status' => 'integer',
    ];

    public function keywordMaster()
    {
        return $this->belongsTo(KeywordMaster::class);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function registerMediaCollections(): void
    {
        parent::registerMediaCollections();
        $this->addMediaCollection('attachments')
            ->singleFile() // Or multiple if needed
            ->useDisk('public');
    }

    public static function getEnum(string $keyword, bool $activeOnly = true, bool $recursive = false): array
    {
        $master = KeywordMaster::where('keyword', $keyword)->first();
        if (!$master) return [];

        $query = self::where('keyword_master_id', $master->id);
        if ($activeOnly) $query->where('status', 1);

        if ($recursive) {
            return $query->with('children')->whereNull('parent_id')->get()->toArray();
        }
        return $query->pluck('value', 'key')->toArray();
    }

    public static function getKeywordId(string $keyword): ?int
    {
        return KeywordMaster::where('keyword', $keyword)->value('id');
    }

    public static function getValueId(string $keyword, string $valKey): ?int
    {
        $masterId = self::getKeywordId($keyword);
        return self::where('keyword_master_id', $masterId)->where('key', $valKey)->value('id');
    }

    public function scopeForKeyword($query, string $keyword)
    {
        $masterId = self::getKeywordId($keyword);
        return $query->where('keyword_master_id', $masterId);
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }
}
