<x-auth.card title="Verify your email" description="Before you continue, please confirm your email address with the link we just sent you.">
    @if ($status)
        <x-ui.alert variant="success" class="mb-4">{{ $status }}</x-ui.alert>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <x-ui.button wire:click="resend">Resend verification email</x-ui.button>
        <x-ui.button variant="ghost" wire:click="logout">Log out</x-ui.button>
    </div>
</x-auth.card>
