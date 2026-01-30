<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ControlAccountForecast extends Model
{
    protected $fillable = [
        'control_account_id',
        'forecast_period_id',
        'last_month_approved_budget',
        'budget_movement',
        'monthly_cost',
        'cost_to_date',
        'estimate_to_complete',
        'estimated_final_cost',
        'last_month_efc',
        'efc_movement',
        'monthly_comments',
    ];

    /**
     * @return BelongsTo<ControlAccount, $this>
     */
    public function controlAccount(): BelongsTo
    {
        return $this->belongsTo(ControlAccount::class);
    }

    /**
     * @return BelongsTo<ForecastPeriod, $this>
     */
    public function forecastPeriod(): BelongsTo
    {
        return $this->belongsTo(ForecastPeriod::class);
    }
}
