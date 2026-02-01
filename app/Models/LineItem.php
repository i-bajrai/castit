<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LineItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cost_package_id',
        'item_no',
        'description',
        'unit_of_measure',
        'original_qty',
        'original_rate',
        'original_amount',
        'sort_order',
        'created_in_period_id',
    ];

    /**
     * @return BelongsTo<CostPackage, $this>
     */
    public function costPackage(): BelongsTo
    {
        return $this->belongsTo(CostPackage::class);
    }

    /**
     * @return BelongsTo<ForecastPeriod, $this>
     */
    public function createdInPeriod(): BelongsTo
    {
        return $this->belongsTo(ForecastPeriod::class, 'created_in_period_id');
    }

    public function existedInPeriod(ForecastPeriod $period): bool
    {
        if (! $this->created_in_period_id) {
            return true;
        }

        return $period->period_date->gte($this->createdInPeriod->period_date);
    }

    /**
     * @return HasMany<LineItemForecast, $this>
     */
    public function forecasts(): HasMany
    {
        return $this->hasMany(LineItemForecast::class);
    }
}
