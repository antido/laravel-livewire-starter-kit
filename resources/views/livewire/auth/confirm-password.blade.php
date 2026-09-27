<x-auth.card title="Confirm your password" description="This is a secure area. Please confirm your password to continue.">
    <form wire:submit="confirm" class="space-y-4">
        <x-ui.input label="Password" type="password" wire:model="password" autocomplete="current-password" required autofocus />

        <x-ui.button type="submit" class="w-full">Confirm</x-ui.button>
    </form>
</x-auth.card>
