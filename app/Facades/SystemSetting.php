<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed get(string $key, mixed $default = null)
 * @method static array all()
 * @method static array allByTopic()
 * @method static array topic(string $topic)
 * @method static \App\Models\SystemSetting set(string $key, mixed $value, ?string $topic = null)
 * @method static string getSiteName()
 * @method static string getSiteUrl()
 * @method static string getSiteSlogan()
 * @method static array getSiteLogos()
 * @method static string getLogoUrl(string $type = 'header')
 * @method static string getFooterText()
 * @method static string getTheme()
 * @method static string getSkin()
 * @method static string getDealershipName()
 * @method static array getDealershipDetails()
 * @method static array getTaxRates()
 * @method static float getGSTRate()
 * @method static float getTDSRate()
 * 
 * @see \App\Services\SystemSettingService
 */
class SystemSetting extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Services\SystemSettingService::class;
    }
}
