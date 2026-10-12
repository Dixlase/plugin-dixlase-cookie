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

use App\Enums\MemberRole;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Admin GET smoke test.
 *
 * Opens every admin GET route this plugin registers and asserts the
 * server does not return 5xx, for both the admin and the editor role.
 *
 * This catches the class of release regression that produced the
 * Inquiry 0.3.0 incident: a controller / view / lang-key crash that
 * only surfaces during the real request lifecycle (middleware stack →
 * controller → view resolution → Blade compile). Unit tests on route
 * config or permissions do not fire that lifecycle, so a page can
 * declare clean and still crash on open.
 *
 * The test is deliberately narrow:
 *
 *   - **In scope**: status < 500 for every admin GET route under
 *     `dixlase-cookie::admin.cookie.*` for admin and editor roles.
 *   - **Out of scope**: 200 vs 403 role boundaries (the permission
 *     config test covers the declarative side); POST / PATCH / DELETE
 *     write flows; MySQL vs SQLite behaviour differences; CSP / inline-
 *     handler scanning.
 *
 * The route list is read from Route::getRoutes() by name prefix, so a
 * new admin GET route added later is picked up with no change here.
 */
class AdminGetRoutesSmokeTest extends TestCase
{
    use RefreshDatabase;

    /** Route-name prefix the plugin stamps on every admin route. */
    private const ROUTE_NAME_PREFIX = 'dixlase-cookie::admin.cookie.';

    private Member $admin;

    private Member $editor;

    protected function setUp(): void
    {
        parent::setUp();

        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';

        // Pin a valid 32-byte AES-256-CBC key regardless of what the
        // surrounding harness supplied. actingAs() writes to the session,
        // which goes through the Encrypter -- the plugin CI workflow
        // injects an APP_KEY whose decoded length is not 32 bytes, so
        // AES-256-CBC rejects it with "Unsupported cipher or incorrect key
        // length". Swap in a known-good key and replace the encrypter
        // binding directly, so no stale closure or stale instance can
        // resurface.
        $rawKey = random_bytes(32);
        config([
            'app.key' => 'base64:'.base64_encode($rawKey),
            'app.cipher' => 'AES-256-CBC',
        ]);
        $this->app->instance('encrypter', new \Illuminate\Encryption\Encrypter($rawKey, 'AES-256-CBC'));

        // The plugin's own migrations are not auto-applied in this harness.
        Artisan::call('migrate', [
            '--path' => 'plugins/DixlaseCookie/database/migrations',
            '--realpath' => false,
        ]);

        // Register the plugin's own ServiceProvider so its interface bindings
        // (ConsentStateProviderInterface -> CookieConsentStateProvider) and
        // view/translator namespaces are in place for the real request
        // lifecycle. PluginServiceProvider is what wires this in production;
        // in the test harness we do it ourselves.
        $this->app->register(\Plugins\DixlaseCookie\App\Providers\DixlaseCookieServiceProvider::class);

        // Register the plugin's admin routes under the admin URL prefix with
        // a reduced middleware stack. Core's `web + auth:member` is enough
        // to reach every controller; stripping the IP / lockdown /
        // activity-log layers keeps the test deterministic without pulling
        // in test fixtures for each of them. plugin.admin.access is
        // intentionally left off here -- role-gating is covered declaratively
        // by the existing permission config test.
        $adminUrl = (string) config('admin.admin_url', 'admin');
        $router = app('router');
        $router->group([
            'prefix' => $adminUrl,
            'middleware' => ['web', 'auth:member'],
        ], function () {
            include base_path('plugins/DixlaseCookie/routes/admin.php');
        });
        $router->getRoutes()->refreshNameLookups();
        $router->getRoutes()->refreshActionLookups();

        $this->admin = Member::factory()->create(['role' => MemberRole::ADMIN]);
        $this->editor = Member::factory()->create(['role' => MemberRole::EDITOR]);
    }

    protected function tearDown(): void
    {
        $_ENV['INSTALLED'] = 'false';
        $_SERVER['INSTALLED'] = 'false';
        parent::tearDown();
    }

    public function test_no_admin_get_route_returns_5xx_for_admin_role(): void
    {
        $urls = $this->adminGetRouteUrls();

        foreach ($urls as $name => $url) {
            $response = $this->actingAs($this->admin, 'member')->get($url);

            $this->assertLessThan(
                500,
                $response->status(),
                "GET {$url} ({$name}) returned {$response->status()} as admin",
            );
        }
    }

    public function test_no_admin_get_route_returns_5xx_for_editor_role(): void
    {
        $urls = $this->adminGetRouteUrls();

        foreach ($urls as $name => $url) {
            $response = $this->actingAs($this->editor, 'member')->get($url);

            $this->assertLessThan(
                500,
                $response->status(),
                "GET {$url} ({$name}) returned {$response->status()} as editor",
            );
        }
    }

    /**
     * Discover every admin GET route the plugin registered.
     *
     * @return array<string, string>  Map of route name to request URL.
     */
    private function adminGetRouteUrls(): array
    {
        $urls = [];
        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();
            if ($name === null || ! str_starts_with($name, self::ROUTE_NAME_PREFIX)) {
                continue;
            }
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }
            $urls[$name] = '/'.ltrim($route->uri(), '/');
        }

        $this->assertNotEmpty(
            $urls,
            'No admin GET routes were discovered; did routes/admin.php load correctly in setUp?',
        );

        return $urls;
    }
}
