<?php

/**
 * This file is part of the Dixlase Cookie plugin.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 */

namespace Plugins\DixlaseCookie\Tests\Feature;

use App\Contracts\Cookie\ConsentStateProviderInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\RateLimiter;
use Plugins\DixlaseCookie\App\Http\Controllers\Front\CookieConsentController;
use Plugins\DixlaseCookie\App\Models\DixlaseCookieConsent;
use Plugins\DixlaseCookie\App\Services\CookieConsentStateProvider;
use Tests\TestCase;

/**
 * /cookie-consent/accept is unauthenticated by design -- every visitor has to
 * be able to give or withdraw consent, so it sits outside front.ip.
 *
 * It was also unthrottled. resolveConsentId() hands back a fresh UUID when the
 * request carries no consent cookie, so updateOrCreate() inserts instead of
 * updating: fetching a CSRF token once and posting in a loop appended a row
 * per request with nothing bounding the table.
 *
 * dls:cookie:prune cleans up after the fact; the throttle stops the inflow.
 *
 * Note what each half of this file covers. Plugin routes are registered only
 * for enabled plugins, so the behavioural tests declare the route and the
 * limiter themselves in setUp(); they verify that the chosen limit (20/minute
 * per IP) really does return 429 and really does not block ordinary use, but
 * they cannot see the production wiring -- removing the throttle from
 * routes/web.php leaves them green. The last two tests are what guard that,
 * by reading the route file and the provider. Both halves are needed: the
 * wiring assertions alone would not notice a limiter configured so loosely it
 * never fires.
 */
class ConsentEndpointThrottleTest extends TestCase
{
    use RefreshDatabase;

    private string $acceptUrl = '/cookie-consent/accept';

    protected function setUp(): void
    {
        parent::setUp();

        // Same shape as CookieConsentAcceptTest: plugin routes are registered
        // by PluginServiceProvider only for enabled plugins, so the route is
        // declared here -- with the throttle the real definition carries, which
        // is the thing under test.
        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';

        Artisan::call('migrate', [
            '--path' => 'plugins/DixlaseCookie/database/migrations',
            '--realpath' => false,
        ]);

        $this->app->singleton(
            ConsentStateProviderInterface::class,
            CookieConsentStateProvider::class,
        );

        RateLimiter::for('cookie-consent-accept', function ($request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(20)->by($request->ip());
        });

        $router = app('router');
        $router->post($this->acceptUrl, [CookieConsentController::class, 'accept'])
            ->middleware(['web', 'throttle:cookie-consent-accept'])
            ->name('dixlase-cookie::cookie-consent.accept');
        $router->getRoutes()->refreshNameLookups();
        $router->getRoutes()->refreshActionLookups();
    }

    private function sendConsent(): \Illuminate\Testing\TestResponse
    {
        return $this->postJson($this->acceptUrl, ['analytics' => true]);
    }

    /**
     * The behaviour that was missing. Without a limiter this loop would insert
     * a row every time and never be refused.
     */
    public function test_the_endpoint_stops_accepting_after_the_limit(): void
    {
        $refused = false;

        for ($i = 0; $i < 25; $i++) {
            if ($this->sendConsent()->status() === 429) {
                $refused = true;
                break;
            }
        }

        $this->assertTrue(
            $refused,
            'An unauthenticated endpoint that inserts a row per request must be rate limited.'
        );
    }

    /**
     * A visitor giving consent, or adjusting categories a few times, must not
     * be caught by it. A limit that blocks normal use is not a fix.
     */
    public function test_ordinary_consent_still_works(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->sendConsent()->assertSuccessful();
        }
    }

    /**
     * Why the throttle matters: each cookie-less request is a new row, not an
     * update of an existing one.
     */
    public function test_requests_without_a_cookie_create_separate_rows(): void
    {
        $before = DixlaseCookieConsent::count();

        $this->sendConsent();
        $this->sendConsent();

        $this->assertSame(
            $before + 2,
            DixlaseCookieConsent::count(),
            'Each cookie-less request gets a fresh consent_id, so it inserts rather than updates -- which is what made the missing limit matter.'
        );
    }

    /**
     * Guards the wiring. The limiter can exist and still not be attached, and
     * the route file is where that is decided in production.
     */
    public function test_the_route_declares_the_throttle(): void
    {
        $routes = file_get_contents(dirname(__DIR__, 2).'/routes/web.php');

        $this->assertStringContainsString(
            'throttle:cookie-consent-accept',
            $routes,
            'The consent route must carry the throttle middleware.'
        );
    }

    /**
     * And that the limiter it names is actually registered by the provider.
     */
    public function test_the_provider_registers_the_limiter(): void
    {
        $provider = file_get_contents(
            dirname(__DIR__, 2).'/app/Providers/DixlaseCookieServiceProvider.php'
        );

        $this->assertStringContainsString(
            "RateLimiter::for('cookie-consent-accept'",
            $provider,
            'A throttle naming a limiter that is not registered would let every request through.'
        );
    }
}
