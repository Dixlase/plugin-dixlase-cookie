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

namespace Plugins\DixlaseCookie\Tests\Feature\Console;

use App\Contracts\Cookie\ConsentStateProviderInterface;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Plugins\DixlaseCookie\App\Console\Commands\CookiePruneCommand;
use Plugins\DixlaseCookie\App\Models\DixlaseCookieConsent;
use Plugins\DixlaseCookie\App\Models\DixlaseCookieSetting;
use Plugins\DixlaseCookie\App\Services\CookieConsentStateProvider;
use Tests\TestCase;

/**
 * Feature tests for the opt-in dls:cookie:prune maintenance command.
 */
class CookiePruneCommandTest extends TestCase
{
    use RefreshDatabase;

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

        // Plugin command auto-discovery reads the (empty) plugins table
        // under RefreshDatabase, so register the command explicitly.
        $this->app->make(Kernel::class)->registerCommand($this->app->make(CookiePruneCommand::class));
    }

    private function record(string $consentId, int $version, string $consentedAt): void
    {
        DixlaseCookieConsent::create([
            'consent_id' => $consentId,
            'categories' => ['necessary' => true, 'analytics' => true],
            'policy_version' => $version,
            'consented_at' => $consentedAt,
        ]);
    }

    public function test_requires_a_mode(): void
    {
        $this->artisan('dls:cookie:prune')->assertExitCode(1);
        $this->assertSame(0, DixlaseCookieConsent::query()->count());
    }

    public function test_dry_run_deletes_nothing(): void
    {
        DixlaseCookieSetting::setValue(CookieConsentStateProvider::VERSION_SETTING_KEY, '2');
        $this->record('a', 1, '2026-06-01 10:00:00');
        $this->record('a', 2, '2026-06-02 10:00:00');

        $this->artisan('dls:cookie:prune', ['--stale' => true, '--keep-latest' => true, '--dry-run' => true])
            ->assertExitCode(0);

        $this->assertSame(2, DixlaseCookieConsent::query()->count());
    }

    public function test_stale_mode_deletes_rows_below_current_version(): void
    {
        DixlaseCookieSetting::setValue(CookieConsentStateProvider::VERSION_SETTING_KEY, '2');
        $this->record('a', 1, '2026-06-01 10:00:00'); // stale
        $this->record('a', 2, '2026-06-02 10:00:00'); // current
        $this->record('b', 1, '2026-06-01 11:00:00'); // stale

        $this->artisan('dls:cookie:prune', ['--stale' => true])->assertExitCode(0);

        $this->assertSame(1, DixlaseCookieConsent::query()->count());
        $this->assertSame(2, DixlaseCookieConsent::query()->first()->policy_version);
    }

    public function test_keep_latest_mode_compacts_to_one_row_per_consent_id(): void
    {
        $this->record('a', 1, '2026-06-01 10:00:00');
        $this->record('a', 1, '2026-06-02 10:00:00'); // latest for a
        $this->record('b', 1, '2026-06-03 10:00:00'); // only row for b

        $this->artisan('dls:cookie:prune', ['--keep-latest' => true])->assertExitCode(0);

        $this->assertSame(2, DixlaseCookieConsent::query()->count());
        $this->assertSame(1, DixlaseCookieConsent::query()->forConsentId('a')->count());
        $this->assertSame(1, DixlaseCookieConsent::query()->forConsentId('b')->count());

        // The surviving row for "a" is the most recent one.
        $latestA = DixlaseCookieConsent::query()->forConsentId('a')->first();
        $this->assertSame('2026-06-02 10:00:00', $latestA->consented_at->format('Y-m-d H:i:s'));
    }
}
