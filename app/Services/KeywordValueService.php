<?php

namespace App\Services;

use App\Models\Core\Keyvalue;
use App\Models\Core\KeywordMaster;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * KeywordValueService - Centralized service for Keyword/Keyvalue lookups
 * 
 * Handles all interactions with the Keyword-Value mechanism
 * - Get value IDs by keyword + key
 * - Get value objects
 * - Get all values for a keyword
 * - Caching for performance
 * - Error handling and logging
 */
class KeywordValueService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const CACHE_PREFIX = 'kwv_';

    /**
     * Get Keyvalue ID by keyword and key
     * 
     * Usage: KeywordValueService::getValueId('fuel_type', 'diesel')
     * Returns: Integer ID or null
     * 
     * @param string $keyword The keyword name (e.g., 'fuel_type', 'body_type')
     * @param string $key The value key (e.g., 'diesel', 'petrol')
     * @param bool $activeOnly Only search active records
     * @return int|null
     */
    public static function getValueId(string $keyword, string $key, bool $activeOnly = true): ?int
    {
        try {
            $cacheKey = self::CACHE_PREFIX . "id_{$keyword}_{$key}";

            // Try to get from cache
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }

            // Get KeywordMaster first
            $master = KeywordMaster::where('keyword', $keyword)->first();
            if (!$master) {
                Log::warning("KeywordMaster not found for keyword: {$keyword}");
                return null;
            }

            // Query Keyvalue with proper relationship
            $query = Keyvalue::where('keyword_master_id', $master->id)
                ->where('key', $key);

            if ($activeOnly) {
                $query->where('status', 1);
            }

            $result = $query->value('id');

            // Cache the result (even if null, to avoid repeated queries)
            Cache::put($cacheKey, $result, self::CACHE_TTL);

            return $result;
        } catch (\Exception $e) {
            Log::error("KeywordValueService::getValueId error", [
                'keyword' => $keyword,
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get Keyvalue object by keyword and key
     * 
     * Usage: KeywordValueService::getValue('fuel_type', 'diesel')
     * Returns: Keyvalue model instance or null
     * 
     * @param string $keyword
     * @param string $key
     * @param bool $activeOnly
     * @return Keyvalue|null
     */
    public static function getValue(string $keyword, string $key, bool $activeOnly = true): ?Keyvalue
    {
        try {
            $master = KeywordMaster::where('keyword', $keyword)->first();
            if (!$master) {
                return null;
            }

            $query = Keyvalue::where('keyword_master_id', $master->id)
                ->where('key', $key);

            if ($activeOnly) {
                $query->where('status', 1);
            }

            return $query->first();
        } catch (\Exception $e) {
            Log::error("KeywordValueService::getValue error", [
                'keyword' => $keyword,
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get all values for a keyword
     * 
     * Usage: KeywordValueService::getValues('fuel_type')
     * Returns: ['diesel' => 1, 'petrol' => 2, ...]
     * 
     * @param string $keyword
     * @param bool $activeOnly
     * @return array Key-value pairs: ['key' => id, ...]
     */
    public static function getValues(string $keyword, bool $activeOnly = true): array
    {
        try {
            $cacheKey = self::CACHE_PREFIX . "values_{$keyword}";

            // Try to get from cache
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }

            $master = KeywordMaster::where('keyword', $keyword)->first();
            if (!$master) {
                return [];
            }

            $query = Keyvalue::where('keyword_master_id', $master->id);

            if ($activeOnly) {
                $query->where('status', 1);
            }

            $result = $query->pluck('id', 'key')->toArray();

            // Cache the result
            Cache::put($cacheKey, $result, self::CACHE_TTL);

            return $result;
        } catch (\Exception $e) {
            Log::error("KeywordValueService::getValues error", [
                'keyword' => $keyword,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get all value objects for a keyword with their data
     * 
     * Usage: KeywordValueService::getValueObjects('body_type')
     * Returns: Collection of Keyvalue models
     * 
     * @param string $keyword
     * @param bool $activeOnly
     * @param bool $recursive Include child values
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getValueObjects(string $keyword, bool $activeOnly = true, bool $recursive = false)
    {
        try {
            $master = KeywordMaster::where('keyword', $keyword)->first();
            if (!$master) {
                return collect();
            }

            $query = Keyvalue::where('keyword_master_id', $master->id);

            if ($activeOnly) {
                $query->where('status', 1);
            }

            if ($recursive) {
                $query->with('children');
                $query->whereNull('parent_id');
            }

            return $query->get();
        } catch (\Exception $e) {
            Log::error("KeywordValueService::getValueObjects error", [
                'keyword' => $keyword,
                'error' => $e->getMessage(),
            ]);
            return collect();
        }
    }

    /**
     * Get KeywordMaster ID by keyword
     * 
     * Usage: KeywordValueService::getKeywordId('fuel_type')
     * Returns: Integer ID or null
     * 
     * @param string $keyword
     * @return int|null
     */
    public static function getKeywordId(string $keyword): ?int
    {
        try {
            $cacheKey = self::CACHE_PREFIX . "keyword_{$keyword}";

            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }

            $result = KeywordMaster::where('keyword', $keyword)->value('id');

            Cache::put($cacheKey, $result, self::CACHE_TTL);

            return $result;
        } catch (\Exception $e) {
            Log::error("KeywordValueService::getKeywordId error", [
                'keyword' => $keyword,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get value by keyword and value property (instead of key)
     * 
     * Usage: KeywordValueService::getValueByValue('fuel_type', 'Diesel')
     * Returns: Keyvalue model or null
     * Note: This searches by 'value' column, not 'key'
     * 
     * @param string $keyword
     * @param string $value The value to search for
     * @param bool $activeOnly
     * @return Keyvalue|null
     */
    public static function getValueByValue(string $keyword, string $value, bool $activeOnly = true): ?Keyvalue
    {
        try {
            $master = KeywordMaster::where('keyword', $keyword)->first();
            if (!$master) {
                return null;
            }

            $query = Keyvalue::where('keyword_master_id', $master->id)
                ->where('value', $value);

            if ($activeOnly) {
                $query->where('status', 1);
            }

            return $query->first();
        } catch (\Exception $e) {
            Log::error("KeywordValueService::getValueByValue error", [
                'keyword' => $keyword,
                'value' => $value,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Check if a keyword exists
     * 
     * @param string $keyword
     * @return bool
     */
    public static function keywordExists(string $keyword): bool
    {
        try {
            return KeywordMaster::where('keyword', $keyword)->exists();
        } catch (\Exception $e) {
            Log::error("KeywordValueService::keywordExists error", [
                'keyword' => $keyword,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Check if a value exists for a keyword
     * 
     * @param string $keyword
     * @param string $key
     * @param bool $activeOnly
     * @return bool
     */
    public static function valueExists(string $keyword, string $key, bool $activeOnly = true): bool
    {
        try {
            $master = KeywordMaster::where('keyword', $keyword)->first();
            if (!$master) {
                return false;
            }

            $query = Keyvalue::where('keyword_master_id', $master->id)
                ->where('key', $key);

            if ($activeOnly) {
                $query->where('status', 1);
            }

            return $query->exists();
        } catch (\Exception $e) {
            Log::error("KeywordValueService::valueExists error", [
                'keyword' => $keyword,
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get enum array for select dropdowns
     * 
     * Usage: KeywordValueService::getEnum('fuel_type')
     * Returns: ['diesel' => 'Diesel', 'petrol' => 'Petrol', ...]
     * 
     * @param string $keyword
     * @param bool $activeOnly
     * @return array
     */
    public static function getEnum(string $keyword, bool $activeOnly = true): array
    {
        try {
            $cacheKey = self::CACHE_PREFIX . "enum_{$keyword}";

            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }

            $master = KeywordMaster::where('keyword', $keyword)->first();
            if (!$master) {
                return [];
            }

            $query = Keyvalue::where('keyword_master_id', $master->id);

            if ($activeOnly) {
                $query->where('status', 1);
            }

            $result = $query->pluck('value', 'key')->toArray();

            Cache::put($cacheKey, $result, self::CACHE_TTL);

            return $result;
        } catch (\Exception $e) {
            Log::error("KeywordValueService::getEnum error", [
                'keyword' => $keyword,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Clear all caches for a specific keyword
     * 
     * @param string|null $keyword If null, clears all caches
     * @return void
     */
    public static function clearCache(?string $keyword = null): void
    {
        if ($keyword === null) {
            // Clear all kwv_ prefixed caches
            Cache::flush();
            Log::info("KeywordValueService: Cleared all caches");
        } else {
            // Clear specific keyword caches
            Cache::forget(self::CACHE_PREFIX . "id_{$keyword}");
            Cache::forget(self::CACHE_PREFIX . "values_{$keyword}");
            Cache::forget(self::CACHE_PREFIX . "enum_{$keyword}");
            Cache::forget(self::CACHE_PREFIX . "keyword_{$keyword}");
            Log::info("KeywordValueService: Cleared caches for keyword: {$keyword}");
        }
    }

    /**
     * Get value ID with fallback to value search
     * First tries by key, then by value property
     * 
     * Usage: KeywordValueService::findValueId('fuel_type', 'Diesel')
     * 
     * @param string $keyword
     * @param string $searchTerm Can be key or value
     * @param bool $activeOnly
     * @return int|null
     */
    public static function findValueId(string $keyword, string $searchTerm, bool $activeOnly = true): ?int
    {
        // Try by key first
        $id = self::getValueId($keyword, $searchTerm, $activeOnly);
        if ($id !== null) {
            return $id;
        }

        // Try by value
        $value = self::getValueByValue($keyword, $searchTerm, $activeOnly);
        return $value?->id;
    }
}
