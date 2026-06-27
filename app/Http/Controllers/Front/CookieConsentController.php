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

namespace Plugins\DixlaseCookie\App\Http\Controllers\Front;

use App\Contracts\Cookie\ConsentStateProviderInterface;
use App\Events\ConsentChanged;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Plugins\DixlaseCookie\App\Http\Requests\Front\AcceptCookieConsentRequest;
use Plugins\DixlaseCookie\App\Models\DixlaseCookieConsent;
use Plugins\DixlaseCookie\App\Models\DixlaseCookieSetting;
use Plugins\DixlaseCookie\App\Services\CookieConsentStateProvider;

/**
 * Records a visitor's cookie-consent decision (banner + withdrawal UI).
 *
 * Each call appends a NEW row to the consent log — never updates an
 * existing one — so the table doubles as an audit-friendly event log
 * (see {@see DixlaseCookieConsent}). The visitor is identified by a
 * UUID persisted in the {@see CookieConsentStateProvider::COOKIE_NAME}
 * cookie; the same UUID is reused across actions so withdrawals and
 * re-consents stay grouped under one history.
 *
 * Mirrors DixlaseLegal's accept controller but with the minimal,
 * privacy-first field set (no IP / UA / session id) and per-category
 * granularity instead of a single accept-all flag.
 */
class CookieConsentController extends Controller
{
    /** Setting key holding the persistent cookie lifetime in days. */
    public const LIFETIME_DAYS_SETTING_KEY = 'cookie_consent_lifetime_days';

    /** Default lifetime when the setting is unset, roughly one year. */
    public const DEFAULT_LIFETIME_DAYS = 365;

    /**
     * Record the visitor's per-category decision and queue a long-lived
     * cookie carrying their consent UUID.
     *
     * The category snapshot is stamped with the consent version that was
     * active at decision time. If an operator later bumps the version,
     * the stored row no longer matches and the provider treats it as
     * "not asked yet" — re-showing the banner without us touching the
     * client cookie.
     */
    public function accept(AcceptCookieConsentRequest $request): JsonResponse
    {
        $provider = app(ConsentStateProviderInterface::class);

        // Snapshot the visitor's state *before* writing the new row so
        // the ConsentChanged payload reflects the actual transition.
        $previous = $provider->snapshot();
        $version = $provider->version();

        $consentId = $this->resolveConsentId($request);
        $current = $request->consentedCategories();

        // Upsert by consent_id: one row per visitor, updated in place.
        // The full change history is captured separately by DixlaseLegal's
        // audit listener on the ConsentChanged event, so this table only
        // ever holds the visitor's current decision.
        DixlaseCookieConsent::updateOrCreate(
            ['consent_id' => $consentId],
            [
                'categories' => $current,
                'policy_version' => $version,
                'consented_at' => now(),
            ],
        );

        Cookie::queue(
            CookieConsentStateProvider::COOKIE_NAME,
            $consentId,
            $this->currentLifetimeDays() * 24 * 60,
        );

        // Expose the consent UUID out-of-band on the request so an audit
        // listener (DixlaseLegal) can persist it alongside its row. The
        // ConsentChanged payload itself stays a plain serialisable pair of
        // arrays for the Wasm / Capability Broker boundary, so the id is
        // not part of the event contract; consumers that do not care
        // simply ignore the attribute. This is a one-way hint — we never
        // depend on anyone reading it.
        $request->attributes->set('dixlase_cookie.consent_id', $consentId);

        // The row is always appended (full audit log), but ConsentChanged
        // fires only when the effective decision actually changed — true
        // to the event's name. A first-time decision (previous === []) is
        // always a change, including a first-time "reject all", because
        // "not asked yet" differs from "asked and denied". An identical
        // re-submit appends a row but dispatches nothing.
        if ($this->snapshotsDiffer($previous, $current)) {
            ConsentChanged::dispatch($previous, $current, $version);
        }

        return response()->json([
            'status' => 'ok',
            'consent' => $current,
            'version' => $version,
        ]);
    }

    /**
     * Whether two consent snapshots represent different decisions,
     * compared order-insensitively so key ordering never causes a false
     * "changed". An empty previous (first-time visitor) differs from any
     * non-empty current.
     *
     * @param  array<string, bool>  $previous
     * @param  array<string, bool>  $current
     */
    private function snapshotsDiffer(array $previous, array $current): bool
    {
        ksort($previous);
        ksort($current);

        return $previous !== $current;
    }

    /**
     * Reuse the visitor's existing consent UUID when their cookie is
     * present, otherwise mint a fresh one so a first-time visitor gets a
     * stable identity for all subsequent actions.
     */
    private function resolveConsentId(AcceptCookieConsentRequest $request): string
    {
        $existing = $request->cookie(CookieConsentStateProvider::COOKIE_NAME);
        if (is_string($existing) && $existing !== '') {
            return $existing;
        }

        return (string) Str::uuid();
    }

    /**
     * Read the configured cookie lifetime in days, falling back to
     * {@see self::DEFAULT_LIFETIME_DAYS} when missing or non-numeric.
     */
    public static function currentLifetimeDays(): int
    {
        $raw = DixlaseCookieSetting::getValue(self::LIFETIME_DAYS_SETTING_KEY);
        if ($raw === null || $raw === '' || ! is_numeric($raw)) {
            return self::DEFAULT_LIFETIME_DAYS;
        }

        return max(1, (int) $raw);
    }
}
