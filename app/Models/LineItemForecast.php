<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LineItemForecast extends Model
{
    protected $fillable = [
        'line_item_id',
        'forecast_period_id',
        'period_qty',
        'period_rate',
        'fcac_qty',
        'fcac_rate',
        'comments',
    ];

    /**
     * @return BelongsTo<LineItem, $this>
     */
    public function lineItem(): BelongsTo
    {
        return $this->belongsTo(LineItem::class);
    }

    /**
     * @return BelongsTo<ForecastPeriod, $this>
     */
    public function forecastPeriod(): BelongsTo
    {
        return $this->belongsTo(ForecastPeriod::class);
    }
}
