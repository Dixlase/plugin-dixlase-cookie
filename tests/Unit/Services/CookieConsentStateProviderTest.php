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

namespace Plugins\DixlaseCookie\Tests\Unit\Services;

use App\Contracts\Cookie\ConsentStateProviderInterface;
use App\Enums\ConsentCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Plugins\DixlaseCookie\App\Models\DixlaseCookieConsent;
use Plugins\DixlaseCookie\App\Models\DixlaseCookieSetting;
use Plugins\DixlaseCookie\App\Services\CookieConsentStateProvider;
use Tests\TestCase;

/**
 * Behaviour tests for the B-3a persistent-cookie reader.
 *
 * Unlike the B-1 stub these tests exercise the real lookup path:
 * a consent UUID carried in the {@see CookieConsentStateProvider::COOKIE_NAME}
 * cookie resolves to the visitor's most recent append-only consent row,
 * which drives `has()` / `snapshot()`. The conservative fallbacks (no
 * cookie, no row, stale policy version) all collapse to "necessary only".
 */
class CookieConsentStateProviderTest extends TestCase
{
    use RefreshDatabase;

    private CookieConsentStateProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate', [
            '--path' => 'plugins/DixlaseCookie/database/migrations',
            '--realpath' => false,
        ]);

        $this->provider = new CookieConsentStateProvider();
    }

    /**
     * Bind a request carrying the consent cookie so `request()` inside
     * the provider resolves the given UUID. Pass null for "no cookie".
     */
    private function withConsentCookie(?string $consentId): void
    {
        $cookies = $consentId === null
            ? []
            : [CookieConsentStateProvider::COOKIE_NAME => $consentId];

        $this->app->instance(
            'request',
            Request::create('/', 'GET', [], $cookies),
        );
    }

    /**
     * Insert one append-only consent row. `consentedAt` is given as an
     * offset string so tests can order rows deterministically.
     *
     * @param  array<string, bool>  $categories
     */
    private function recordConsent(
        string $consentId,
        array $categories,
        int $policyVersion = 1,
        string $consentedAt = '2026-06-24 12:00:00',
    ): DixlaseCookieConsent {
        return DixlaseCookieConsent::create([
            'consent_id' => $consentId,
            'categories' => $categories,
            'policy_version' => $policyVersion,
            'consented_at' => $consentedAt,
        ]);
    }

    public function test_implements_the_core_contract(): void
    {
        $this->assertInstanceOf(ConsentStateProviderInterface::class, $this->provider);
    }

    public function test_necessary_category_is_always_granted(): void
    {
        // `necessary` is implicit — granted even with no cookie at all.
        $this->withConsentCookie(null);

        $this->assertTrue($this->provider->has(ConsentCategory::Necessary->value));
        $this->assertTrue($this->provider->has('necessary'));
    }

    public function test_without_a_cookie_only_necessary_is_granted(): void
    {
        $this->withConsentCookie(null);

        $this->assertFalse($this->provider->has(ConsentCategory::Functional->value));
        $this->assertFalse($this->provider->has(ConsentCategory::Analytics->value));
        $this->assertFalse($this->provider->has(ConsentCategory::Marketing->value));
        $this->assertSame([], $this->provider->snapshot());
    }

    public function test_cookie_without_a_matching_row_falls_back_to_denied(): void
    {
        // A stray / forged cookie value that maps to no stored row must
        // not grant anything beyond the implicit necessary category.
        $this->withConsentCookie('00000000-0000-0000-0000-000000000000');

        $this->assertSame([], $this->provider->snapshot());
        $this->assertFalse($this->provider->has(ConsentCategory::Analytics->value));
        $this->assertTrue($this->provider->has(ConsentCategory::Necessary->value));
    }

    public function test_recorded_decision_drives_the_snapshot(): void
    {
        $uuid = '11111111-1111-1111-1111-111111111111';
        $this->recordConsent($uuid, [
            'necessary' => true,
            'functional' => true,
            'analytics' => true,
            'marketing' => false,
        ]);
        $this->withConsentCookie($uuid);

        $this->assertSame([
            'necessary' => true,
            'functional' => true,
            'analytics' => true,
            'marketing' => false,
        ], $this->provider->snapshot());

        $this->assertTrue($this->provider->has(ConsentCategory::Functional->value));
        $this->assertTrue($this->provider->has(ConsentCategory::Analytics->value));
        $this->assertFalse($this->provider->has(ConsentCategory::Marketing->value));
    }

    public function test_updating_the_row_in_place_reflects_the_new_state(): void
    {
        $uuid = '22222222-2222-2222-2222-222222222222';
        // First decision grants analytics; the visitor later withdraws it.
        // The same row is updated in place (one row per consent_id).
        DixlaseCookieConsent::updateOrCreate(
            ['consent_id' => $uuid],
            ['categories' => ['necessary' => true, 'analytics' => true], 'policy_version' => 1, 'consented_at' => '2026-06-24 10:00:00'],
        );
        DixlaseCookieConsent::updateOrCreate(
            ['consent_id' => $uuid],
            ['categories' => ['necessary' => true, 'analytics' => false], 'policy_version' => 1, 'consented_at' => '2026-06-24 11:00:00'],
        );
        $this->withConsentCookie($uuid);

        $this->assertSame(1, DixlaseCookieConsent::query()->forConsentId($uuid)->count());
        $this->assertFalse($this->provider->has(ConsentCategory::Analytics->value));
    }

    public function test_stale_policy_version_is_treated_as_no_decision(): void
    {
        // Visitor agreed under policy v1, but the operator has bumped to v2.
        $uuid = '33333333-3333-3333-3333-333333333333';
        $this->recordConsent($uuid, ['necessary' => true, 'analytics' => true], 1);
        DixlaseCookieSetting::setValue(CookieConsentStateProvider::VERSION_SETTING_KEY, '2');
        $this->withConsentCookie($uuid);

        $this->assertSame([], $this->provider->snapshot());
        $this->assertFalse($this->provider->has(ConsentCategory::Analytics->value));
        $this->assertTrue($this->provider->has(ConsentCategory::Necessary->value));
    }

    public function test_necessary_is_forced_true_in_a_non_empty_snapshot(): void
    {
        // Even a malformed row that stored necessary=false must surface
        // necessary as granted per the contract.
        $uuid = '44444444-4444-4444-4444-444444444444';
        $this->recordConsent($uuid, ['necessary' => false, 'analytics' => true]);
        $this->withConsentCookie($uuid);

        $snapshot = $this->provider->snapshot();
        $this->assertTrue($snapshot['necessary']);
        $this->assertTrue($this->provider->has(ConsentCategory::Necessary->value));
    }

    public function test_unknown_category_returns_false(): void
    {
        $uuid = '55555555-5555-5555-5555-555555555555';
        $this->recordConsent($uuid, ['necessary' => true, 'analytics' => true]);
        $this->withConsentCookie($uuid);

        $this->assertFalse($this->provider->has('marketing-email'));
        $this->assertFalse($this->provider->has(''));
        $this->assertFalse($this->provider->has('NECESSARY'));
    }

    public function test_version_defaults_to_one_when_setting_is_absent(): void
    {
        $this->assertSame(1, $this->provider->version());
    }

    public function test_version_reads_the_setting_and_clamps_to_at_least_one(): void
    {
        DixlaseCookieSetting::setValue(CookieConsentStateProvider::VERSION_SETTING_KEY, '5');
        $this->assertSame(5, $this->provider->version());

        DixlaseCookieSetting::setValue(CookieConsentStateProvider::VERSION_SETTING_KEY, '0');
        $this->assertSame(1, $this->provider->version());

        DixlaseCookieSetting::setValue(CookieConsentStateProvider::VERSION_SETTING_KEY, 'not-a-number');
        $this->assertSame(1, $this->provider->version());
    }
}
