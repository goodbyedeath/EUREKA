<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Token authentication for native clients.
 *
 * Deliberately separate from the web login: the browser keeps its session cookie
 * and nothing about the existing PWA changes. This only issues bearer tokens.
 */
class TokenController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:120',
        ]);

        // Same throttle shape as the web login, keyed per email+IP.
        $key = 'api-login:' . strtolower($credentials['email']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'success' => false,
                'message' => __('Too many attempts. Try again in :seconds seconds.', [
                    'seconds' => RateLimiter::availableIn($key),
                ]),
            ], 429);
        }

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($key, 300);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        if (property_exists($user, 'is_active') || isset($user->is_active)) {
            if (! $user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => __('This account is not active.'),
                ], 403);
            }
        }

        // An expired access window must not yield a token at all — otherwise the
        // client holds a credential the admin believes they revoked.
        if ($user->accessWindowExpired()) {
            return response()->json([
                'success' => false,
                'message' => __('Your access period has ended. Please contact an administrator to be granted access again.'),
                'access_window_expired' => true,
            ], 403);
        }

        RateLimiter::clear($key);

        // Starts the clock on first login, exactly as the web flow does.
        $user->startAccessWindow();
        $user->forceFill(['last_login_at' => now()])->save();

        // One token per named device; re-authenticating replaces it rather than
        // accumulating tokens that nobody can revoke.
        $device = $credentials['device_name'] ?? 'android-client';
        $user->tokens()->where('name', $device)->delete();

        $token = $user->createToken($device, ['*'], $user->accessWindowEndsAt());

        return response()->json([
            'success' => true,
            'token' => $token->plainTextToken,
            'expires_at' => optional($user->accessWindowEndsAt())->toIso8601String(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
                'team_id' => $user->team_id,
                'locale' => app()->getLocale(),
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Cheap call for the client to confirm a token is still good and learn how
     * much of the access window is left.
     */
    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
                'team_id' => $user->team_id,
            ],
            'access_window_ends_at' => optional($user->accessWindowEndsAt())->toIso8601String(),
        ]);
    }
}
