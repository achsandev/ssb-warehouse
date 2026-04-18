<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Exceptions\UnauthorizedException;

class CustomPermissionMiddleware
{
    public function handle($request, Closure $next, ...$permissions)
    {
        $user = Auth::user();
        if (!$user) {
            throw UnauthorizedException::notLoggedIn();
        }
        // If user has manage.all, allow all
        if ($user->can('all.manage')) {
            return $next($request);
        }
        // Check if user has any of the required permissions (OR logic)
        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return $next($request);
            }
        }
        throw UnauthorizedException::forPermissions($permissions);
    }
}
