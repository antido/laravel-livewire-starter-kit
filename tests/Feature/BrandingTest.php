<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

/**
 * No page or email shows the word "Laravel" to people using the app.
 */
class BrandingTest extends TestCase
{
    use RefreshDatabase, SeedsRolesAndPermissions;

    /**
     * The text a visitor can read: tags, scripts and styles removed.
     */
    private function visibleText(string $html): string
    {
        $html = (string) preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $html);

        return html_entity_decode(strip_tags($html));
    }

    public function test_the_default_app_name_is_not_laravel(): void
    {
        $this->assertStringNotContainsStringIgnoringCase('laravel', (string) config('app.name'));
    }

    public function test_guest_pages_do_not_mention_laravel(): void
    {
        foreach (['/', '/login', '/register', '/forgot-password', '/reset-password/some-token', '/no-such-page'] as $url) {
            $this->assertStringNotContainsStringIgnoringCase('laravel', $this->visibleText((string) $this->get($url)->getContent()), $url);
        }
    }

    public function test_signed_in_pages_do_not_mention_laravel(): void
    {
        $superAdmin = $this->superAdmin();
        $employee = Employee::factory()->create();

        $urls = [
            route('dashboard'), route('employees.index'), route('employees.create'), route('employees.show', $employee),
            route('admin.users.index'), route('admin.users.show', $superAdmin), route('admin.roles.index'), route('admin.roles.create'),
            route('admin.permissions.index'), route('admin.audit-trails.index'), route('notifications.index'), route('settings.appearance'),
        ];

        foreach ($urls as $url) {
            $content = (string) $this->actingAs($superAdmin)->get($url)->assertOk()->getContent();
            $this->assertStringNotContainsStringIgnoringCase('laravel', $this->visibleText($content), $url);
            $this->assertStringContainsString((string) config('app.name'), $content, $url);
        }

        $this->assertStringNotContainsStringIgnoringCase('laravel', $this->visibleText((string) $this->actingAs($this->userWithRole(RoleName::User))->get(route('dashboard'))->getContent()));
    }

    public function test_emails_do_not_mention_laravel(): void
    {
        $user = User::factory()->unverified()->create();

        foreach ([(new VerifyEmail)->toMail($user), (new ResetPassword('token'))->toMail($user)] as $mail) {
            $this->assertStringNotContainsStringIgnoringCase('laravel', $this->visibleText((string) $mail->render()));
        }
    }
}
