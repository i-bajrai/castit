<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class ControlAccount extends Model
{
    /** @use HasFactory<\Database\Factories\ControlAccountFactory> */
    use HasFactory;

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
     * @return HasMany<CostPackage, $this>
     */
    public function costPackages(): HasMany
    {
        return $this->hasMany(CostPackage::class);
    }

    /**
     * @return HasManyThrough<LineItem, CostPackage, $this>
     */
    public function lineItems(): HasManyThrough
    {
        return $this->hasManyThrough(LineItem::class, CostPackage::class);
    }

    /**
     * @return HasMany<BudgetAdjustment, $this>
     */
    public function budgetAdjustments(): HasMany
    {
        return $this->hasMany(BudgetAdjustment::class);
    }
}
