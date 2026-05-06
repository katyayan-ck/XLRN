<?php

namespace App\Models\Vehicle;

use App\Models\BaseModel;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class VehicleModel extends BaseModel
{
    use CrudTrait;

    protected $table = 'xlr8_vehicle_model';

    protected $fillable = [
        'brand_code',
        'segment_code',
        'sub_segment_code',
        'code',
        'name',
        'oem_name',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_code', 'code');
    }

    public function segment()
    {
        return $this->belongsTo(Segment::class, 'segment_code', 'code');
    }

    public function subSegment()
    {
        return $this->belongsTo(SubSegment::class, 'sub_segment_code', 'code');
    }

    public function variants()
    {
        return $this->hasMany(Variant::class, 'model_code', 'code');
    }

    public function colors()
    {
        return $this->hasMany(Color::class, 'model_code', 'code');
    }

    // ── Code Generation ───────────────────────────────────────────

    /**
     * Derive model code from Custom Model name.
     * "BE6" → "BE6" | "XUV 700" → "XUV700" | "Scorpio N" → "SCORN"
     */
    public static function generateCode(string $customModelName): string
    {
        $clean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $customModelName));
        return substr($clean, 0, 10);
    }
}
