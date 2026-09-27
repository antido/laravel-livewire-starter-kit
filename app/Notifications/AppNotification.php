<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * An in-app notification stored in the database. The payload is always:
 * type, title, message and url (an internal path). It never carries passwords, tokens or other
 * sensitive details. Queued after the database transaction commits; with QUEUE_CONNECTION=sync
 * it is sent right away.
 */
abstract class AppNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->afterCommit();
    }

    abstract protected function type(): NotificationType;

    abstract protected function title(): string;

    abstract protected function message(): string;

    /**
     * An internal path such as "/dashboard", never an absolute URL.
     */
    abstract protected function url(): string;

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{type: string, title: string, message: string, url: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type()->value,
            'title' => $this->title(),
            'message' => $this->message(),
            'url' => $this->url(),
        ];
    }
}
