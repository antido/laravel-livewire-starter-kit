<x-auth.card title="Forgot your password?" description="Enter your email address and we will send you a link to choose a new one.">
    @if ($status)
        <x-ui.alert variant="success" class="mb-4">{{ $status }}</x-ui.alert>
    @endif

    <form wire:submit="sendResetLink" class="space-y-4">
        <x-ui.input label="Email address" type="email" wire:model="email" autocomplete="username" required autofocus />

        <x-ui.button type="submit" class="w-full">Email reset link</x-ui.button>
    </form>

    <x-slot:footer>
        <x-ui.link :href="route('login')">Back to log in</x-ui.link>
    </x-slot:footer>
</x-auth.card>
