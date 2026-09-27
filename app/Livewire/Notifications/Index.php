<?php

declare(strict_types=1);

namespace App\Livewire\Notifications;

use App\Livewire\Concerns\InteractsWithActor;
use App\Services\NotificationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Notifications\DatabaseNotification;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The signed-in user's own notifications. No permission is needed: nobody can see anyone else's.
 */
#[Title('Notifications')]
class Index extends Component
{
    use InteractsWithActor, WithPagination;

    public const int PER_PAGE = 20;

    /**
     * "all" or "unread".
     */
    #[Url(except: 'all')]
    public string $tab = 'all';

    public function showTab(string $tab): void
    {
        $this->tab = $tab === 'unread' ? 'unread' : 'all';
        $this->resetPage();
    }

    public function open(string $id, NotificationService $notifications): void
    {
        $notification = $notifications->markAsRead($this->actor(), $id);
        $url = $notifications->safeUrl($notification);

        $this->dispatch('notifications-changed');

        if ($url !== null) {
            $this->redirect($url);
        }
    }

    public function markAsRead(string $id, NotificationService $notifications): void
    {
        $notifications->markAsRead($this->actor(), $id);
        $this->dispatch('notifications-changed');
    }

    public function markAsUnread(string $id, NotificationService $notifications): void
    {
        $notifications->markAsUnread($this->actor(), $id);
        $this->dispatch('notifications-changed');
    }

    public function markAllAsRead(NotificationService $notifications): void
    {
        $notifications->markAllAsRead($this->actor());
        $this->dispatch('notifications-changed');
        $this->dispatch('toast', type: 'success', message: __('All notifications are marked as read.'));
    }

    public function delete(string $id, NotificationService $notifications): void
    {
        $notifications->delete($this->actor(), $id);
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

        return view('livewire.notifications.index', [
            'notifications' => $this->notifications(),
            'unread' => $notifications->unreadCount($actor),
        ]);
    }

    /**
     * @return LengthAwarePaginator<int, DatabaseNotification>
     */
    private function notifications(): LengthAwarePaginator
    {
        $query = $this->tab === 'unread' ? $this->actor()->unreadNotifications() : $this->actor()->notifications();

        return $query->latest()->paginate(self::PER_PAGE);
    }
}
