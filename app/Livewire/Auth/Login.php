<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthenticationService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::guest')]
#[Title('Log in')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function login(AuthenticationService $authentication): void
    {
        try {
            $this->validate(LoginRequest::rules());

            $authentication->attempt($this->email, $this->password, $this->remember, (string) request()->ip());
        } finally {
            // Never send the password back to the browser in the component snapshot.
            $this->reset('password');
        }

        $this->redirectIntended(route('dashboard', absolute: false));
    }

    public function render(): View
    {
        return view('livewire.auth.login');
    }
}
