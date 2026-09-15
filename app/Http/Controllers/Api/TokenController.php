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

        // An account removed by a game archive: say the game is over rather than "wrong password",
        // so the app shows its end screen and stops calling us. A live account with the same e-mail
        // (a later event) logs in normally.
        if (! $user && ($archive = \App\Services\GameArchiveService::archiveForEmail($credentials['email']))) {
            return \App\Services\GameArchiveService::gameEndedResponse($archive);
        }

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($key, 300);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        return $this->issueToken($user, $credentials['device_name'] ?? 'android-client', fn () => RateLimiter::clear($key));
    }

    /**
     * A team logs in by scanning its printed login card (APK report #16, operator-approved 15 Sep).
     * Accepts the QR payload ("EUREKA-LOGIN:<code>") or the bare code; answers exactly like login.
     */
    public function loginCode(Request $request, \App\Services\LoginCardService $cards)
    {
        $data = $request->validate([
            'code' => 'required|string|max:200',
            'device_name' => 'nullable|string|max:120',
        ]);

        $hash = $cards->hashFor($data['code']);
        $card = \App\Models\LoginCard::with('user')->where('code_hash', $hash)->first();

        if (! $card || ! $card->user || $card->revoked_at) {
            if (! $card && ($archive = \App\Services\GameArchiveService::archiveForLoginCode($hash))) {
                return \App\Services\GameArchiveService::gameEndedResponse($archive);
            }

            // One answer for unknown, rotated and revoked: no hint whether the account exists.
            return response()->json([
                'success' => false,
                'error' => 'invalid_login_code',
                'message' => 'Kartu login tidak dikenali atau sudah tidak berlaku. Minta kartu baru ke panitia.',
            ], 401);
        }

        $response = $this->issueToken($card->user, $data['device_name'] ?? 'android-client');
        if ($response->getStatusCode() === 200) {
            $card->forceFill(['last_used_at' => now()])->save();
        }

        return $response;
    }

    /** The one place a login becomes a token: account state, access window, one token per device. */
    private function issueToken(User $user, string $device, ?\Closure $onSuccess = null)
    {
        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'error' => 'account_inactive',
                'message' => __('This account is not active.'),
            ], 403);
        }

        // An expired access window must not yield a token at all — otherwise the
        // client holds a credential the admin believes they revoked.
        if ($user->accessWindowExpired()) {
            return response()->json([
                'success' => false,
                'error' => 'access_window_expired',
                'message' => __('Your access period has ended. Please contact an administrator to be granted access again.'),
                'access_window_expired' => true,
            ], 403);
        }

        if ($onSuccess) {
            $onSuccess();
        }

        // Starts the clock on first login, exactly as the web flow does.
        $user->startAccessWindow();
        $user->forceFill(['last_login_at' => now()])->save();

        // One token per named device; re-authenticating replaces it rather than
        // accumulating tokens that nobody can revoke.
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
