<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Role check middleware — reads role from RawTokenAuth request attributes.
 *
 * Previously used $request->user() instanceof Student/Lecturer/Admin and
 * $user->currentAccessToken() (HasApiTokens).  Those caused SIGSEGV on ECS
 * Fargate.  Now reads pre-resolved role/user set by RawTokenAuth.
 */
class AuthRole
{
    public function handle(Request $request, Closure $next, ...$roles): mixed
    {
        $user = $request->attributes->get('raw_user');
        $role = $request->attributes->get('raw_role');

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if (! empty($roles) && ! in_array($role, $roles)) {
            return response()->json(['message' => 'You have no authorization to access this page'], 403);
        }

        return $next($request);
    }
}
