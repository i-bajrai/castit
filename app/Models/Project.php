<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 */
class Project extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'project_number',
        'original_budget',
        'start_date',
        'end_date',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
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
