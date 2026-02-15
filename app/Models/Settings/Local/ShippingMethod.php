<?php

namespace App\Models\Settings\Local;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'geo_zone_id',
        'description',
        'price',
        'free_over',
        'min_subtotal',
        'max_subtotal',
        'is_active',
        'sort_order',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'free_over' => 'decimal:2',
            'min_subtotal' => 'decimal:2',
            'max_subtotal' => 'decimal:2',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'settings' => 'array',
        ];
    }

    public function geoZone(): BelongsTo
    {
        return $this->belongsTo(GeoZone::class);
    }
}
