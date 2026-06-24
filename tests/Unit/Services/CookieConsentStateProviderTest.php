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
use PHPUnit\Framework\TestCase;
use Plugins\DixlaseCookie\App\Services\CookieConsentStateProvider;

/**
 * Behaviour tests for the B-1 stub provider.
 *
 * The values these tests pin down are the GDPR-correct conservative
 * defaults — anything that depends on a visitor's actual recorded
 * decision is "no decision yet" (false / empty / 1). When B-2 and
 * B-3 replace the body of {@see CookieConsentStateProvider} with the
 * real cookie reader, these expectations change: at that point
 * `has('analytics')` etc. will depend on the persisted cookie value,
 * and these tests will need replacing rather than amending.
 */
class CookieConsentStateProviderTest extends TestCase
{
    private CookieConsentStateProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new CookieConsentStateProvider();
    }

    public function test_implements_the_core_contract(): void
    {
        $this->assertInstanceOf(ConsentStateProviderInterface::class, $this->provider);
    }

    public function test_necessary_category_is_always_granted(): void
    {
        // The `necessary` category is implicit — the site cannot
        // function without it (CSRF, session, the consent record
        // itself). Implementations MUST return true for it.
        $this->assertTrue($this->provider->has(ConsentCategory::Necessary->value));
        $this->assertTrue($this->provider->has('necessary'));
    }

    public function test_non_necessary_standard_categories_default_to_false(): void
    {
        // Without an accept action, no consent has been given for
        // these categories. Returning true here would be a GDPR
        // violation — that is what the stub is guarding against.
        $this->assertFalse($this->provider->has(ConsentCategory::Functional->value));
        $this->assertFalse($this->provider->has(ConsentCategory::Analytics->value));
        $this->assertFalse($this->provider->has(ConsentCategory::Marketing->value));
    }

    public function test_unknown_category_returns_false(): void
    {
        // The contract accepts arbitrary strings (plugin-defined
        // categories). An unknown one MUST return false, never throw
        // and never accidentally default to true.
        $this->assertFalse($this->provider->has('marketing-email'));
        $this->assertFalse($this->provider->has(''));
        $this->assertFalse($this->provider->has('NECESSARY'));
    }

    public function test_snapshot_is_empty_until_a_visitor_decision_is_recorded(): void
    {
        // The contract distinguishes "absent from snapshot" (not
        // asked) from "present and false" (asked and denied). The
        // stub has no visitor decision yet, so the snapshot is empty
        // — including `necessary`, which is implicit but unrecorded.
        $this->assertSame([], $this->provider->snapshot());
    }

    public function test_version_defaults_to_one(): void
    {
        // Initial policy version. B-2 will replace this with a
        // setting lookup so the operator-controlled bump action
        // invalidates all existing client cookies.
        $this->assertSame(1, $this->provider->version());
    }
}
