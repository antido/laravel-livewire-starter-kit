<x-auth.card title="Log in" description="Welcome back. Enter your details to continue.">
    @if (session('error'))
        <x-ui.alert variant="danger" class="mb-4">{{ session('error') }}</x-ui.alert>
    @endif

    <form wire:submit="login" class="space-y-4">
        <x-ui.input label="Email address" type="email" wire:model="email" autocomplete="username" required autofocus />

        <div>
            <x-ui.input label="Password" type="password" wire:model="password" autocomplete="current-password" required />
            <div class="mt-1.5 text-right text-sm">
                <x-ui.link :href="route('password.request')">Forgot your password?</x-ui.link>
            </div>
        </div>

        <x-ui.checkbox label="Remember me" wire:model="remember" />

        <x-ui.button type="submit" class="w-full">Log in</x-ui.button>
    </form>

    @if (config('auth.registration.enabled'))
        <x-slot:footer>
            Don't have an account? <x-ui.link :href="route('register')">Sign up</x-ui.link>
        </x-slot:footer>
    @endif
</x-auth.card>
