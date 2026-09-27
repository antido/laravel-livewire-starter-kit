<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Fills created_by and updated_by with the signed-in user. They are set here, server-side, and never
 * taken from a request.
 *
 * @mixin Model
 */
trait HasAuditFields
{
    public static function bootHasAuditFields(): void
    {
        static::creating(function (Model $model): void {
            $id = Auth::id();
            $model->setAttribute('created_by', $id);
            $model->setAttribute('updated_by', $id);
        });

        static::updating(function (Model $model): void {
            $model->setAttribute('updated_by', Auth::id());
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
