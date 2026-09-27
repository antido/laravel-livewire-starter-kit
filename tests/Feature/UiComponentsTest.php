<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class UiComponentsTest extends TestCase
{
    public function test_the_button_renders_its_variants_and_a_link_when_given_an_href(): void
    {
        $this->blade('<x-ui.button variant="danger">Delete</x-ui.button>')
            ->assertSee('Delete')
            ->assertSee('type="button"', false)
            ->assertSee('bg-red-600', false);

        $this->blade('<x-ui.button href="/somewhere">Go</x-ui.button>')
            ->assertSee('<a href="/somewhere"', false)
            ->assertDontSee('<button', false);

        $this->blade('<x-ui.button type="submit">Save</x-ui.button>')
            ->assertSee('type="submit"', false)
            ->assertSee('wire:loading.attr="disabled"', false);
    }

    public function test_a_disabled_button_with_an_href_renders_as_a_disabled_button(): void
    {
        $this->blade('<x-ui.button href="/somewhere" :disabled="true">Go</x-ui.button>')
            ->assertDontSee('href="/somewhere"', false)
            ->assertSee('disabled', false);
    }

    public function test_the_input_shows_its_label_hint_and_validation_error(): void
    {
        $this->blade('<x-ui.input label="Email address" name="email" hint="We never share it." />')
            ->assertSee('<label for="field-email"', false)
            ->assertSee('We never share it.');

        $errors = (new ViewErrorBag)->put('default', new MessageBag(['email' => ['The email field is required.']]));
        view()->share('errors', $errors);

        $this->blade('<x-ui.input label="Email address" wire:model="email" />')
            ->assertSee('The email field is required.')
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('aria-describedby="field-email-error"', false);
    }

    public function test_the_password_input_has_a_show_password_toggle(): void
    {
        $this->blade('<x-ui.input label="Password" type="password" name="password" />')
            ->assertSee('aria-label="Show password"', false)
            ->assertSee('aria-controls="field-password"', false);
    }

    public function test_the_badge_renders_each_variant(): void
    {
        $this->blade('<x-ui.badge variant="success">Active</x-ui.badge>')
            ->assertSee('Active')
            ->assertSee('bg-emerald-50', false);

        $this->blade('<x-ui.badge variant="accent">New</x-ui.badge>')->assertSee('bg-accent-50', false);
        $this->blade('<x-ui.badge variant="unknown">Fallback</x-ui.badge>')->assertSee('bg-zinc-100', false);
    }

    public function test_the_alert_renders_its_role_and_can_be_dismissible(): void
    {
        $this->blade('<x-ui.alert variant="danger" title="Oops">Something failed.</x-ui.alert>')
            ->assertSee('role="alert"', false)
            ->assertSee('Oops')
            ->assertSee('Something failed.')
            ->assertDontSee('aria-label="Dismiss"', false);

        $this->blade('<x-ui.alert variant="info" dismissible>FYI</x-ui.alert>')
            ->assertSee('role="status"', false)
            ->assertSee('aria-label="Dismiss"', false);
    }

    public function test_the_empty_state_renders_its_title_message_and_action(): void
    {
        $this->blade('<x-ui.empty-state title="No users found" message="Try another search."><a href="/new">Add user</a></x-ui.empty-state>')
            ->assertSee('No users found')
            ->assertSee('Try another search.')
            ->assertSee('Add user');
    }

    public function test_components_escape_user_content(): void
    {
        $this->blade('<x-ui.badge>{{ $name }}</x-ui.badge>', ['name' => '<script>alert(1)</script>'])
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;', false);

        $this->blade('<x-ui.empty-state :title="$name" />', ['name' => '<img src=x onerror=alert(1)>'])
            ->assertDontSee('<img src=x', false);

        $this->blade('<x-ui.avatar :name="$name" />', ['name' => '<b>Bold</b> Name'])
            ->assertDontSee('<b>', false);
    }

    public function test_the_sortable_table_header_announces_its_sort_order(): void
    {
        $this->blade('<x-ui.table.th sortable direction="desc">Name</x-ui.table.th>')
            ->assertSee('aria-sort="descending"', false)
            ->assertSee('<button', false);

        $this->blade('<x-ui.table.th>Email</x-ui.table.th>')
            ->assertDontSee('aria-sort', false);
    }

    public function test_the_toast_container_shows_flashed_messages(): void
    {
        session()->flash('success', 'Profile saved.');

        $this->blade('<x-ui.toast />')
            ->assertSee('Profile saved.')
            ->assertSee('$store.toasts', false);
    }

    public function test_the_component_preview_is_not_available_outside_the_local_environment(): void
    {
        $this->assertFalse(app()->environment('local'));

        $this->get('/dev/components')->assertNotFound();
    }
}
