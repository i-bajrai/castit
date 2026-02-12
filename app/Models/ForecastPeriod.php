<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $period_date
 */
class ForecastPeriod extends Model
{
    /** @use HasFactory<\Database\Factories\ForecastPeriodFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'period_date',
        'is_current',
        'locked_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_date' => 'date',
            'is_current' => 'boolean',
            'locked_at' => 'datetime',
        ];
    }

    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }

    public function isEditable(): bool
    {
        return ! $this->isLocked()
            && $this->period_date->startOfMonth()->equalTo(now()->startOfMonth());
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return HasMany<LineItemForecast, $this>
     */
    public function lineItemForecasts(): HasMany
    {
        return $this->hasMany(LineItemForecast::class);
    }

    /**
     * @return HasMany<BudgetAdjustment, $this>
     */
    public function budgetAdjustments(): HasMany
    {
        return $this->hasMany(BudgetAdjustment::class);
    }
}
