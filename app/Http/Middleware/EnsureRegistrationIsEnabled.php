<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hides the sign-up routes (404) when REGISTRATION_ENABLED is false.
 */
class EnsureRegistrationIsEnabled
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless((bool) config('auth.registration.enabled'), Response::HTTP_NOT_FOUND);

        return $next($request);
    }
}
