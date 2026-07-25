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

namespace Plugins\DixlaseCookie\Tests\Feature\Admin;

use App\Contracts\Cookie\ConsentStateProviderInterface;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Events\ConsentChanged;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Plugins\DixlaseCookie\App\Http\Controllers\Admin\CookieAdminController;
use Plugins\DixlaseCookie\App\Http\Middleware\InjectCookieConsentBanner;
use Plugins\DixlaseCookie\App\Http\Controllers\Front\CookieConsentController;
use Plugins\DixlaseCookie\App\Models\DixlaseCookieSetting;
use Plugins\DixlaseCookie\App\Services\CookieConsentStateProvider;
use Tests\TestCase;

/**
 * Feature tests for the B-5 cookie admin settings screen.
 */
class CookieAdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    private Member $admin;

    private string $indexUrl;

    private string $updateUrl;

    private string $bumpUrl;

    protected function setUp(): void
    {
        parent::setUp();

        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';

        Artisan::call('migrate', [
            '--path' => 'plugins/DixlaseCookie/database/migrations',
            '--realpath' => false,
        ]);

        $this->app['view']->addNamespace('dixlase-cookie', base_path('plugins/DixlaseCookie/resources/views'));
        $this->app['translator']->addNamespace('dixlase-cookie', base_path('plugins/DixlaseCookie/lang'));

        $this->app->singleton(
            ConsentStateProviderInterface::class,
            CookieConsentStateProvider::class,
        );

        $adminUrl = config('admin.admin_url', 'admin');
        $this->indexUrl = "/{$adminUrl}/cookie/settings";
        $this->updateUrl = "/{$adminUrl}/cookie/settings";
        $this->bumpUrl = "/{$adminUrl}/cookie/settings/bump-version";

        $router = app('router');
        $router->prefix($adminUrl)
            ->middleware(['web', 'auth:member'])
            ->group(function () use ($router) {
                $router->prefix('cookie')
                    ->name('dixlase-cookie::admin.cookie.')
                    ->group(function () use ($router) {
                        $router->get('/settings', [CookieAdminController::class, 'index'])->name('settings.index');
                        $router->match(['patch'], '/settings', [CookieAdminController::class, 'update'])->name('settings.update');
                        $router->post('/settings/bump-version', [CookieAdminController::class, 'bumpVersion'])->name('settings.bump-version');
                    });
            });
        $router->getRoutes()->refreshNameLookups();
        $router->getRoutes()->refreshActionLookups();

        $this->admin = Member::create([
            'account_name' => 'cookieadmin',
            'display_name' => 'Cookie Admin',
            'email' => 'cookie-admin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::ADMIN,
            'status' => MemberStatus::Active,
        ]);
    }

    protected function tearDown(): void
    {
        $_ENV['INSTALLED'] = 'false';
        $_SERVER['INSTALLED'] = 'false';
        parent::tearDown();
    }

    public function test_guest_is_redirected(): void
    {
        $this->get($this->indexUrl)->assertRedirect();
    }

    public function test_admin_can_access_settings(): void
    {
        $response = $this->actingAs($this->admin, 'member')->get($this->indexUrl);

        $response->assertOk();
        $response->assertViewIs('dixlase-cookie::admin.cookie.settings.index');
        $response->assertViewHas('cookieConsentEnabled');
        $response->assertViewHas('currentVersion', 1);
    }

    public function test_banner_is_disabled_by_default(): void
    {
        $response = $this->actingAs($this->admin, 'member')->get($this->indexUrl);

        $this->assertFalse($response->viewData('cookieConsentEnabled'));
    }

    public function test_can_save_settings(): void
    {
        $response = $this->actingAs($this->admin, 'member')->patch($this->updateUrl, [
            'cookie_consent_enabled' => '1',
            'cookie_consent_lifetime_days' => '180',
            'cookie_consent_privacy_url' => '/privacy',
            'cookie_consent_cookie_url' => 'https://example.com/cookies',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSame('1', DixlaseCookieSetting::getValue(InjectCookieConsentBanner::ENABLED_SETTING_KEY));
        $this->assertSame('180', DixlaseCookieSetting::getValue(CookieConsentController::LIFETIME_DAYS_SETTING_KEY));
        $this->assertSame('/privacy', DixlaseCookieSetting::getValue(InjectCookieConsentBanner::PRIVACY_URL_SETTING_KEY));
        $this->assertSame('https://example.com/cookies', DixlaseCookieSetting::getValue(InjectCookieConsentBanner::COOKIE_URL_SETTING_KEY));
    }

    public function test_disabled_when_toggle_omitted(): void
    {
        // Enable first, then submit without the toggle (browsers omit
        // unchecked checkboxes) and confirm it flips back to off.
        $this->actingAs($this->admin, 'member')->patch($this->updateUrl, ['cookie_consent_enabled' => '1']);
        $this->actingAs($this->admin, 'member')->patch($this->updateUrl, []);

        $this->assertSame('0', DixlaseCookieSetting::getValue(InjectCookieConsentBanner::ENABLED_SETTING_KEY));
    }

    public function test_empty_url_clears_the_setting(): void
    {
        $this->actingAs($this->admin, 'member')->patch($this->updateUrl, [
            'cookie_consent_privacy_url' => 'https://example.com/privacy',
        ]);
        $this->assertSame('https://example.com/privacy', DixlaseCookieSetting::getValue(InjectCookieConsentBanner::PRIVACY_URL_SETTING_KEY));

        $this->actingAs($this->admin, 'member')->patch($this->updateUrl, ['cookie_consent_privacy_url' => '']);
        $this->assertNull(DixlaseCookieSetting::getValue(InjectCookieConsentBanner::PRIVACY_URL_SETTING_KEY));
    }

    public function test_relative_path_without_leading_slash_is_accepted(): void
    {
        $response = $this->actingAs($this->admin, 'member')->patch($this->updateUrl, [
            'cookie_consent_privacy_url' => 'legal/privacy-policy',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame('legal/privacy-policy', DixlaseCookieSetting::getValue(InjectCookieConsentBanner::PRIVACY_URL_SETTING_KEY));
    }

    public function test_dangerous_scheme_is_rejected(): void
    {
        $response = $this->actingAs($this->admin, 'member')->patch($this->updateUrl, [
            'cookie_consent_privacy_url' => 'javascript:alert(1)',
        ]);

        $response->assertSessionHasErrors('cookie_consent_privacy_url');
    }

    public function test_bump_version_increments_and_reprompts(): void
    {
        $this->assertSame(1, app(ConsentStateProviderInterface::class)->version());

        $response = $this->actingAs($this->admin, 'member')->post($this->bumpUrl);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSame('2', DixlaseCookieSetting::getValue(CookieConsentStateProvider::VERSION_SETTING_KEY));
        $this->assertSame(2, app(ConsentStateProviderInterface::class)->version());
    }

    public function test_bump_version_does_not_dispatch_consent_changed(): void
    {
        // A global version bump is not a per-visitor decision — the
        // ConsentChanged event must fire only when a visitor re-consents.
        Event::fake([ConsentChanged::class]);

        $this->actingAs($this->admin, 'member')->post($this->bumpUrl)->assertRedirect();

        Event::assertNotDispatched(ConsentChanged::class);
    }
}
