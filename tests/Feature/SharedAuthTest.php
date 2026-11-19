<?php

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

test('logging out of the church site also ends the chordsystem session', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('http://ictamx.test/logout');

    $response->assertRedirect();
    expect((string) $response->headers->get('Location'))
        ->toStartWith('http://chordsystem.test/sso/logout?')
        ->toContain('return=http%3A%2F%2Fictamx.test%2F');
    $this->assertGuest();
});
