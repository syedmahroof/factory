<?php

use App\Models\User;
use App\Models\UserType;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

/*
|--------------------------------------------------------------------------
| The SPA at the site root
|--------------------------------------------------------------------------
|
| The Vue app used to be mounted under /app because a root catch-all would have
| swallowed the Livewire screens. Those are gone, so it now owns the root and
| everything Laravel still answers itself is named as an exclusion instead.
|
| That inverts the failure mode. Before, a mistake left a screen unreachable;
| now a mistake in the exclusion list silently hands an API call, a webhook or a
| PDF stream to the Vue shell, which answers 200 with an HTML page. A webhook
| POST that gets a 200 and an HTML body is a payment event we have accepted and
| dropped, so these are the tests that matter most in this file.
|
| The prefix boundary is the subtle one: `(?!api)` alone would read `apitest` as
| the API prefix, so every exclusion carries a trailing (/|$).
|
*/

beforeEach(function () {
    foreach ([UserType::Admin => 'Admin', UserType::Merchant => 'Merchant'] as $id => $name) {
        DB::table('user_types')->updateOrInsert(['id' => $id], ['name' => $name]);
    }
});

/** The Vue shell, told apart by its mount point rather than by status alone. */
function assertRoutesToSpa($response): void
{
    $response->assertSuccessful();

    expect($response->getContent())->toContain('<div id="app">');
}

it('serves the Vue shell at the site root', function () {
    assertRoutesToSpa($this->get('/'));
});

it('serves the Vue login screen at /login', function () {
    assertRoutesToSpa($this->get('/login'));
});

it('keeps the login route name that the auth middleware redirects to', function () {
    // Authenticate::redirectTo() calls route('login'); the Blade auth
    // scaffolding that used to register that name is gone, so the SPA route
    // carries it. Without this the /Admin PDF streams 500 instead of redirecting.
    expect(Route::has('login'))->toBeTrue()
        ->and(route('login'))->toEndWith('/login');
});

it('hands deep SPA paths to the Vue router rather than 404ing', function (string $path) {
    assertRoutesToSpa($this->get($path));
})->with([
    'dashboard' => '/admin',
    'a register' => '/admin/merchants',
    'a detail screen' => '/admin/merchants/84',
    'a nested tab' => '/admin/investors/12/transactions',
    'password reset target' => '/reset-password?token=abc&email=a%40b.test',
]);

/*
| What must NOT reach the SPA.
*/

it('does not swallow the API', function () {
    $response = $this->getJson('/api/v1/merchants');

    $response->assertUnauthorized();

    expect($response->getContent())->not->toContain('<div id="app">');
});

it('does not swallow the Actum webhooks', function () {
    // A 200 with an HTML body here would be an accepted-and-discarded payment
    // event. Anything other than the SPA shell is fine; the shell is not.
    $response = $this->postJson('/webhooks/actum/postback', []);

    expect($response->getContent())->not->toContain('<div id="app">');
});

it('leaves the Blade PDF streams reachable', function () {
    $merchantUser = User::create([
        'user_type_id' => UserType::Merchant,
        'name' => 'PDF Route Merchant',
        'email' => 'pdf-'.uniqid().'@example.test',
        'password' => Hash::make('x'),
    ]);

    // Unauthenticated it redirects to login — which is the proof it reached the
    // Blade route and its auth middleware, not the SPA catch-all.
    $this->get('/Admin/Account/Merchant/BalanceReport/'.$merchantUser->id)
        ->assertRedirect(route('login'));
});

it('reads an exclusion as a whole prefix, not a string prefix', function (string $path) {
    // `apitest` is not the `api` prefix, and `Administration` is not `Admin`.
    // Without the trailing (/|$) in the lookahead these would 404.
    assertRoutesToSpa($this->get($path));
})->with([
    'apitest' => '/apitest',
    'api-docs' => '/api-docs',
    'Administration' => '/Administration',
    'sessions' => '/sessions',
    'logouts' => '/logouts',
]);

/*
| The session bridge moved with the mount point.
*/

it('exposes the session bridge at the root, not under /app', function () {
    $admin = User::create([
        'user_type_id' => UserType::Admin,
        'name' => 'Bridge Admin',
        'email' => 'bridge-'.uniqid().'@example.test',
        'password' => Hash::make('x'),
    ]);

    Sanctum::actingAs($admin, ['admin']);

    $this->postJson('/session')->assertSuccessful();

    expect(route('spa.session.store'))->toEndWith('/session')
        ->and(route('spa.session.store'))->not->toContain('/app/');
});

it('no longer answers on the old /app mount point', function () {
    // /app is now just another SPA path, not a second mount point. It must not
    // keep working as one, or the two would drift.
    expect(route('spa'))->not->toContain('/app');
});

it('points the password reset email at the root-mounted screen', function () {
    $user = new User(['email' => 'reset@example.test']);

    $url = ResetPassword::$createUrlCallback
        ? call_user_func(ResetPassword::$createUrlCallback, $user, 'token-123')
        : null;

    expect($url)->toContain('/reset-password?token=token-123')
        ->and($url)->not->toContain('/app/reset-password');
})->skip(fn () => ! isset(ResetPassword::$createUrlCallback),
    'No reset URL callback registered.');
