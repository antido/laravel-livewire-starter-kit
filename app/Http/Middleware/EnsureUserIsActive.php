<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\AuthenticationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs out a user whose account was deactivated while their session was still open.
 */
class EnsureUserIsActive
{
    public function __construct(private readonly AuthenticationService $authentication) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && ! $user->isActive()) {
            $this->authentication->logout();

            if ($request->expectsJson()) {
                abort(Response::HTTP_FORBIDDEN, __(AuthenticationService::INACTIVE_MESSAGE));
            }

            return redirect()->route('login')->with('error', __(AuthenticationService::INACTIVE_MESSAGE));
        }

        return $next($request);
    }
}
