<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RoleName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase, SeedsRolesAndPermissions;

    public function test_a_guest_sees_the_landing_page_with_log_in_and_sign_up(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('A clean foundation for your next app.')
            ->assertSee('href="'.route('login').'"', false)
            ->assertSee('href="'.route('register').'"', false)
            ->assertSee('Sign up')
            ->assertDontSee('Go to dashboard')
            ->assertSee('<title>'.config('app.name').'</title>', false)
            ->assertSee('<meta name="description"', false);
    }

    public function test_it_shows_the_six_features(): void
    {
        $this->get('/')->assertOk()->assertSeeInOrder([
            'Secure sign-in', 'Roles &amp; privileges', 'Employee records', 'Audit trails', 'Notifications', 'Light &amp; dark mode',
        ], false);
    }

    public function test_a_signed_in_user_sees_go_to_dashboard(): void
    {
        $this->actingAs($this->userWithRole(RoleName::User))
            ->get('/')
            ->assertOk()
            ->assertSee('Go to dashboard')
            ->assertSee('href="'.route('dashboard').'"', false)
            ->assertDontSee('href="'.route('register').'"', false);
    }

    public function test_sign_up_is_hidden_when_registration_is_disabled(): void
    {
        config(['auth.registration.enabled' => false]);

        $response = $this->get('/')->assertOk()->assertDontSee('Sign up')->assertDontSee('Create account');

        // "Get started" then points to the login page.
        $this->assertMatchesRegularExpression('#<a href="'.preg_quote(route('login'), '#').'"[^>]*data-test="get-started"#', $response->getContent() ?: '');
    }

    public function test_the_page_does_not_mention_the_out_of_scope_modules(): void
    {
        $content = strtolower(strip_tags($this->get('/')->assertOk()->getContent() ?: ''));

        foreach (['sales', 'inventory', 'suppliers', 'purchases', 'expenses', 'payroll', 'reports'] as $module) {
            $this->assertStringNotContainsString($module, $content, "The landing page mentions {$module}.");
        }
    }

    public function test_the_theme_toggle_is_in_the_top_bar(): void
    {
        $this->get('/')->assertOk()->assertSee('data-test="theme-toggle"', false);
    }
}
