<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * A user's own in-app notifications. Every lookup goes through $user->notifications(), so one user can
 * never read, change or delete another user's notification, whatever id is sent.
 */
class NotificationService
{
    public function unreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    /**
     * @throws NotFoundHttpException for a malformed id or one that is not the user's
     */
    public function find(User $user, string $id): DatabaseNotification
    {
        if (! Str::isUuid($id)) {
            throw new NotFoundHttpException;
        }

        $notification = $user->notifications()->whereKey($id)->first();

        if (! $notification instanceof DatabaseNotification) {
            throw new NotFoundHttpException;
        }

        return $notification;
    }

    public function markAsRead(User $user, string $id): DatabaseNotification
    {
        $notification = $this->find($user, $id);
        $notification->markAsRead();

        return $notification;
    }

    public function markAsUnread(User $user, string $id): DatabaseNotification
    {
        $notification = $this->find($user, $id);
        $notification->markAsUnread();

        return $notification;
    }

    public function markAllAsRead(User $user): int
    {
        return $user->unreadNotifications()->update(['read_at' => now()]);
    }

    public function delete(User $user, string $id): void
    {
        $this->find($user, $id)->delete();
    }

    /**
     * The notification's link if it is an internal path, otherwise null. Absolute URLs, protocol-relative
     * URLs ("//evil.test") and anything with a scheme are rejected, which prevents open redirects.
     */
    public function safeUrl(DatabaseNotification $notification): ?string
    {
        $url = $notification->data['url'] ?? null;

        if (! is_string($url) || $url === '' || strlen($url) > 2000) {
            return null;
        }

        if (! str_starts_with($url, '/') || str_starts_with($url, '//') || str_contains($url, '\\')) {
            return null;
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $url) === 1 || parse_url($url, PHP_URL_SCHEME) !== null || parse_url($url, PHP_URL_HOST) !== null) {
            return null;
        }

        return $url;
    }
}
