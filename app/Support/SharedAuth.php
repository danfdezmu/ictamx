<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Str;

class SharedAuth
{
    public static function enabled(): bool
    {
        $secret = config('shared-auth.secret');

        return is_string($secret) && $secret !== '';
    }

    public static function isProvider(): bool
    {
        return config('shared-auth.role') === 'provider';
    }

    public static function isClient(): bool
    {
        return config('shared-auth.role') === 'client';
    }

    /**
     * @param  array{email: string, name: string, verified: bool}  $identity
     */
    public static function token(array $identity): string
    {
        return self::encrypter()->encrypt([
            'email' => mb_strtolower($identity['email']),
            'name' => $identity['name'],
            'verified' => $identity['verified'],
            'exp' => now()->addMinutes(2)->getTimestamp(),
        ]);
    }

    public static function tokenFor(User $user): string
    {
        return self::token([
            'email' => $user->email,
            'name' => $user->name,
            'verified' => $user->hasVerifiedEmail(),
        ]);
    }

    /**
     * @return array{email: string, name: string, verified: bool, exp: int}|null
     */
    public static function readToken(string $value): ?array
    {
        if ($value === '') {
            return null;
        }

        try {
            $payload = self::encrypter()->decrypt($value);
        } catch (DecryptException) {
            return null;
        }

        if (! is_array($payload) || ! isset($payload['email'], $payload['name'], $payload['exp'])) {
            return null;
        }

        if ((int) $payload['exp'] < now()->getTimestamp()) {
            return null;
        }

        return [
            'email' => (string) $payload['email'],
            'name' => (string) $payload['name'],
            'verified' => (bool) ($payload['verified'] ?? false),
            'exp' => (int) $payload['exp'],
        ];
    }

    /**
     * @param  array{email: string, name: string, verified: bool, exp: int}  $identity
     */
    public static function provision(array $identity): User
    {
        $user = User::query()->whereRaw('lower(email) = ?', [$identity['email']])->first();

        if ($user === null) {
            $user = new User;
            $user->email = $identity['email'];
            $user->password = Str::password(40);
        }

        $user->name = $identity['name'];

        if ($identity['verified'] && $user->email_verified_at === null) {
            $user->email_verified_at = now();
        }

        if (! $user->exists || $user->isDirty()) {
            $user->save();
        }

        return $user;
    }

    public static function allows(string $url): bool
    {
        $peer = rtrim((string) config('shared-auth.peer'), '/');

        if ($peer === '' || ! str_starts_with($peer, 'http')) {
            return false;
        }

        return $url === $peer || str_starts_with($url, $peer.'/');
    }

    public static function handoffUrl(string $return, User $user): string
    {
        $separator = str_contains($return, '?') ? '&' : '?';

        return $return.$separator.http_build_query([
            'token' => self::tokenFor($user),
        ]);
    }

    public static function loginUrl(string $acceptUrl): string
    {
        return self::peerPath('/login', $acceptUrl);
    }

    public static function registerUrl(string $acceptUrl): string
    {
        return self::peerPath('/register', $acceptUrl);
    }

    public static function forgotPasswordUrl(): string
    {
        return rtrim((string) config('shared-auth.peer'), '/').'/forgot-password';
    }

    public static function remoteLogoutUrl(string $return): string
    {
        return rtrim((string) config('shared-auth.peer'), '/').'/sso/logout?'.self::signedLogoutQuery($return);
    }

    public static function signedLogoutQuery(string $return): string
    {
        $expires = now()->addMinutes(5)->getTimestamp();

        return http_build_query([
            'return' => $return,
            'expires' => $expires,
            'signature' => self::sign($expires, $return),
        ]);
    }

    public static function signatureIsValid(string $return, int $expires, string $signature): bool
    {
        if ($return === '' || $expires < now()->getTimestamp() || $signature === '') {
            return false;
        }

        return hash_equals(self::sign($expires, $return), $signature);
    }

    public static function acceptUrl(string $root): string
    {
        return rtrim($root, '/').'/sso/accept';
    }

    private static function peerPath(string $path, string $acceptUrl): string
    {
        return rtrim((string) config('shared-auth.peer'), '/').$path.'?'.http_build_query([
            'return' => $acceptUrl,
        ]);
    }

    private static function sign(int $expires, string $return): string
    {
        return hash_hmac('sha256', $expires.'|'.$return, (string) config('shared-auth.secret'));
    }

    private static function encrypter(): Encrypter
    {
        return new Encrypter(hash('sha256', (string) config('shared-auth.secret'), true), 'aes-256-cbc');
    }
}
