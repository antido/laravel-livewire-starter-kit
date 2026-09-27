<?php

declare(strict_types=1);

namespace App\Livewire\Notifications;

use App\Livewire\Concerns\InteractsWithActor;
use App\Services\NotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * The header bell. The unread count refreshes every 30 seconds while the tab is visible; the list
 * itself is only loaded when the dropdown is opened.
 */
class Bell extends Component
{
    use InteractsWithActor;

    /**
     * How many notifications the dropdown shows.
     */
    public const int LATEST = 10;

    public bool $loaded = false;

    /**
     * Called when the dropdown opens.
     */
    public function load(): void
    {
        $this->loaded = true;
    }

    /**
     * Poll target: only re-renders the count (the list stays unloaded until opened).
     */
    public function refreshCount(): void
    {
        //
    }

    /**
     * Mark the notification read, then go to its link if it is an internal path.
     */
    public function visit(string $id, NotificationService $notifications): void
    {
        $notification = $notifications->markAsRead($this->actor(), $id);
        $url = $notifications->safeUrl($notification);

        $this->dispatch('notifications-changed');

        if ($url !== null) {
            $this->redirect($url);
        }
    }

    public function markAllAsRead(NotificationService $notifications): void
    {
        $notifications->markAllAsRead($this->actor());

        $this->dispatch('notifications-changed');
    }

    #[On('notifications-changed')]
    public function refresh(): void
    {
        //
    }

    public function render(NotificationService $notifications): View
    {
        $actor = $this->actor();

        /** @var Collection<int, DatabaseNotification> $latest */
        $latest = $this->loaded ? $actor->notifications()->latest()->limit(self::LATEST)->get() : collect();

        return view('livewire.notifications.bell', [
            'unread' => $notifications->unreadCount($actor),
            'latest' => $latest,
        ]);
    }
}
