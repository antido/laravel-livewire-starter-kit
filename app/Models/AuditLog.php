<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AuditEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * One immutable audit entry. Entries can be created, never updated or deleted through the model;
 * only the retention setting (AUDIT_RETENTION_DAYS) removes old ones, through "model:prune".
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $user_name
 * @property string|null $user_email
 * @property AuditEvent $event
 * @property string|null $auditable_type
 * @property int|null $auditable_id
 * @property string $description
 * @property array<string, mixed>|null $old_values
 * @property array<string, mixed>|null $new_values
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon|null $created_at
 */
class AuditLog extends Model
{
    use MassPrunable;

    public const null UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'user_name',
        'user_email',
        'event',
        'auditable_type',
        'auditable_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Audit log entries cannot be changed.');
        });

        static::deleting(function (): never {
            throw new LogicException('Audit log entries cannot be deleted.');
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event' => AuditEvent::class,
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
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
     * @return MorphTo<Model, $this>
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Entries older than the retention period. Nothing is pruned when no period is set.
     *
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        $days = config('audit.retention_days');

        if (! is_numeric($days) || (int) $days < 1) {
            return static::query()->whereRaw('1 = 0');
        }

        return static::query()->where('created_at', '<', now()->subDays((int) $days));
    }

    /**
     * Who acted: the current name of the account, the name it had when it acted (if it was deleted since), or "System".
     */
    public function actorName(): string
    {
        $user = $this->relationLoaded('user') ? $this->user : null;

        return match (true) {
            $user !== null => $user->name,
            $this->user_name !== null => $this->user_name.' (deleted)',
            default => 'System',
        };
    }

    /**
     * A readable subject, for example "User #12 (Juan Dela Cruz)", or null for entries without one.
     */
    public function subjectLabel(): ?string
    {
        if ($this->auditable_type === null) {
            return null;
        }

        $type = class_basename($this->auditable_type);
        $subject = $this->relationLoaded('auditable') ? $this->auditable : null;
        $name = match (true) {
            $subject instanceof User, $subject instanceof Role, $subject instanceof Permission => $subject->name,
            $subject !== null && method_exists($subject, 'auditLabel') => $subject->auditLabel(),
            default => null,
        };

        return trim("{$type} #{$this->auditable_id}".($name !== null ? " ({$name})" : ''));
    }
}
