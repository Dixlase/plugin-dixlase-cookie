<?php

/**
 * This file is part of DixlaseCookie.
 *
 * Copyright (C) 2026 exc-D inc.
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
use App\Events\ConsentChanged;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Plugins\DixlaseCookie\App\Http\Controllers\Front\CookieConsentController;
use Plugins\DixlaseCookie\App\Models\DixlaseCookieConsent;
use Plugins\DixlaseCookie\App\Models\DixlaseCookieSetting;
use Plugins\DixlaseCookie\App\Services\CookieConsentStateProvider;
use Tests\TestCase;

/**
 * Feature tests for the B-3b accept endpoint.
 *
 * Covers the full request path: validation, append-only row insertion,
 * consent-UUID reuse, persistent cookie queueing, and ConsentChanged
 * dispatch with correctly-built previous/current snapshots.
 */
class CookieConsentAcceptTest extends TestCase
{
    use RefreshDatabase;

    private string $acceptUrl = '/cookie-consent/accept';

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

        // Bind the contract explicitly so the controller resolves the
        // real reader regardless of plugin auto-registration in tests.
        $this->app->singleton(
            ConsentStateProviderInterface::class,
            CookieConsentStateProvider::class,
        );

        $router = app('router');
        $router->post($this->acceptUrl, [CookieConsentController::class, 'accept'])
            ->middleware(['web'])
            ->name('dixlase-cookie::cookie-consent.accept');
        $router->getRoutes()->refreshNameLookups();
        $router->getRoutes()->refreshActionLookups();
    }

    public function test_first_time_accept_records_a_row_and_queues_the_cookie(): void
    {
        Event::fake([ConsentChanged::class]);

        $response = $this->postJson($this->acceptUrl, [
            'functional' => true,
            'analytics' => true,
            'marketing' => false,
        ]);

        $response->assertOk()->assertJson([
            'status' => 'ok',
            'version' => 1,
            'consent' => [
                'necessary' => true,
                'functional' => true,
                'analytics' => true,
                'marketing' => false,
            ],
        ]);

        $this->assertSame(1, DixlaseCookieConsent::query()->count());
        $row = DixlaseCookieConsent::query()->firstOrFail();
        $this->assertSame([
            'necessary' => true,
            'functional' => true,
            'analytics' => true,
            'marketing' => false,
        ], $row->categories);
        $this->assertSame(1, $row->policy_version);
        $this->assertNotEmpty($row->consent_id);
        $this->assertNotNull($row->consented_at);

        // The persistent cookie carries the same UUID as the stored row.
        $response->assertCookie(CookieConsentStateProvider::COOKIE_NAME, $row->consent_id);

        Event::assertDispatched(ConsentChanged::class, function (ConsentChanged $event) {
            return $event->previous === []
                && $event->current['analytics'] === true
                && $event->current['marketing'] === false
                && $event->version === 1;
        });
    }

    public function test_reject_all_records_an_explicit_denial(): void
    {
        $response = $this->postJson($this->acceptUrl, []);

        $response->assertOk();
        $row = DixlaseCookieConsent::query()->firstOrFail();
        $this->assertSame([
            'necessary' => true,
            'functional' => false,
            'analytics' => false,
            'marketing' => false,
        ], $row->categories);
    }

    public function test_existing_consent_id_cookie_is_reused_and_updated_in_place(): void
    {
        $uuid = '99999999-9999-9999-9999-999999999999';

        // Prior decision: analytics granted under policy v1.
        DixlaseCookieConsent::create([
            'consent_id' => $uuid,
            'categories' => ['necessary' => true, 'analytics' => true, 'functional' => false, 'marketing' => false],
            'policy_version' => 1,
            'consented_at' => now()->subDay(),
        ]);

        Event::fake([ConsentChanged::class]);

        // Drop cookie encryption so the simulated incoming cookie reaches
        // the request as plaintext (in production EncryptCookies decrypts
        // the previously-set encrypted cookie to the same plaintext UUID).
        $this->withoutMiddleware(EncryptCookies::class);

        // Visitor returns with their cookie and withdraws analytics.
        // withCredentials() is required for cookies to ride a JSON request.
        $response = $this
            ->withCredentials()
            ->withUnencryptedCookie(CookieConsentStateProvider::COOKIE_NAME, $uuid)
            ->postJson($this->acceptUrl, ['analytics' => false]);

        $response->assertOk();

        // The SAME row was updated in place — no new row, one per visitor.
        $rows = DixlaseCookieConsent::query()->forConsentId($uuid)->get();
        $this->assertCount(1, $rows);
        $this->assertFalse($rows->first()->categories['analytics']);
        $response->assertCookie(CookieConsentStateProvider::COOKIE_NAME, $uuid, encrypted: false);

        Event::assertDispatched(ConsentChanged::class, function (ConsentChanged $event) {
            // previous reflects the prior (analytics-on) decision...
            return ($event->previous['analytics'] ?? null) === true
                // ...and current reflects the withdrawal.
                && $event->current['analytics'] === false;
        });
    }

    public function test_policy_version_setting_is_stamped_onto_the_row(): void
    {
        DixlaseCookieSetting::setValue(CookieConsentStateProvider::VERSION_SETTING_KEY, '3');

        $this->postJson($this->acceptUrl, ['analytics' => true])->assertOk();

        $row = DixlaseCookieConsent::query()->firstOrFail();
        $this->assertSame(3, $row->policy_version);
    }
}
