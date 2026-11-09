<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (method_exists($user, 'isActive') && ! $user->isActive()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => __('Email atau kata sandi salah.'),
            ]);
        }

        $userRole = is_object($user->role) ? $user->role->value : (string) $user->role;

        if (! in_array($userRole, $roles, true)) {
            abort(403, 'Unauthorized. Your account role ('.ucfirst($userRole).') does not have access to this resource.');
        }

        return $next($request);
    }
}
