<?php

declare(strict_types=1);

namespace App\Traits;

use App\Enums\AuditEvent;
use App\Services\AuditLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Writes an audit entry when the model is created, updated, deleted or restored. Updates store only
 * the attributes that changed. Models may leave out more attributes by overriding auditExclude().
 *
 * @mixin Model
 */
trait Auditable
{
    /**
     * Attributes never worth auditing on any model.
     *
     * @var list<string>
     */
    private static array $alwaysExcludedFromAudit = ['created_at', 'updated_at'];

    public static function bootAuditable(): void
    {
        static::created(function (Model $model): void {
            /** @var Model&self $model */
            $model->writeAudit(AuditEvent::Created, [], $model->auditableAttributes($model->getAttributes()));
        });

        static::updated(function (Model $model): void {
            /** @var Model&self $model */
            $changed = $model->auditableAttributes($model->getChanges());

            if ($changed === []) {
                return;
            }

            $old = array_intersect_key($model->auditableAttributes($model->getPrevious()), $changed);

            $model->writeAudit(AuditEvent::Updated, $old, $changed);
        });

        static::deleted(function (Model $model): void {
            /** @var Model&self $model */
            $model->writeAudit(AuditEvent::Deleted, $model->auditableAttributes($model->getAttributes()), []);
        });

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::registerModelEvent('restored', function (Model $model): void {
                /** @var Model&self $model */
                $model->writeAudit(AuditEvent::Restored, [], []);
            });
        }
    }

    /**
     * Extra attributes this model leaves out of the audit trail. Hidden attributes are always left out.
     *
     * @return list<string>
     */
    protected function auditExclude(): array
    {
        return [];
    }

    /**
     * The name shown for this record in the audit trail.
     */
    public function auditLabel(): string
    {
        $name = $this->getAttribute('name');

        return is_string($name) ? $name : '#'.$this->getKey();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function auditableAttributes(array $attributes): array
    {
        $excluded = array_merge(self::$alwaysExcludedFromAudit, $this->auditExclude(), $this->getHidden());

        $values = array_diff_key($attributes, array_flip($excluded));

        // Store enums and dates as their plain values.
        foreach ($values as $key => $value) {
            if ($value instanceof \BackedEnum) {
                $values[$key] = $value->value;
            } elseif ($value instanceof \DateTimeInterface) {
                $values[$key] = $value->format('Y-m-d H:i:s');
            }
        }

        return app(AuditLogService::class)->redact($values);
    }

    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    protected function writeAudit(AuditEvent $event, array $old, array $new): void
    {
        $verb = match ($event) {
            AuditEvent::Created => 'Created',
            AuditEvent::Deleted => 'Deleted',
            AuditEvent::Restored => 'Restored',
            default => 'Updated',
        };

        $type = strtolower(class_basename($this));

        app(AuditLogService::class)->record($event, $this, "{$verb} {$type} {$this->auditLabel()}", $old, $new);
    }
}
