<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EmployeeStatus;
use App\Traits\Auditable;
use App\Traits\HasAuditFields;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * An employee record. It may be linked to one user account so the employee can log in.
 * user_id, employee_number, created_by and updated_by are set server-side only (not fillable).
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $employee_number
 * @property string $first_name
 * @property string|null $middle_name
 * @property string $last_name
 * @property string|null $suffix
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property string $position
 * @property Carbon|null $hire_date
 * @property EmployeeStatus $status
 * @property string|null $notes
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read string $full_name
 */
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use Auditable, HasAuditFields, HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'email',
        'phone',
        'address',
        'position',
        'hire_date',
        'status',
        'notes',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'status' => EmployeeStatus::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * "Juan Santos Dela Cruz Jr."
     *
     * @return Attribute<string, never>
     */
    protected function fullName(): Attribute
    {
        return Attribute::get(fn (): string => trim(implode(' ', array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
            $this->suffix,
        ], fn (?string $part): bool => $part !== null && $part !== ''))));
    }

    public function auditLabel(): string
    {
        return "{$this->full_name} ({$this->employee_number})";
    }

    /**
     * @return list<string>
     */
    protected function auditExclude(): array
    {
        return ['created_by', 'updated_by', 'deleted_at'];
    }

    /**
     * Search by name, employee number or position.
     *
     * @param  Builder<Employee>  $query
     * @return Builder<Employee>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);

        if ($term === '') {
            return $query;
        }

        $like = '%'.$term.'%';
        $words = preg_split('/\s+/', $term) ?: [];

        return $query->where(fn (Builder $query) => $query
            ->where('first_name', 'like', $like)
            ->orWhere('last_name', 'like', $like)
            ->orWhere('middle_name', 'like', $like)
            ->orWhere('employee_number', 'like', $like)
            ->orWhere('position', 'like', $like)
            // "Juan Cruz" matches first name Juan and last name (Dela) Cruz, on any database.
            ->when(count($words) > 1, fn (Builder $query) => $query->orWhere(fn (Builder $query) => $query
                ->where('first_name', 'like', $words[0].'%')
                ->where('last_name', 'like', '%'.$words[count($words) - 1].'%'))));
    }
}
