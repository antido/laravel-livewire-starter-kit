<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Livewire\Auth\ConfirmPassword;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Auth\VerifyEmail;
use App\Models\User;
use App\Services\AuthenticationService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailNotification;
use Illuminate\Auth\SessionGuard;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase, SeedsRolesAndPermissions;

    // ---- Registration ----

    public function test_the_sign_up_page_renders(): void
    {
        $this->get(route('register'))->assertOk()->assertSeeLivewire(Register::class);
    }

    public function test_a_user_can_register_and_receives_the_default_role(): void
    {
        Event::fake([Registered::class]);

        Livewire::test(Register::class)
            ->set('name', 'Juan Dela Cruz')
            ->set('email', 'juan@example.com')
            ->set('password', 'password-123')
            ->set('password_confirmation', 'password-123')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'juan@example.com')->sole();
        $this->assertSame([RoleName::User->value], $user->getRoleNames()->all());
        $this->assertTrue(Hash::check('password-123', $user->password));
        $this->assertAuthenticatedAs($user);
        Event::assertDispatched(Registered::class);
    }

    public function test_registration_never_takes_a_role_from_the_request(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Sneaky')
            ->set('email', 'sneaky@example.com')
            ->set('password', 'password-123')
            ->set('password_confirmation', 'password-123')
            ->call('register');

        $this->assertFalse(User::where('email', 'sneaky@example.com')->sole()->hasRole(RoleName::SuperAdmin->value));

        // A crafted Livewire payload cannot add a property the component does not have.
        $this->expectException(\Throwable::class);
        Livewire::test(Register::class)->set('role', RoleName::SuperAdmin->value);
    }

    public function test_the_default_role_follows_the_configuration(): void
    {
        config(['auth.registration.default_role' => RoleName::Administrator->value]);

        Livewire::test(Register::class)
            ->set('name', 'Ana')
            ->set('email', 'ana@example.com')
            ->set('password', 'password-123')
            ->set('password_confirmation', 'password-123')
            ->call('register');

        $this->assertSame([RoleName::Administrator->value], User::where('email', 'ana@example.com')->sole()->getRoleNames()->all());
    }

    public function test_the_default_role_can_never_be_super_admin(): void
    {
        config(['auth.registration.default_role' => RoleName::SuperAdmin->value]);

        $this->expectException(\RuntimeException::class);

        Livewire::test(Register::class)
            ->set('name', 'Ana')
            ->set('email', 'ana@example.com')
            ->set('password', 'password-123')
            ->set('password_confirmation', 'password-123')
            ->call('register');
    }

    public function test_registration_validates_its_input(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        Livewire::test(Register::class)
            ->set('name', '')
            ->set('email', 'taken@example.com')
            ->set('password', 'short')
            ->set('password_confirmation', 'different')
            ->call('register')
            ->assertHasErrors(['name' => 'required', 'email' => 'unique', 'password']);
    }

    public function test_registration_is_blocked_when_disabled(): void
    {
        config(['auth.registration.enabled' => false]);

        $this->get('/register')->assertNotFound();
        $this->get(route('login'))->assertOk()->assertDontSee('Sign up');

        Livewire::test(Register::class)->assertStatus(404);
        $this->assertSame(0, User::count());
    }

    // ---- Login and logout ----

    public function test_the_login_page_renders(): void
    {
        $this->get(route('login'))->assertOk()->assertSeeLivewire(Login::class)->assertSee('Sign up');
    }

    public function test_a_user_can_log_in(): void
    {
        $user = User::factory()->withRole(RoleName::User)->create(['password' => 'secret-pass-1']);

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'secret-pass-1')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false))
            ->assertSet('password', '');

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()?->last_login_at);
    }

    public function test_invalid_credentials_get_one_generic_message(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass-1']);

        $wrongPassword = Livewire::test(Login::class)
            ->set('email', $user->email)->set('password', 'wrong')->call('login')
            ->assertHasErrors('email');
        $unknownEmail = Livewire::test(Login::class)
            ->set('email', 'nobody@example.com')->set('password', 'wrong')->call('login')
            ->assertHasErrors('email');

        $this->assertSame(__('auth.failed'), $wrongPassword->errors()->first('email'));
        $this->assertSame(__('auth.failed'), $unknownEmail->errors()->first('email'));
        $this->assertGuest();
    }

    public function test_login_is_throttled_after_five_failures(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass-1']);

        for ($i = 0; $i < AuthenticationService::MAX_LOGIN_ATTEMPTS; $i++) {
            Livewire::test(Login::class)->set('email', $user->email)->set('password', 'wrong')->call('login');
        }

        $component = Livewire::test(Login::class)
            ->set('email', $user->email)->set('password', 'secret-pass-1')->call('login')
            ->assertHasErrors('email');

        $this->assertStringContainsString('Too many login attempts', $component->errors()->first('email'));
        $this->assertGuest();
    }

    public function test_an_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->inactive()->withRole(RoleName::User)->create(['password' => 'secret-pass-1']);

        $component = Livewire::test(Login::class)
            ->set('email', $user->email)->set('password', 'secret-pass-1')->call('login')
            ->assertHasErrors('email');

        $this->assertSame(AuthenticationService::INACTIVE_MESSAGE, $component->errors()->first('email'));
        $this->assertGuest();
    }

    public function test_an_inactive_user_with_a_wrong_password_gets_the_generic_message(): void
    {
        $user = User::factory()->inactive()->create(['password' => 'secret-pass-1']);

        $component = Livewire::test(Login::class)
            ->set('email', $user->email)->set('password', 'wrong')->call('login');

        $this->assertSame(__('auth.failed'), $component->errors()->first('email'));
    }

    public function test_an_open_session_ends_when_the_user_is_deactivated(): void
    {
        $user = User::factory()->withRole(RoleName::User)->create();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $user->forceFill(['status' => 'inactive'])->save();

        $this->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', AuthenticationService::INACTIVE_MESSAGE);
        $this->assertGuest();
    }

    public function test_the_session_is_regenerated_on_login(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass-1']);

        $this->startSession();
        $before = session()->getId();

        app(AuthenticationService::class)->attempt($user->email, 'secret-pass-1', false, '127.0.0.1');

        $this->assertNotSame($before, session()->getId());
    }

    public function test_logout_invalidates_the_session(): void
    {
        $user = User::factory()->withRole(RoleName::User)->create();
        $this->actingAs($user);
        session()->put('marker', 'kept-before-logout');
        $token = session()->token();

        $this->post(route('logout'))->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull(session('marker'));
        $this->assertNotSame($token, session()->token());
    }

    public function test_logout_requires_post(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/logout')->assertMethodNotAllowed();
        $this->assertAuthenticated();
    }

    public function test_guests_are_redirected_from_the_dashboard_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_the_dashboard_requires_the_dashboard_permission(): void
    {
        $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertForbidden();
        $this->actingAs($this->userWithRole(RoleName::User))->get(route('dashboard'))->assertOk();
    }

    public function test_signed_in_users_cannot_open_the_guest_pages(): void
    {
        $this->actingAs($this->userWithRole(RoleName::User));

        $this->get(route('login'))->assertRedirect();
        $this->get(route('register'))->assertRedirect();
    }

    // ---- Password reset ----

    public function test_a_reset_link_can_be_requested(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        Livewire::test(ForgotPassword::class)
            ->set('email', $user->email)
            ->call('sendResetLink')
            ->assertHasNoErrors()
            ->assertSet('status', ForgotPassword::SENT_MESSAGE);

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_the_reset_request_does_not_reveal_whether_an_email_exists(): void
    {
        Notification::fake();

        Livewire::test(ForgotPassword::class)
            ->set('email', 'nobody@example.com')
            ->call('sendResetLink')
            ->assertHasNoErrors()
            ->assertSet('status', ForgotPassword::SENT_MESSAGE);

        Notification::assertNothingSent();
    }

    public function test_the_password_can_be_reset_with_a_valid_token(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        Livewire::test(ForgotPassword::class)->set('email', $user->email)->call('sendResetLink');

        Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use ($user): bool {
            $this->get(route('password.reset', ['token' => $notification->token, 'email' => $user->email]))->assertOk();

            Livewire::test(ResetPassword::class, ['token' => $notification->token])
                ->set('email', $user->email)
                ->set('password', 'brand-new-pass')
                ->set('password_confirmation', 'brand-new-pass')
                ->call('resetPassword')
                ->assertHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });

        $this->assertTrue(Hash::check('brand-new-pass', (string) $user->fresh()?->password));
    }

    public function test_an_invalid_reset_token_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass-1']);

        Livewire::test(ResetPassword::class, ['token' => 'not-a-real-token'])
            ->set('email', $user->email)
            ->set('password', 'brand-new-pass')
            ->set('password_confirmation', 'brand-new-pass')
            ->call('resetPassword')
            ->assertHasErrors('email');

        $this->assertTrue(Hash::check('secret-pass-1', (string) $user->fresh()?->password));
    }

    // ---- Email verification ----

    public function test_unverified_users_are_sent_to_the_verification_notice(): void
    {
        $user = User::factory()->unverified()->withRole(RoleName::User)->create();

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('verification.notice'));
        $this->get(route('verification.notice'))->assertOk()->assertSeeLivewire(VerifyEmail::class);
    }

    public function test_the_verification_email_can_be_resent(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        Livewire::actingAs($user)->test(VerifyEmail::class)->call('resend');

        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_the_email_can_be_verified_with_the_signed_link(): void
    {
        $user = User::factory()->unverified()->withRole(RoleName::User)->create();

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)->get($url)->assertRedirect(route('dashboard', absolute: false));
        $this->assertTrue((bool) $user->fresh()?->hasVerifiedEmail());
    }

    public function test_a_tampered_verification_link_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1('someone-else@example.com'),
        ]);

        $this->actingAs($user)->get($url)->assertForbidden();
        $this->assertFalse((bool) $user->fresh()?->hasVerifiedEmail());
    }

    // ---- Password confirmation ----

    public function test_the_password_can_be_confirmed(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass-1']);

        $this->actingAs($user)->get(route('password.confirm'))->assertOk();

        Livewire::actingAs($user)->test(ConfirmPassword::class)
            ->set('password', 'secret-pass-1')
            ->call('confirm')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertNotNull(session('auth.password_confirmed_at'));
    }

    public function test_a_wrong_password_is_not_confirmed(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass-1']);

        Livewire::actingAs($user)->test(ConfirmPassword::class)
            ->set('password', 'wrong')
            ->call('confirm')
            ->assertHasErrors('password');

        $this->assertNull(session('auth.password_confirmed_at'));
    }

    public function test_the_web_routes_use_csrf_protection_and_session_authentication(): void
    {
        // Laravel skips the CSRF check while running tests, so assert the middleware is in the web group.
        $this->assertContains(PreventRequestForgery::class, app('router')->getMiddlewareGroups()['web']);
        $this->assertInstanceOf(SessionGuard::class, Auth::guard('web'));
    }
}
