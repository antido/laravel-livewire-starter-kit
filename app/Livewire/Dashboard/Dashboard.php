<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Models\User;
use App\Services\NavigationService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dashboard')]
class Dashboard extends Component
{
    public function render(NavigationService $navigation): View
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        $now = now();

        return view('livewire.dashboard.dashboard', [
            'user' => $user,
            'greeting' => match (true) {
                $now->hour < 12 => 'Good morning',
                $now->hour < 18 => 'Good afternoon',
                default => 'Good evening',
            },
            'today' => $now->format('l, j F Y'),
            'roles' => $user->getRoleNames(),
            'quickLinks' => $navigation->quickLinksFor($user),
            'unreadCount' => Route::has('notifications.index') ? $user->unreadNotifications()->count() : 0,
        ]);
    }
}
