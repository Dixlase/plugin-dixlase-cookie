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
use Plugins\DixlaseCookie\App\Services\CookieConsentStateProvider;
use Tests\TestCase;

/**
 * Integration tests for ConsentChanged dispatch semantics (B-6).
 *
 * The accept endpoint always appends a consent row, but ConsentChanged
 * fires only when the effective decision actually changed: a first-time
 * decision (previous === []) always counts as a change; an identical
 * re-submit does not.
 */
class ConsentChangedDispatchTest extends TestCase
{
    use RefreshDatabase;

    private string $acceptUrl = '/cookie-consent/accept';

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate', [
            '--path' => 'plugins/DixlaseCookie/database/migrations',
            '--realpath' => false,
        ]);

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

    public function test_first_time_decision_dispatches_with_empty_previous(): void
    {
        Event::fake([ConsentChanged::class]);

        $this->postJson($this->acceptUrl, ['analytics' => true])->assertOk();

        Event::assertDispatched(ConsentChanged::class, function (ConsentChanged $event) {
            return $event->previous === []
                && $event->current['analytics'] === true
                && $event->version === 1;
        });
    }

    public function test_first_time_reject_all_still_dispatches(): void
    {
        // "not asked yet" ([]) differs from "asked and denied", so even a
        // first-time reject-all is a change worth announcing.
        Event::fake([ConsentChanged::class]);

        $this->postJson($this->acceptUrl, [])->assertOk();

        Event::assertDispatched(ConsentChanged::class, function (ConsentChanged $event) {
            return $event->previous === []
                && $event->current['analytics'] === false
                && $event->current['necessary'] === true;
        });
    }

    public function test_identical_re_accept_appends_a_row_but_does_not_dispatch(): void
    {
        $uuid = 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb';
        DixlaseCookieConsent::create([
            'consent_id' => $uuid,
            'categories' => ['necessary' => true, 'functional' => false, 'analytics' => true, 'marketing' => false],
            'policy_version' => 1,
            'consented_at' => now()->subDay(),
        ]);

        Event::fake([ConsentChanged::class]);
        $this->withoutMiddleware(EncryptCookies::class);

        $response = $this
            ->withCredentials()
            ->withUnencryptedCookie(CookieConsentStateProvider::COOKIE_NAME, $uuid)
            ->postJson($this->acceptUrl, ['functional' => false, 'analytics' => true, 'marketing' => false]);

        $response->assertOk();

        // Row still appended (full audit log)...
        $this->assertCount(2, DixlaseCookieConsent::query()->forConsentId($uuid)->get());
        // ...but nothing changed, so no event.
        Event::assertNotDispatched(ConsentChanged::class);
    }

    public function test_changed_re_accept_dispatches_with_prior_snapshot(): void
    {
        $uuid = 'cccccccc-cccc-cccc-cccc-cccccccccccc';
        DixlaseCookieConsent::create([
            'consent_id' => $uuid,
            'categories' => ['necessary' => true, 'functional' => false, 'analytics' => true, 'marketing' => false],
            'policy_version' => 1,
            'consented_at' => now()->subDay(),
        ]);

        Event::fake([ConsentChanged::class]);
        $this->withoutMiddleware(EncryptCookies::class);

        $this
            ->withCredentials()
            ->withUnencryptedCookie(CookieConsentStateProvider::COOKIE_NAME, $uuid)
            ->postJson($this->acceptUrl, ['functional' => false, 'analytics' => false, 'marketing' => false])
            ->assertOk();

        Event::assertDispatched(ConsentChanged::class, function (ConsentChanged $event) {
            return ($event->previous['analytics'] ?? null) === true
                && $event->current['analytics'] === false
                && $event->version === 1;
        });
    }
}
