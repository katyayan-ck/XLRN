<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static int|null getValueId(string $keyword, string $key, bool $activeOnly = true)
 * @method static \App\Models\Core\Keyvalue|null getValue(string $keyword, string $key, bool $activeOnly = true)
 * @method static array getValues(string $keyword, bool $activeOnly = true)
 * @method static \Illuminate\Database\Eloquent\Collection getValueObjects(string $keyword, bool $activeOnly = true, bool $recursive = false)
 * @method static int|null getKeywordId(string $keyword)
 * @method static \App\Models\Core\Keyvalue|null getValueByValue(string $keyword, string $value, bool $activeOnly = true)
 * @method static bool keywordExists(string $keyword)
 * @method static bool valueExists(string $keyword, string $key, bool $activeOnly = true)
 * @method static array getEnum(string $keyword, bool $activeOnly = true)
 * @method static void clearCache(string|null $keyword = null)
 * @method static int|null findValueId(string $keyword, string $searchTerm, bool $activeOnly = true)
 * 
 * @see \App\Services\KeywordValueService
 */
class KeywordValue extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'keyword-value';
    }
}
