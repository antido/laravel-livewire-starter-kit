<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RoleName;
use App\Enums\ThemePreference;
use App\Enums\UserStatus;
use App\Traits\Auditable;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property UserStatus $status
 * @property ThemePreference $theme_preference
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $last_login_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use Auditable, HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'active',
        'theme_preference' => 'system',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'theme_preference' => ThemePreference::class,
        ];
    }

    /**
     * Sign-in bookkeeping and personal preferences are not audited.
     *
     * @return list<string>
     */
    protected function auditExclude(): array
    {
        return ['last_login_at', 'theme_preference'];
    }

    /**
     * The employee record linked to this account, if any (archived records included: they keep their link).
     *
     * @return HasOne<Employee, $this>
     */
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class)->withTrashed();
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(RoleName::SuperAdmin->value);
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', UserStatus::Active->value);
    }

    /**
     * The first letters of the first and last words of the name, for avatars.
     */
    public function initials(): string
    {
        $words = preg_split('/\s+/', trim($this->name)) ?: [];
        $first = $words[0] ?? '';
        $last = count($words) > 1 ? $words[count($words) - 1] : '';

        return mb_strtoupper(mb_substr($first, 0, 1).mb_substr($last, 0, 1));
    }

    public function firstName(): string
    {
        $words = preg_split('/\s+/', trim($this->name)) ?: [];

        return $words[0] ?? $this->name;
    }
}
