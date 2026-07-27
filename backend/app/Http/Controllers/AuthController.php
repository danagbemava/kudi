<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /** Failed attempts allowed per email+IP before the pair is locked out. */
    private const MAX_ATTEMPTS = 5;

    /** How long the lockout lasts, in seconds. */
    private const DECAY_SECONDS = 60;

    public function login(Request $request): JsonResponse
    {
        // Answer an already-signed-in caller in JSON. This used to be the
        // `guest` middleware's job, which redirects to "/" — a 302 towards an
        // HTML page, from an endpoint every other route answers as JSON, and
        // the SPA can only read that as an opaque network failure.
        if ($request->user()) {
            return response()->json(['data' => $request->user()]);
        }

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $this->ensureIsNotRateLimited($request);

        if (! Auth::attempt($credentials)) {
            // Only failures are counted and a success clears the tally, so
            // someone who mistypes twice and then gets it right is not locked
            // out for the rest of the minute.
            RateLimiter::hit($this->throttleKey($request), self::DECAY_SECONDS);

            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));

        $request->session()->regenerate();

        return response()->json(['data' => $request->user()]);
    }

    public function logout(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    /**
     * Refuse further attempts once the limit is reached.
     *
     * 429 rather than a validation error: this says nothing about the
     * credentials, and the client has to be able to tell "wrong password" from
     * "stop asking" to say anything useful about either.
     */
    private function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), self::MAX_ATTEMPTS)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        abort(response()->json([
            'message' => "Too many sign-in attempts. Try again in {$seconds} seconds.",
            'retry_after' => $seconds,
        ], 429, ['Retry-After' => (string) $seconds]));
    }

    /**
     * Keyed on email *and* IP. On IP alone one person behind a shared address
     * could lock out everyone else; on email alone anyone could lock a known
     * account out at will.
     */
    private function throttleKey(Request $request): string
    {
        return Str::transliterate(
            Str::lower((string) $request->input('email')).'|'.$request->ip()
        );
    }
}
