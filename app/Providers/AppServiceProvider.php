<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\RoleName;
use App\Http\Middleware\EnsureUserIsActive;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\DevCommands;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureAuthorization();
        $this->configurePasswords();

        $this->configureLivewire();
        $this->configureDevCommands();
    }

    /**
     * Livewire actions (and the bell's polling) post to one update endpoint. Throttle it per user or
     * IP address, and keep signing out deactivated users on it too.
     */
    private function configureLivewire(): void
    {
        RateLimiter::for('livewire', fn (Request $request): Limit => Limit::perMinute(300)->by(
            $request->user()?->getAuthIdentifier() ?? $request->ip(),
        ));

        Livewire::setUpdateRoute(fn (array $handle, string $path) => Route::post($path, $handle)
            ->middleware(['web', 'throttle:livewire']));

        Livewire::addPersistentMiddleware([EnsureUserIsActive::class]);
    }

    /**
     * "composer dev" also runs the scheduler, which prunes old audit entries.
     */
    private function configureDevCommands(): void
    {
        if ($this->app->runningInConsole() && class_exists(DevCommands::class)) {
            DevCommands::artisan('schedule:work', 'scheduler');
        }
    }

    /**
     * The Super Admin passes every Gate check (Spatie's recommended approach). Rules that must hold
     * even for a Super Admin, such as protecting the last Super Admin, are enforced in the services.
     */
    private function configureAuthorization(): void
    {
        Gate::before(fn (User $user): ?bool => $user->hasRole(RoleName::SuperAdmin->value) ? true : null);
    }

    private function configurePasswords(): void
    {
        Password::defaults(fn (): Password => $this->app->isProduction()
            ? Password::min(8)->mixedCase()->numbers()->uncompromised()
            : Password::min(8));
    }
}
