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
use Illuminate\Http\Request;
use Plugins\DixlaseCookie\App\Models\DixlaseCookieConsent;
use Plugins\DixlaseCookie\App\Models\DixlaseCookieSetting;

/**
 * Default implementation of {@see ConsentStateProviderInterface}.
 *
 * Reads the current visitor's consent from the persistent cookie
 * issued at accept time (B-3b). On each request the visitor carries a
 * {@see self::COOKIE_NAME} cookie holding the UUID that groups their
 * append-only consent rows; this class resolves the most recent row
 * for that UUID and derives the live category snapshot from it.
 *
 * Staleness: a stored decision is only honoured while its
 * `policy_version` still matches the operator-controlled
 * {@see self::VERSION_SETTING_KEY} setting. After an operator bumps
 * the version (privacy-policy revision), every older row is treated as
 * "no decision yet" so the banner re-appears and non-necessary
 * categories fall back to denied — without us touching client storage.
 *
 * Conservative fallbacks (no cookie, no matching row, stale version,
 * or unreadable settings) collapse to the GDPR-correct default: only
 * `necessary` is granted, everything else denied, snapshot empty.
 * Consumers (DixlaseSEO's GA tag in particular) probing via
 * `app()->bound(...)` therefore see "consent system present, analytics
 * not yet accepted" and correctly defer their tracking emission.
 */
final class CookieConsentStateProvider implements ConsentStateProviderInterface
{
    /**
     * Persistent per-visitor cookie holding the consent UUID. The
     * value matches the `consent_id` column on
     * {@see DixlaseCookieConsent}; B-3b issues it at accept time.
     */
    public const COOKIE_NAME = 'dixlase_cookie_consent_id';

    /** Setting key holding the operator-controlled consent version. */
    public const VERSION_SETTING_KEY = 'cookie_consent_version';

    /** Default consent version when the setting is unset (no bump yet). */
    public const DEFAULT_VERSION = 1;

    /**
     * {@inheritDoc}
     *
     * `necessary` is implicitly granted whenever the consent system is
     * present (the site cannot function without it), so it short-circuits
     * to true without a lookup. Every other category is true only when
     * the visitor's current (non-stale) recorded decision granted it.
     */
    public function has(string $category): bool
    {
        if ($category === ConsentCategory::Necessary->value) {
            return true;
        }

        return ($this->snapshot()[$category] ?? false) === true;
    }

    /**
     * Whether the operator currently has the consent banner switched on.
     *
     * This is NOT part of {@see ConsentStateProviderInterface}; it is an
     * optional probe consumers may duck-type for. When the banner is off,
     * no consent is being collected from visitors, so a consumer that gates
     * trackers on consent (DixlaseSEO's GA tag) should treat the site as
     * having no active gating and emit as usual — rather than suppressing
     * trackers forever against a banner the visitor can never answer.
     *
     * Mirrors the toggle the banner-injection middleware reads, so the two
     * stay in lock-step. Unreadable settings (e.g. table not migrated yet)
     * collapse to "off" so a half-installed site never blocks indefinitely.
     */
    public function isBannerEnabled(): bool
    {
        try {
            return (bool) DixlaseCookieSetting::getValue(
                \Plugins\DixlaseCookie\App\Http\Middleware\InjectCookieConsentBanner::ENABLED_SETTING_KEY
            );
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     *
     * Resolves the visitor's most recent consent row from the cookie
     * UUID and returns its category map (normalised to booleans, with
     * `necessary` forced present and true). Returns an empty array when
     * there is no cookie, no matching row, or the stored decision
     * predates the current policy version — i.e. "not asked yet".
     */
    public function snapshot(): array
    {
        $consentId = $this->currentConsentId();
        if ($consentId === null) {
            return [];
        }

        $record = DixlaseCookieConsent::query()
            ->forConsentId($consentId)
            ->latestFirst()
            ->first();

        if ($record === null) {
            return [];
        }

        // A decision agreed to under an older policy is no longer valid
        // consent — treat it as "not asked" so the banner re-appears.
        if ((int) $record->policy_version !== $this->version()) {
            return [];
        }

        $categories = $record->categories;
        if (! is_array($categories)) {
            return [];
        }

        $snapshot = [];
        foreach ($categories as $key => $value) {
            $snapshot[$key] = (bool) $value;
        }

        // The contract requires `necessary` to be present and true in
        // any non-empty snapshot, regardless of what was persisted.
        $snapshot[ConsentCategory::Necessary->value] = true;

        return $snapshot;
    }

    /**
     * {@inheritDoc}
     *
     * Reads the {@see self::VERSION_SETTING_KEY} setting, falling back
     * to {@see self::DEFAULT_VERSION} when missing or non-numeric. The
     * value is clamped to >= 1 per the contract.
     */
    public function version(): int
    {
        $raw = DixlaseCookieSetting::getValue(self::VERSION_SETTING_KEY);
        if ($raw === null || $raw === '' || ! is_numeric($raw)) {
            return self::DEFAULT_VERSION;
        }

        return max(1, (int) $raw);
    }

    /**
     * Resolve the consent UUID from the current request's cookie, or
     * null when there is no active request or no usable cookie value.
     */
    private function currentConsentId(): ?string
    {
        $request = request();
        if (! $request instanceof Request) {
            return null;
        }

        $value = $request->cookie(self::COOKIE_NAME);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
