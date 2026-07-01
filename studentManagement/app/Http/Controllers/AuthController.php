<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Auth controller — uses DB::table() exclusively for login token creation.
 *
 * Reason: Student/Lecturer/Admin models previously used HasApiTokens which
 * causes PHP-FPM SIGSEGV on ECS Fargate during model boot.  HasApiTokens has
 * been removed from those models.  We create Sanctum-compatible tokens manually
 * so the token format is unchanged and the rest of the app still works.
 *
 * Token format: "{personal_access_token.id}|{40-char-random}"  (Sanctum-standard)
 * Token stored:  hash('sha256', $plainToken)
 */
class AuthController extends Controller
{
    private const TABLES = [
        'student'  => 'students',
        'lecturer' => 'lecturers',
        'admin'    => 'admins',
    ];

    public function login(Request $request)
    {
        $id       = trim((string) $request->input('id', ''));
        $password = (string) $request->input('password', '');

        if ($id === '' || $password === '') {
            return response()->json(['message' => 'ID and password are required.'], 422);
        }

        $user = null;
        $role = null;

        foreach (self::TABLES as $r => $table) {
            $row = DB::table($table)->where('id', $id)->first();
            if ($row) {
                $user = $row;
                $role = $r;
                break;
            }
        }

        if (!$user || !password_verify($password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Create a Sanctum-compatible token without touching Eloquent
        $plainToken = Str::random(40);
        $expiresAt  = now()->addHour()->toDateTimeString();

        $tokenableType = match ($role) {
            'student'  => 'App\\Models\\Student',
            'lecturer' => 'App\\Models\\Lecturer',
            'admin'    => 'App\\Models\\Admin',
        };

        $tokenId = DB::table('personal_access_tokens')->insertGetId([
            'tokenable_type' => $tokenableType,
            'tokenable_id'   => $user->id,
            'name'           => 'auth_token',
            'token'          => hash('sha256', $plainToken),
            'abilities'      => '["*"]',
            'expires_at'     => $expiresAt,
            'created_at'     => now()->toDateTimeString(),
            'updated_at'     => now()->toDateTimeString(),
        ]);

        return response()->json([
            'message'    => 'Login successful',
            'token'      => "{$tokenId}|{$plainToken}",
            'expires_at' => $expiresAt,
            'role'       => $role,
            'user'       => [
                'id'   => $user->id,
                'name' => $user->name,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        // Token already validated by RawTokenAuth middleware
        $tokenId = $request->attributes->get('raw_token_id');

        if ($tokenId) {
            DB::table('personal_access_tokens')->where('id', $tokenId)->delete();
        }

        $user = $request->attributes->get('raw_user');

        return response()->json([
            'message' => 'Logout successful',
            'user'    => $user ? ['id' => $user->id, 'name' => $user->name] : null,
        ]);
    }

    public function profile(Request $request)
    {
        $user = $request->attributes->get('raw_user');
        $role = $request->attributes->get('raw_role');

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        return response()->json([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email ?? null,
            'role'  => $role,
        ]);
    }
}
