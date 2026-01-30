<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForecastPeriod extends Model
{
    protected $fillable = [
        'project_id',
        'period_date',
        'is_current',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_date' => 'date',
            'is_current' => 'boolean',
        ];
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
     * @return HasMany<ControlAccountForecast, $this>
     */
    public function controlAccountForecasts(): HasMany
    {
        return $this->hasMany(ControlAccountForecast::class);
    }
}
