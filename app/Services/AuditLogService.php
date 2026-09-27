<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuditEvent;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Writes audit entries. The actor, IP address and user agent are captured automatically, and
 * sensitive keys (config/audit.php) are removed from the stored values.
 */
class AuditLogService
{
    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    public function record(AuditEvent $event, ?Model $subject, string $description, array $old = [], array $new = [], ?User $actor = null): AuditLog
    {
        $actor ??= $this->currentUser();
        $request = app()->runningInConsole() && ! app()->runningUnitTests() ? null : request();

        $old = $this->redact($old);
        $new = $this->redact($new);

        return AuditLog::create([
            'user_id' => $actor?->getKey(),
            'user_name' => $actor?->name,
            'user_email' => $actor?->email,
            'event' => $event,
            'auditable_type' => $subject?->getMorphClass(),
            'auditable_id' => $subject?->getKey(),
            'description' => Str::limit($description, 250, ''),
            'old_values' => $old === [] ? null : $old,
            'new_values' => $new === [] ? null : $new,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent() !== null ? Str::limit((string) $request->userAgent(), 509) : null,
        ]);
    }

    /**
     * Remove every redacted key, at any depth.
     *
     * @param  array<array-key, mixed>  $values
     * @return array<array-key, mixed>
     */
    public function redact(array $values): array
    {
        /** @var list<string> $patterns */
        $patterns = config('audit.redact', []);
        $clean = [];

        foreach ($values as $key => $value) {
            if (is_string($key) && Str::is($patterns, Str::lower($key))) {
                continue;
            }

            $clean[$key] = is_array($value) ? $this->redact($value) : $value;
        }

        return $clean;
    }

    private function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
