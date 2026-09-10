<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $this->checkTooManyFailedAttempts($request);

        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            // Refuse an account whose admin-granted window has already run out.
            //
            // Checked AFTER the password, not before. Run first, it answered a distinct
            // "your access period has ended" to anyone who typed an email — no password
            // needed — which told an attacker both that the account exists and what state
            // it is in, and burned an audit log line per probe.
            if (Auth::user()->accessWindowExpired()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $this->logAuthEvent('login_blocked_window_expired', $request);

                throw ValidationException::withMessages([
                    'email' => __('Your access period has ended. Please contact an administrator to be granted access again.'),
                ]);
            }

            $request->session()->regenerate();
            
            // Clear login attempts
            RateLimiter::clear($this->throttleKey($request));
            
            // Update user login tracking
            $user = Auth::user();
            $user->updateLastLogin();

            // Starts the clock on first login only; a re-login does not extend it.
            $user->startAccessWindow();
            $this->updateLoginHistory($user, $request);
            
            // Log successful login
            $this->logAuthEvent('login_success', $request);
            
            // Determine redirect based on user role
            $intended = $user->isAdmin() ? route('admin.dashboard') : route('user.dashboard');
            
            return redirect()->intended($intended);
        }

        // Record failed attempt
        RateLimiter::hit($this->throttleKey($request), 300); // 5 minutes decay
        $this->logAuthEvent('login_failed', $request);

        throw ValidationException::withMessages([
            'email' => __('auth.failed'),
        ]);
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        // Rate limit registration attempts
        $key = 'register:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => "Too many registration attempts. Try again in {$seconds} seconds.",
            ]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|regex:/^[a-zA-Z\s]+$/',
            'email' => 'required|string|email:rfc,dns|max:255|unique:users',
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
            ],
            'role' => 'sometimes|in:admin,user',
        ], [
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
            'name.regex' => 'Name may only contain letters and spaces.',
        ]);

        RateLimiter::hit($key, 300); // 5 minutes decay

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'] ?? 'user',
            'email_verified_at' => now(), // Auto-verify for now, or implement email verification
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        // Log registration
        $this->logAuthEvent('registration_success', $request);

        // Redirect based on role
        $intended = $user->isAdmin() ? route('admin.dashboard') : route('user.dashboard');
        
        return redirect($intended);
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        
        // Log logout
        $this->logAuthEvent('logout', $request);
        
        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect('/login')->with('status', 'You have been logged out successfully.');
    }

    /**
     * Check for too many failed login attempts
     */
    protected function checkTooManyFailedAttempts(Request $request)
    {
        $key = $this->throttleKey($request);
        $maxAttempts = 5;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            
            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }
    }

    /**
     * Get the throttle key for the given request
     */
    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->input('email')).'|'.$request->ip());
    }

    /**
     * Log authentication events
     */
    protected function logAuthEvent(string $event, Request $request): void
    {
        \Log::info("Auth Event: {$event}", [
            'email' => $request->input('email'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now(),
        ]);
    }

    /**
     * Update user login history
     */
    protected function updateLoginHistory(User $user, Request $request): void
    {
        $loginData = [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString(),
        ];

        // Get existing login history or initialize empty array
        $history = $user->login_history ?? [];
        
        // Add new login to beginning of array
        array_unshift($history, $loginData);
        
        // Keep only last 10 logins
        $history = array_slice($history, 0, 10);
        
        // Update user record
        $user->update(['login_history' => $history]);
    }
}