<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthenticationService;
use Illuminate\Http\RedirectResponse;

class LogoutController extends Controller
{
    public function __invoke(AuthenticationService $authentication): RedirectResponse
    {
        $authentication->logout();

        return redirect('/');
    }
}
