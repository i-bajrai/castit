<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ControlAccount extends Model
{
    protected $fillable = [
        'project_id',
        'phase',
        'code',
        'description',
        'category',
        'baseline_budget',
        'approved_budget',
        'sort_order',
    ];

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return HasMany<ControlAccountForecast, $this>
     */
    public function forecasts(): HasMany
    {
        return $this->hasMany(ControlAccountForecast::class);
    }

    /**
     * @return HasMany<BudgetAdjustment, $this>
     */
    public function budgetAdjustments(): HasMany
    {
        return $this->hasMany(BudgetAdjustment::class);
    }
}
