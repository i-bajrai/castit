<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'project_number',
        'original_budget',
        'status',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<ForecastPeriod, $this>
     */
    public function forecastPeriods(): HasMany
    {
        return $this->hasMany(ForecastPeriod::class);
    }

    /**
     * @return HasMany<CostPackage, $this>
     */
    public function costPackages(): HasMany
    {
        return $this->hasMany(CostPackage::class);
    }

    /**
     * @return HasMany<ControlAccount, $this>
     */
    public function controlAccounts(): HasMany
    {
        return $this->hasMany(ControlAccount::class);
    }
}
