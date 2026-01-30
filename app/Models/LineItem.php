<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LineItem extends Model
{
    protected $fillable = [
        'cost_package_id',
        'item_no',
        'description',
        'unit_of_measure',
        'original_qty',
        'original_rate',
        'original_amount',
        'sort_order',
    ];

    /**
     * @return BelongsTo<CostPackage, $this>
     */
    public function costPackage(): BelongsTo
    {
        return $this->belongsTo(CostPackage::class);
    }

    /**
     * @return HasMany<LineItemForecast, $this>
     */
    public function forecasts(): HasMany
    {
        return $this->hasMany(LineItemForecast::class);
    }
}
