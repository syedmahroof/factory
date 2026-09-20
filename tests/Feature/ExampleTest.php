<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\Feature\Routing\SpaRoutesTest;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The site root used to be a server-side redirect: `/` ran through the auth
     * middleware, so a guest was bounced to the login screen before anything
     * rendered.
     *
     * It cannot be, now that the SPA is mounted at the root. The SPA
     * authenticates with a bearer token held in localStorage, which no request
     * to Laravel carries and no session cookie reflects, so a server-side guard
     * here would bounce a perfectly signed-in operator to the login screen
     * whenever their bridged web session had lapsed ahead of their token.
     *
     * The root therefore serves the shell, and the Vue router decides: `/`
     * redirects to the dashboard, whose `requiresAuth` guard sends a guest on to
     * the login screen. What is still asserted server-side is that the shell is
     * what comes back, and that the login route the middleware redirects
     * genuinely-guarded requests to still exists.
     *
     * @see SpaRoutesTest for the full catch-all contract.
     */
    public function test_the_root_serves_the_spa_shell(): void
    {
        $response = $this->get('/');

        $response->assertSuccessful();
        $this->assertStringContainsString('<div id="app">', $response->getContent());
    }

    public function test_the_login_route_the_auth_middleware_redirects_to_still_exists(): void
    {
        $this->assertTrue(Route::has('login'));
        $this->assertStringEndsWith('/login', route('login'));
    }
}
