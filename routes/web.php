<?php

declare(strict_types=1);

use App\Livewire\Admin\AuditTrails;
use App\Livewire\Admin\Permissions;
use App\Livewire\Admin\Roles;
use App\Livewire\Admin\Users;
use App\Livewire\Dashboard\Dashboard;
use App\Livewire\Employees;
use App\Livewire\Notifications;
use App\Livewire\Settings\Appearance;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified', 'active'])->group(function (): void {
    Route::livewire('dashboard', Dashboard::class)->middleware('permission:dashboard.view')->name('dashboard');

    // Personal pages: every user sees and manages only their own, so no permission is needed.
    Route::livewire('settings/appearance', Appearance::class)->name('settings.appearance');
    Route::livewire('notifications', Notifications\Index::class)->name('notifications.index');

    Route::livewire('employees', Employees\Index::class)->middleware('permission:employees.view')->name('employees.index');
    Route::livewire('employees/create', Employees\Create::class)->middleware('permission:employees.create')->name('employees.create');
    // Archived employees can still be viewed.
    Route::livewire('employees/{employee}', Employees\Show::class)->middleware('permission:employees.view')->name('employees.show')->withTrashed();
    Route::livewire('employees/{employee}/edit', Employees\Edit::class)->middleware('permission:employees.update')->name('employees.edit');

    // Administration. Each route checks its permission here, and each component authorizes again
    // through the policies on mount and in every action.
    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::livewire('users', Users\Index::class)->middleware('permission:users.view')->name('users.index');
        Route::livewire('users/create', Users\Create::class)->middleware('permission:users.create')->name('users.create');
        Route::livewire('users/{user}', Users\Show::class)->middleware('permission:users.view')->name('users.show');
        Route::livewire('users/{user}/edit', Users\Edit::class)->middleware('permission:users.update')->name('users.edit');
        Route::livewire('users/{user}/reset-password', Users\ResetPassword::class)
            ->middleware(['permission:users.update', 'password.confirm'])
            ->name('users.reset-password');

        Route::livewire('roles', Roles\Index::class)->middleware('permission:roles.view')->name('roles.index');
        Route::livewire('roles/create', Roles\Create::class)->middleware('permission:roles.create')->name('roles.create');
        Route::livewire('roles/{role}/edit', Roles\Edit::class)->middleware('permission:roles.update')->name('roles.edit');

        Route::livewire('permissions', Permissions\Index::class)->middleware('permission:permissions.view')->name('permissions.index');

        Route::livewire('audit-trails', AuditTrails\Index::class)->middleware('permission:audit_trails.view')->name('audit-trails.index');
    });
});

// A preview of every shared UI component in both themes. Only registered in the local environment.
if (app()->environment('local')) {
    Route::view('dev/components', 'dev.components')->name('dev.components');
}

require __DIR__.'/auth.php';
