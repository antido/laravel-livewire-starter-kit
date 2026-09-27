<x-auth.card title="Create an account" description="It only takes a minute.">
    <form wire:submit="register" class="space-y-4">
        <x-ui.input label="Full name" wire:model="name" autocomplete="name" required autofocus />
        <x-ui.input label="Email address" type="email" wire:model="email" autocomplete="username" required />
        <x-ui.input label="Password" type="password" wire:model="password" autocomplete="new-password" hint="At least 8 characters." required />
        <x-ui.input label="Confirm password" type="password" wire:model="password_confirmation" autocomplete="new-password" required />

        <x-ui.button type="submit" class="w-full">Sign up</x-ui.button>
    </form>

    <x-slot:footer>
        Already have an account? <x-ui.link :href="route('login')">Log in</x-ui.link>
    </x-slot:footer>
</x-auth.card>
