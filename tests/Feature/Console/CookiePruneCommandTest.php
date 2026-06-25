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

    public function test_dry_run_deletes_nothing(): void
    {
        DixlaseCookieSetting::setValue(CookieConsentStateProvider::VERSION_SETTING_KEY, '2');
        $this->record('a', 1, '2026-06-01 10:00:00'); // stale
        $this->record('b', 2, '2026-06-02 10:00:00'); // current

        $this->artisan('dls:cookie:prune', ['--dry-run' => true])->assertExitCode(0);

        $this->assertSame(2, DixlaseCookieConsent::query()->count());
    }

    public function test_prunes_rows_below_current_version(): void
    {
        DixlaseCookieSetting::setValue(CookieConsentStateProvider::VERSION_SETTING_KEY, '2');
        $this->record('a', 1, '2026-06-01 10:00:00'); // stale, abandoned
        $this->record('b', 2, '2026-06-02 10:00:00'); // current
        $this->record('c', 1, '2026-06-01 11:00:00'); // stale, abandoned

        $this->artisan('dls:cookie:prune')->assertExitCode(0);

        $this->assertSame(1, DixlaseCookieConsent::query()->count());
        $this->assertSame('b', DixlaseCookieConsent::query()->first()->consent_id);
    }

    public function test_keeps_current_version_rows(): void
    {
        // Default version is 1; a v1 row is current and must survive.
        $this->record('a', 1, '2026-06-01 10:00:00');

        $this->artisan('dls:cookie:prune')->assertExitCode(0);

        $this->assertSame(1, DixlaseCookieConsent::query()->count());
    }
}
