<x-auth.card title="Choose a new password">
    <form wire:submit="resetPassword" class="space-y-4">
        <x-ui.input label="Email address" type="email" wire:model="email" autocomplete="username" required />
        <x-ui.input label="New password" type="password" wire:model="password" autocomplete="new-password" hint="At least 8 characters." required autofocus />
        <x-ui.input label="Confirm new password" type="password" wire:model="password_confirmation" autocomplete="new-password" required />

        <x-ui.button type="submit" class="w-full">Reset password</x-ui.button>
    </form>
</x-auth.card>
