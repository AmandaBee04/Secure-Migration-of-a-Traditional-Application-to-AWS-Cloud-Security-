<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Token auth that never touches Eloquent user models.
 *
 * Replaces auth:sanctum + auth.role to avoid SIGSEGV caused by HasApiTokens
 * model boot on ECS Fargate (PHP 8.2 + Debian Bookworm).
 *
 * Token format: "{personal_access_token.id}|{plain_40_char_token}"
 * (same format Sanctum uses, so existing tokens are compatible)
 */
class RawTokenAuth
{
    // Maps tokenable_type → [role, table]
    private const ROLE_MAP = [
        'App\\Models\\Student'  => ['role' => 'student',  'table' => 'students'],
        'App\\Models\\Lecturer' => ['role' => 'lecturer', 'table' => 'lecturers'],
        'App\\Models\\Admin'    => ['role' => 'admin',    'table' => 'admins'],
    ];

    /**
     * @param  string  $requiredRole  optional — e.g. 'admin', 'lecturer', 'student'
     */
    public function handle(Request $request, Closure $next, string $requiredRole = ''): mixed
    {
        $header = $request->header('Authorization', '');

        if (! str_starts_with($header, 'Bearer ')) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $raw = substr($header, 7);

        if (! str_contains($raw, '|')) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        [$tokenId, $plainToken] = explode('|', $raw, 2);

        // Look up token record — uses PersonalAccessToken table directly, no model boot
        $tokenRecord = DB::table('personal_access_tokens')
            ->where('id', (int) $tokenId)
            ->first();

        if (! $tokenRecord ||
            ! hash_equals($tokenRecord->token, hash('sha256', $plainToken))
        ) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($tokenRecord->expires_at && now()->isAfter($tokenRecord->expires_at)) {
            return response()->json(['message' => 'Token expired.'], 401);
        }

        $map = self::ROLE_MAP[$tokenRecord->tokenable_type] ?? null;

        if (! $map) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Role restriction (raw.auth:admin, raw.auth:lecturer, raw.auth:student)
        if ($requiredRole && $map['role'] !== $requiredRole) {
            return response()->json(['message' => 'You have no authorization to access this page.'], 403);
        }

        // Load user row via DB::table — no Eloquent model boot
        $userData = DB::table($map['table'])
            ->where('id', $tokenRecord->tokenable_id)
            ->first();

        if (! $userData) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Stash on request attributes for AuthController / AuthRole to read
        $request->attributes->set('raw_user',     $userData);
        $request->attributes->set('raw_role',     $map['role']);
        $request->attributes->set('raw_token_id', $tokenRecord->id);

        // Make $request->user() work in controllers WITHOUT booting Eloquent models
        $request->setUserResolver(static function () use ($userData) {
            return $userData; // stdClass with id, name, email, password …
        });

        return $next($request);
    }
}
