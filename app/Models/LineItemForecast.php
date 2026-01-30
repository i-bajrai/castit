<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LineItemForecast extends Model
{
    protected $fillable = [
        'line_item_id',
        'forecast_period_id',
        'previous_qty',
        'previous_rate',
        'previous_amount',
        'ctd_qty',
        'ctd_rate',
        'ctd_amount',
        'ctc_qty',
        'ctc_rate',
        'ctc_amount',
        'fcac_rate',
        'fcac_amount',
        'variance',
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
