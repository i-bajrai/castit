<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CostPackage extends Model
{
    /** @use HasFactory<\Database\Factories\CostPackageFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'control_account_id',
        'item_no',
        'name',
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
     * @return BelongsTo<ControlAccount, $this>
     */
    public function controlAccount(): BelongsTo
    {
        return $this->belongsTo(ControlAccount::class);
    }

    /**
     * @return HasMany<LineItem, $this>
     */
    public function lineItems(): HasMany
    {
        return $this->hasMany(LineItem::class);
    }
}
