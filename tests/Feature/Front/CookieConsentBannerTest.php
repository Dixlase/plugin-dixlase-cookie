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
use App\Contracts\Site\SiteContextInterface;
use App\Contracts\TranslationResolver;
use App\Models\Site;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Plugins\DixlaseCookie\App\Http\Controllers\Front\CookieConsentController;
use Plugins\DixlaseCookie\App\Http\Middleware\InjectCookieConsentBanner;
use Plugins\DixlaseCookie\App\Models\DixlaseCookieConsent;
use Plugins\DixlaseCookie\App\Models\DixlaseCookieSetting;
use Plugins\DixlaseCookie\App\Services\CookieConsentStateProvider;
use ReflectionMethod;
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

    /**
     * Without DixlaseMultilingual, core's SetFrontLocale lets the browser
     * Accept-Language outrank the site default, so a Japanese single-language
     * site would show an English banner to an English-browser visitor. The
     * banner must instead follow the site's configured default language.
     */
    public function test_banner_renders_in_site_default_locale_when_multilingual_absent(): void
    {
        $this->setSiteDefaultLocale('ja');
        // Simulate an English-browser visitor: the resolved app locale is en.
        app()->setLocale('en');
        $this->assertFalse(app()->bound(TranslationResolver::class));

        DixlaseCookieSetting::setValue(InjectCookieConsentBanner::ENABLED_SETTING_KEY, '1');

        $response = $this->get($this->pageUrl, ['Accept-Language' => 'en']);

        $response->assertOk();
        // Banner UI is Japanese (site default), not the app/browser locale.
        $response->assertSee('すべて受け入れる', false);
        $response->assertDontSee('Accept all', false);
    }

    /**
     * renderBanner() must render under the site-default locale but restore
     * the request locale afterwards so the rest of the response is untouched.
     */
    public function test_render_banner_restores_the_request_locale(): void
    {
        $this->setSiteDefaultLocale('ja');
        app()->setLocale('en');

        $html = $this->invokeProtected('renderBanner');

        // Rendered in the site default (ja) ...
        $this->assertStringContainsString('すべて受け入れる', $html);
        // ... but the request locale is put back.
        $this->assertSame('en', app()->getLocale());
    }

    /**
     * bannerLocale() honors the visitor's app locale when Multilingual is
     * present (it binds TranslationResolver), and falls back to the site
     * default when it is absent.
     */
    public function test_banner_locale_uses_app_locale_with_multilingual_else_site_default(): void
    {
        $this->setSiteDefaultLocale('ja');
        app()->setLocale('en');

        // Multilingual absent -> site default.
        $this->assertFalse(app()->bound(TranslationResolver::class));
        $this->assertSame('ja', $this->invokeProtected('bannerLocale'));

        // Multilingual present -> visitor's app locale, ignoring site default.
        app()->instance(TranslationResolver::class, \Mockery::mock(TranslationResolver::class));
        $this->assertSame('en', $this->invokeProtected('bannerLocale'));
    }

    /**
     * Point the primary site's default language at $locale and reload it into
     * the SiteContext so LocaleHelper::getSiteDefaultLocale() reflects it.
     */
    private function setSiteDefaultLocale(string $locale): void
    {
        Site::query()->whereKey(1)->update(['primary_locale' => $locale]);
        app(SiteContextInterface::class)->setCurrent(1);
    }

    /**
     * Invoke a protected method on a fresh injector instance.
     */
    private function invokeProtected(string $method): string
    {
        $ref = new ReflectionMethod(InjectCookieConsentBanner::class, $method);
        $ref->setAccessible(true);

        return $ref->invoke(new InjectCookieConsentBanner());
    }
}
