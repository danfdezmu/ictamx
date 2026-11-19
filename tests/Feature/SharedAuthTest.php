<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use App\Support\SharedAuth;

beforeEach(function () {
    config([
        'shared-auth.secret' => 'testing-secret',
        'shared-auth.role' => 'provider',
        'shared-auth.peer' => 'http://chordsystem.test',
    ]);
});

test('logging in on the church site hands the session to chordsystem', function () {
    $user = User::factory()->create();
    $return = 'http://chordsystem.test/sso/accept';

    $this->get('/login?return='.urlencode($return));

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect();
    $location = (string) $response->headers->get('Location');
    expect($location)->toStartWith($return.'?token=');

    $token = (string) parse_url($location, PHP_URL_QUERY);
    parse_str($token, $query);
    $identity = SharedAuth::readToken((string) $query['token']);

    expect($identity)->not->toBeNull()
        ->and($identity['email'])->toBe(mb_strtolower($user->email))
        ->and($identity['verified'])->toBeTrue();
});

test('an already open church session continues into chordsystem', function () {
    $user = User::factory()->create();
    $return = 'http://chordsystem.test/sso/accept';

    $response = $this->actingAs($user)->get('/login?return='.urlencode($return));

    $response->assertRedirect();
    expect((string) $response->headers->get('Location'))->toStartWith($return.'?token=');
});

test('a return address outside chordsystem stays on the church site', function () {
    $user = User::factory()->create();

    $this->get('/login?return='.urlencode('https://evil.test/sso/accept'));

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));
});

test('logging out from chordsystem ends the church session', function () {
    $user = User::factory()->create();
    $return = 'http://chordsystem.test/';

    $this->actingAs($user)
        ->get('/sso/logout?'.SharedAuth::signedLogoutQuery($return))
        ->assertRedirect($return);

    $this->assertGuest();
});

test('a logout link with a bad signature is rejected', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/sso/logout?'.http_build_query([
            'return' => 'http://chordsystem.test/',
            'expires' => now()->addMinute()->getTimestamp(),
            'signature' => 'no',
        ]))
        ->assertForbidden();

    $this->assertAuthenticated();
});

test('the sidebar sends the open church session into chordsystem', function () {
    $user = User::factory()->create();
    $accept = 'http://chordsystem.test/sso/accept';

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('chordSystem', '/chordsystem'));

    $response = $this->actingAs($user)->get(route('chordsystem'));

    $response->assertRedirect();
    $location = (string) $response->headers->get('Location');
    expect($location)->toStartWith($accept.'?token=');

    parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
    $identity = SharedAuth::readToken((string) $query['token']);

    expect($identity)->not->toBeNull()
        ->and($identity['email'])->toBe(mb_strtolower($user->email));

    $version = (string) app(HandleInertiaRequests::class)->version(request());

    $inertia = $this->actingAs($user)
        ->withHeaders([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $version,
        ])
        ->get(route('chordsystem'));

    $inertia->assertStatus(409);
    expect((string) $inertia->headers->get('X-Inertia-Location'))->toStartWith($accept.'?token=');
});

test('a guest is asked to sign in before opening chordsystem', function () {
    $this->get(route('chordsystem'))->assertRedirect(route('login'));
});

test('chordsystem stays hidden when the shared session is off', function () {
    config([
        'shared-auth.secret' => '',
        'shared-auth.role' => 'provider',
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)->get(route('chordsystem'))->assertNotFound();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('chordSystem', null));
});

test('the church site does not offer chordsystem when it is not the session provider', function () {
    config(['shared-auth.role' => 'client']);

    $this->actingAs(User::factory()->create())
        ->get(route('chordsystem'))
        ->assertNotFound();
});

test('logging out of the church site also ends the chordsystem session', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('http://ictamx.test/logout');

    $response->assertRedirect();
    expect((string) $response->headers->get('Location'))
        ->toStartWith('http://chordsystem.test/sso/logout?')
        ->toContain('return=http%3A%2F%2Fictamx.test%2F');
    $this->assertGuest();
});
