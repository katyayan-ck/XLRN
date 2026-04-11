<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class XExchange extends BaseModel
{
    use SoftDeletes;

    protected $table = 'xcelr8_exchange';
    protected $fillable = [];
    protected $guarded = ['id'];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'id');
    }

    public static function getVerifiedCounts($type, $timeFrame = null)
    {
        $query = self::where('verification_status', 1)->where('status', 2)
            ->where('purchase_type', $type)
            ->whereHas('booking', function ($q) use ($timeFrame) {
                $q->whereNull('deleted_at');
                if ($timeFrame === 'mtd') {
                    $q->where('booking_date', '>=', now()->subDays(30));
                } elseif ($timeFrame === 'ytd') {
                    $q->where('booking_date', '>=', now()->subDays(365));
                }
            });

        return $query->count();
    }

    public static function getPendingCounts($type)
    {
        return self::whereIn('verification_status', [0, null])
            ->where('purchase_type', $type)
            ->whereHas('booking', function ($q) {
                $q->whereNull('deleted_at');
            })->count();
    }
}
