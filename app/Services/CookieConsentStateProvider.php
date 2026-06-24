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

namespace Plugins\DixlaseCookie\App\Services;

use App\Contracts\Cookie\ConsentStateProviderInterface;
use App\Enums\ConsentCategory;

/**
 * Default implementation of {@see ConsentStateProviderInterface}.
 *
 * This is the B-1 stub: it returns the GDPR-correct conservative
 * defaults until the cookie reader (B-3) and the version setting
 * reader (B-2) are wired up. The defaults are:
 *
 * - `has('necessary')` returns true — the site cannot function
 *   without strictly-necessary cookies, so they are implicitly
 *   granted.
 * - Every other category returns false — without an explicit
 *   accept action from the visitor, no consent has been given.
 * - `snapshot()` returns an empty array — no decision recorded yet.
 * - `version()` returns 1 — no policy bump yet.
 *
 * Consumers (DixlaseSEO's GA tag in particular) that probe this
 * provider via `app()->bound(...)` therefore see "consent system
 * present, analytics not yet accepted" and correctly defer their
 * tracking emission. The stub satisfies the contract end-to-end so
 * a follow-up PR can validate the soft-dependency wiring before the
 * real cookie reader replaces this class.
 */
final class CookieConsentStateProvider implements ConsentStateProviderInterface
{
    /**
     * {@inheritDoc}
     *
     * Returns true only for the implicit `necessary` category; all
     * other categories require an explicit accept that this stub has
     * no way to read yet. B-3 will replace this body with a lookup
     * against the persistent consent cookie issued at accept time.
     */
    public function has(string $category): bool
    {
        return $category === ConsentCategory::Necessary->value;
    }

    /**
     * {@inheritDoc}
     *
     * Empty until B-3 wires in the cookie reader. Returning an empty
     * array (rather than seeding `['necessary' => true]`) matches the
     * interface contract that absent categories mean "the visitor
     * has not been asked about this yet" — including `necessary`,
     * which is implicit but unrecorded.
     */
    public function snapshot(): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     *
     * Returns the default version 1. B-2 will replace this body with
     * a lookup against the cookie_consent_version setting so the
     * operator-controlled bump action invalidates client cookies.
     */
    public function version(): int
    {
        return 1;
    }
}
