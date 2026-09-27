<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the email as verified. EmailVerificationRequest checks the signed link against the signed-in user.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if (! $request->user()?->hasVerifiedEmail()) {
            $request->fulfill();
        }

        return redirect()->intended(route('dashboard', absolute: false))->with('success', __('Your email address is verified.'));
    }
}
