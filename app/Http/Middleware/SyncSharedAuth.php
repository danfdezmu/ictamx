<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\SharedAuth;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SyncSharedAuth
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! SharedAuth::enabled()) {
            return $next($request);
        }

        if (SharedAuth::isClient()) {
            return $this->asClient($request, $next);
        }

        if (SharedAuth::isProvider()) {
            return $this->asProvider($request, $next);
        }

        return $next($request);
    }

    /**
     * @param  Closure(Request): Response  $next
     */
    private function asClient(Request $request, Closure $next): Response
    {
        if ($request->session()->get('shared_auth') === true) {
            $request->session()->put('auth.password_confirmed_at', now()->unix());
        }

        if (! $this->isRemoteAuthRoute($request)) {
            return $next($request);
        }

        if ($request->user() !== null) {
            return redirect()->route('dashboard');
        }

        return redirect()->away($this->remoteAuthUrl($request));
    }

    /**
     * @param  Closure(Request): Response  $next
     */
    private function asProvider(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && $request->is('login', 'register')) {
            $return = $request->query('return');

            if (is_string($return) && SharedAuth::allows($return)) {
                $request->session()->put('shared_auth.return', $return);
            }
        }

        $response = $next($request);
        $user = $request->user();

        if (! $user instanceof User || ! $user->hasVerifiedEmail()) {
            return $response;
        }

        $return = $request->session()->get('shared_auth.return');

        if (! is_string($return) || ! SharedAuth::allows($return)) {
            return $response;
        }

        $handoff = ($request->isMethod('POST') && $request->is('login', 'register', 'two-factor-challenge'))
            || ($request->isMethod('GET') && $request->is('login', 'register'));

        if (! $handoff) {
            return $response;
        }

        $request->session()->forget('shared_auth.return');

        return redirect()->away(SharedAuth::handoffUrl($return, $user));
    }

    private function isRemoteAuthRoute(Request $request): bool
    {
        return $request->is(
            'login',
            'register',
            'forgot-password',
            'reset-password',
            'reset-password/*',
            'two-factor-challenge',
        );
    }

    private function remoteAuthUrl(Request $request): string
    {
        $accept = SharedAuth::acceptUrl($request->getSchemeAndHttpHost());

        if ($request->is('register')) {
            return SharedAuth::registerUrl($accept);
        }

        if ($request->is('forgot-password', 'reset-password', 'reset-password/*')) {
            return SharedAuth::forgotPasswordUrl();
        }

        return SharedAuth::loginUrl($accept);
    }
}
