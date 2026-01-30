<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetAdjustment extends Model
{
    protected $fillable = [
        'control_account_id',
        'forecast_period_id',
        'user_id',
        'amount',
        'previous_approved_budget',
        'new_approved_budget',
        'reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'previous_approved_budget' => 'decimal:2',
            'new_approved_budget' => 'decimal:2',
        ];
    }

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

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
