<?php

/**
 * This file is part of DixlaseCookie.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * DixlaseCookie is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU General Public License version 3 or later, as published
 *       by the Free Software Foundation; or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the GPL terms below.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Plugins\DixlaseCookie\Tests\Feature\Front;

use App\Contracts\Cookie\ConsentStateProviderInterface;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Plugins\DixlaseCookie\App\Http\Controllers\Front\CookieConsentController;
use Plugins\DixlaseCookie\App\Http\Middleware\InjectCookieConsentBanner;
use Plugins\DixlaseCookie\App\Models\DixlaseCookieConsent;
use Plugins\DixlaseCookie\App\Models\DixlaseCookieSetting;
use Plugins\DixlaseCookie\App\Services\CookieConsentStateProvider;
use Tests\TestCase;

/**
 * Feature tests for the B-3c banner injector middleware.
 *
 * A synthetic front route returns a minimal HTML page; the injector is
 * pushed onto the web group and we assert the banner is added (or not)
 * before </body> according to the enabled setting and the visitor's
 * current consent state.
 */
class CookieConsentBannerTest extends TestCase
{
    use RefreshDatabase;

    private string $pageUrl = '/__banner_test_page';

    protected function setUp(): void
    {
        parent::setUp();

        // Treat the app as installed so web requests are not redirected to
        // the installer (CI runs against a fresh, uninstalled Core).
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

        // The injector renders the plugin's banner partial, which needs
        // the view and translation namespaces registered.
        $this->app['view']->addNamespace('dixlase-cookie', base_path('plugins/DixlaseCookie/resources/views'));
        $this->app['translator']->addNamespace('dixlase-cookie', base_path('plugins/DixlaseCookie/lang'));

        $router = app('router');
        $router->post('/cookie-consent/accept', [CookieConsentController::class, 'accept'])
            ->middleware(['web'])
            ->name('dixlase-cookie::cookie-consent.accept');
        $router->get($this->pageUrl, fn () => response('<html><body><p>page</p></body></html>')
            ->header('Content-Type', 'text/html'))
            ->middleware(['web']);

        // Idempotent: safe even if the plugin provider already pushed it.
        $router->pushMiddlewareToGroup('web', InjectCookieConsentBanner::class);

        $router->getRoutes()->refreshNameLookups();
        $router->getRoutes()->refreshActionLookups();
    }

    public function test_panel_auto_opens_as_a_banner_when_enabled_and_no_consent(): void
    {
        DixlaseCookieSetting::setValue(InjectCookieConsentBanner::ENABLED_SETTING_KEY, '1');

        $response = $this->get($this->pageUrl);

        $response->assertOk();
        $response->assertSee('data-cookie-consent', false);
        // The plugin's own stylesheet is injected (theme-independent styling).
        $response->assertSee('assets/plugins/DixlaseCookie/css/banner.css', false);
        // Auto-opens as a banner on first visit.
        $response->assertSee('data-autoopen="1"', false);
        $response->assertSee('role="dialog"', false);
        $response->assertSee('/cookie-consent/accept', false);
        $response->assertSee(__('dixlase-cookie::front/cookie-consent.accept_all'), false);
        $response->assertSee(__('dixlase-cookie::front/cookie-consent.analytics_label'), false);
        // The persistent re-open trigger is always present.
        $response->assertSee('data-cookie-consent-trigger', false);
        $response->assertSee(__('dixlase-cookie::front/cookie-consent.reopen'), false);
    }

    public function test_nothing_is_injected_when_disabled(): void
    {
        DixlaseCookieSetting::setValue(InjectCookieConsentBanner::ENABLED_SETTING_KEY, '0');

        $response = $this->get($this->pageUrl);

        $response->assertOk();
        $response->assertDontSee('data-cookie-consent', false);
    }

    public function test_nothing_is_injected_when_setting_is_absent(): void
    {
        // No enabled row at all defaults to off.
        $response = $this->get($this->pageUrl);

        $response->assertOk();
        $response->assertDontSee('data-cookie-consent', false);
    }

    public function test_returning_visitor_gets_a_closed_panel_prefilled_with_their_choices(): void
    {
        DixlaseCookieSetting::setValue(InjectCookieConsentBanner::ENABLED_SETTING_KEY, '1');

        $uuid = 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa';
        DixlaseCookieConsent::create([
            'consent_id' => $uuid,
            'categories' => ['necessary' => true, 'functional' => false, 'analytics' => true, 'marketing' => false],
            'policy_version' => 1,
            'consented_at' => now(),
        ]);

        $this->withoutMiddleware(EncryptCookies::class);

        $response = $this
            ->withUnencryptedCookie(CookieConsentStateProvider::COOKIE_NAME, $uuid)
            ->get($this->pageUrl);

        $response->assertOk();
        // Still injected (so withdrawal is always reachable) but closed.
        $response->assertSee('data-cookie-consent', false);
        $response->assertSee('data-autoopen="0"', false);
        $response->assertSee('data-cookie-consent-trigger', false);
        // Toggles are pre-filled from the visitor's current decision
        // (strings '1'/'0' to match the core x-form-toggle xModel contract).
        $response->assertSee("analytics: '1'", false);
        $response->assertSee("marketing: '0'", false);
    }

    public function test_policy_links_render_when_url_settings_are_present(): void
    {
        DixlaseCookieSetting::setMany([
            InjectCookieConsentBanner::ENABLED_SETTING_KEY => '1',
            InjectCookieConsentBanner::PRIVACY_URL_SETTING_KEY => 'https://example.com/privacy',
            InjectCookieConsentBanner::COOKIE_URL_SETTING_KEY => 'https://example.com/cookies',
        ]);

        $response = $this->get($this->pageUrl);

        $response->assertOk();
        $response->assertSee('https://example.com/privacy', false);
        $response->assertSee('https://example.com/cookies', false);
        $response->assertSee(__('dixlase-cookie::front/cookie-consent.privacy_link'), false);
    }
}
