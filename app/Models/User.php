<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\CompanyRole;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected static function booted(): void
    {
        static::saving(function (User $user) {
            if ($user->company_id === null) {
                $user->company_role = null;
                $user->company_removed_at = null;
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'company_id',
        'company_role',
        'company_removed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'company_role' => CompanyRole::class,
            'company_removed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isCompanyAdmin(): bool
    {
        return $this->company_role === CompanyRole::Admin;
    }

    public function isEngineer(): bool
    {
        return $this->company_role === CompanyRole::Engineer;
    }

    public function isCompanyViewer(): bool
    {
        return $this->company_role === CompanyRole::Viewer;
    }

    public function hasCompanyRole(CompanyRole ...$roles): bool
    {
        return in_array($this->company_role, $roles, true);
    }

    public function belongsToCompany(?int $companyId): bool
    {
        return $this->company_id !== null
            && $this->company_id === $companyId
            && $this->company_removed_at === null;
    }

    public function isRemovedFromCompany(): bool
    {
        return $this->company_removed_at !== null;
    }
}
