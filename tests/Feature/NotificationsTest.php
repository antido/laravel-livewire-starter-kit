<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Livewire\Admin\Users\Edit as UserEdit;
use App\Livewire\Admin\Users\ResetPassword;
use App\Livewire\Auth\Register;
use App\Livewire\Dashboard\Dashboard;
use App\Livewire\Employees\Create as EmployeeCreate;
use App\Livewire\Notifications\Bell;
use App\Livewire\Notifications\Index;
use App\Models\User;
use App\Notifications\EmployeeAccountLinkedNotification;
use App\Notifications\NewUserRegisteredNotification;
use App\Notifications\PasswordChangedByAdminNotification;
use App\Notifications\RolesChangedNotification;
use App\Notifications\WelcomeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase, SeedsRolesAndPermissions;

    /**
     * @param  array<string, mixed>  $data
     */
    private function notify(User $user, array $data = [], bool $read = false): DatabaseNotification
    {
        return $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'test',
            'data' => array_merge(['type' => 'info', 'title' => 'Hello', 'message' => 'A message', 'url' => '/dashboard'], $data),
            'read_at' => $read ? now() : null,
        ]);
    }

    // ---- Triggers ----

    public function test_self_registration_sends_welcome_and_new_user_notifications(): void
    {
        Notification::fake();
        $admin = $this->userWithRole(RoleName::Administrator);
        $staff = $this->userWithRole(RoleName::User);
        $superAdmin = $this->superAdmin();
        $inactiveAdmin = User::factory()->inactive()->withRole(RoleName::Administrator)->create();

        Livewire::test(Register::class)
            ->set('name', 'New Person')->set('email', 'new@example.com')
            ->set('password', 'password-123')->set('password_confirmation', 'password-123')
            ->call('register');

        $newUser = User::where('email', 'new@example.com')->sole();
        Notification::assertSentTo($newUser, WelcomeNotification::class);
        Notification::assertSentTo([$admin, $superAdmin], NewUserRegisteredNotification::class);
        Notification::assertNotSentTo([$staff, $inactiveAdmin, $newUser], NewUserRegisteredNotification::class);
    }

    public function test_a_role_change_notifies_the_affected_user(): void
    {
        Notification::fake();
        $target = $this->userWithRole(RoleName::User);

        Livewire::actingAs($this->userWithRole(RoleName::Administrator))->test(UserEdit::class, ['user' => $target])
            ->set('roles', [RoleName::Administrator->value])
            ->call('save');

        Notification::assertSentTo($target, RolesChangedNotification::class, fn ($notification) => $notification->toArray($target)['message'] === 'You are now: Administrator.');
    }

    public function test_an_admin_password_reset_notifies_the_user(): void
    {
        Notification::fake();
        $target = $this->userWithRole(RoleName::User);
        session()->put('auth.password_confirmed_at', time());

        Livewire::actingAs($this->userWithRole(RoleName::Administrator))->test(ResetPassword::class, ['user' => $target])
            ->set('password', 'brand-new-pass')->set('password_confirmation', 'brand-new-pass')
            ->call('save');

        Notification::assertSentTo($target, PasswordChangedByAdminNotification::class);
    }

    public function test_linking_an_employee_account_notifies_the_linked_user(): void
    {
        Notification::fake();
        $user = $this->userWithRole(RoleName::User);

        Livewire::actingAs($this->userWithRole(RoleName::Administrator))->test(EmployeeCreate::class)
            ->set('form.first_name', 'Juan')->set('form.last_name', 'Cruz')->set('form.position', 'Clerk')
            ->set('form.account_mode', 'link')->set('form.link_user_id', $user->id)
            ->call('save');

        Notification::assertSentTo($user, EmployeeAccountLinkedNotification::class);
    }

    public function test_notifications_are_stored_with_the_standard_payload_and_work_with_the_sync_queue(): void
    {
        $this->assertSame('sync', config('queue.default'));
        $target = $this->userWithRole(RoleName::User);

        Livewire::actingAs($this->userWithRole(RoleName::Administrator))->test(UserEdit::class, ['user' => $target])
            ->set('roles', [RoleName::Administrator->value])
            ->call('save');

        $stored = $target->notifications()->sole();
        $this->assertSame(['type', 'title', 'message', 'url'], array_keys($stored->data));
        $this->assertSame('/dashboard', $stored->data['url']);
    }

    public function test_no_password_or_token_appears_in_any_payload(): void
    {
        $target = $this->userWithRole(RoleName::User);
        session()->put('auth.password_confirmed_at', time());

        Livewire::actingAs($this->userWithRole(RoleName::Administrator))->test(ResetPassword::class, ['user' => $target])
            ->set('password', 'brand-new-pass')->set('password_confirmation', 'brand-new-pass')
            ->call('save');

        $payloads = DatabaseNotification::all()->map(fn ($n) => json_encode($n->data))->implode(' ');
        $this->assertStringNotContainsString('brand-new-pass', $payloads);
        $this->assertStringNotContainsString((string) $target->fresh()?->password, $payloads);
        $this->assertStringNotContainsStringIgnoringCase('token', $payloads);
    }

    // ---- Bell ----

    public function test_the_bell_count_matches_the_unread_notifications(): void
    {
        $user = $this->userWithRole(RoleName::User);
        $this->notify($user);
        $this->notify($user);
        $this->notify($user, read: true);

        Livewire::actingAs($user)->test(Bell::class)
            ->assertViewHas('unread', 2)
            ->assertSee('Notifications, 2 unread')
            ->assertSeeHtml('data-test="notification-count">2</span>');
    }

    public function test_the_bell_shows_9_plus_and_hides_the_badge_at_zero(): void
    {
        $user = $this->userWithRole(RoleName::User);

        Livewire::actingAs($user)->test(Bell::class)->assertDontSeeHtml('data-test="notification-count"');

        foreach (range(1, 12) as $i) {
            $this->notify($user);
        }

        Livewire::actingAs($user)->test(Bell::class)->assertSeeHtml('data-test="notification-count">9+</span>');
    }

    public function test_the_dropdown_loads_the_ten_latest_and_the_empty_state(): void
    {
        $user = $this->userWithRole(RoleName::User);

        Livewire::actingAs($user)->test(Bell::class)->call('load')->assertSeeHtml("You're all caught up.");

        foreach (range(1, 12) as $i) {
            $this->notify($user, ['title' => "Title {$i}"]);
        }

        Livewire::actingAs($user)->test(Bell::class)
            ->assertViewHas('latest', fn ($latest) => $latest->isEmpty())
            ->call('load')
            ->assertViewHas('latest', fn ($latest) => $latest->count() === 10);
    }

    public function test_clicking_an_item_marks_it_read_and_follows_internal_urls_only(): void
    {
        $user = $this->userWithRole(RoleName::User);
        $internal = $this->notify($user, ['url' => '/settings/appearance']);

        Livewire::actingAs($user)->test(Bell::class)->call('visit', $internal->id)->assertRedirect('/settings/appearance');
        $this->assertNotNull($internal->fresh()?->read_at);

        foreach (['https://evil.test/phish', '//evil.test', '/\\evil.test', 'javascript:alert(1)', 'dashboard', "/\tfoo"] as $bad) {
            $external = $this->notify($user, ['url' => $bad]);

            Livewire::actingAs($user)->test(Bell::class)->call('visit', $external->id)->assertNoRedirect();
            $this->assertNotNull($external->fresh()?->read_at);
        }
    }

    public function test_mark_all_as_read(): void
    {
        $user = $this->userWithRole(RoleName::User);
        $this->notify($user);
        $this->notify($user);

        Livewire::actingAs($user)->test(Bell::class)->call('markAllAsRead')->assertViewHas('unread', 0);

        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    public function test_the_bell_is_in_the_header(): void
    {
        $this->actingAs($this->userWithRole(RoleName::User))->get(route('dashboard'))->assertSeeLivewire(Bell::class);
    }

    // ---- Page ----

    public function test_the_page_lists_notifications_with_tabs(): void
    {
        $user = $this->userWithRole(RoleName::User);
        $this->notify($user, ['title' => 'Unread one']);
        $this->notify($user, ['title' => 'Read one'], read: true);

        $this->actingAs($user)->get(route('notifications.index'))->assertOk()->assertSee('Unread one')->assertSee('Read one');

        Livewire::actingAs($user)->test(Index::class)
            ->call('showTab', 'unread')
            ->assertSet('tab', 'unread')
            ->assertSee('Unread one')
            ->assertDontSee('Read one');
    }

    public function test_the_page_needs_no_permission_but_requires_sign_in(): void
    {
        $this->get(route('notifications.index'))->assertRedirect(route('login'));

        // A user without any role can still read their own notifications.
        $this->actingAs(User::factory()->create())->get(route('notifications.index'))->assertOk();
    }

    public function test_the_page_paginates_by_20(): void
    {
        $user = $this->userWithRole(RoleName::User);
        foreach (range(1, 25) as $i) {
            $this->notify($user);
        }

        Livewire::actingAs($user)->test(Index::class)->assertViewHas('notifications', fn ($page) => $page->count() === 20 && $page->total() === 25);
    }

    public function test_mark_read_unread_all_and_delete(): void
    {
        $user = $this->userWithRole(RoleName::User);
        $first = $this->notify($user);
        $second = $this->notify($user);

        Livewire::actingAs($user)->test(Index::class)->call('markAsRead', $first->id);
        $this->assertNotNull($first->fresh()?->read_at);

        Livewire::actingAs($user)->test(Index::class)->call('markAsUnread', $first->id);
        $this->assertNull($first->fresh()?->read_at);

        Livewire::actingAs($user)->test(Index::class)->call('markAllAsRead');
        $this->assertSame(0, $user->unreadNotifications()->count());

        Livewire::actingAs($user)->test(Index::class)->call('delete', $second->id);
        $this->assertModelMissing($second);
    }

    // ---- IDOR ----

    public function test_a_user_cannot_touch_another_users_notification(): void
    {
        $owner = $this->userWithRole(RoleName::User);
        $intruder = $this->userWithRole(RoleName::User);
        $notification = $this->notify($owner, ['title' => 'Private']);

        foreach (['markAsRead', 'markAsUnread', 'delete', 'open'] as $action) {
            Livewire::actingAs($intruder)->test(Index::class)->call($action, $notification->id)->assertNotFound();
        }

        Livewire::actingAs($intruder)->test(Bell::class)->call('visit', $notification->id)->assertNotFound();
        Livewire::actingAs($intruder)->test(Index::class)->assertDontSee('Private');

        $this->assertModelExists($notification);
        $this->assertNull($notification->fresh()?->read_at);
    }

    public function test_malformed_ids_are_rejected(): void
    {
        $user = $this->userWithRole(RoleName::User);

        Livewire::actingAs($user)->test(Index::class)->call('delete', "1' OR '1'='1")->assertNotFound();
        Livewire::actingAs($user)->test(Bell::class)->call('visit', 'not-a-uuid')->assertNotFound();
    }

    // ---- Dashboard hook ----

    public function test_the_dashboard_shows_the_unread_line_only_when_there_are_unread_notifications(): void
    {
        $user = $this->userWithRole(RoleName::User);

        Livewire::actingAs($user)->test(Dashboard::class)->assertDontSee('unread notification');

        $this->notify($user);
        $this->notify($user);

        Livewire::actingAs($user)->test(Dashboard::class)->assertSee('You have 2 unread notifications.');
    }
}
