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

namespace Plugins\DixlaseCookie\App\Http\Controllers\Admin;

use App\Contracts\Cookie\ConsentStateProviderInterface;
use App\Traits\AdminInterfaceTrait;
use App\Traits\AdminLoggedInTrait;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Plugins\DixlaseCookie\App\Http\Controllers\Front\CookieConsentController;
use Plugins\DixlaseCookie\App\Http\Middleware\InjectCookieConsentBanner;
use Plugins\DixlaseCookie\App\Http\Requests\Admin\UpdateCookieSettingsRequest;
use Plugins\DixlaseCookie\App\Models\DixlaseCookieSetting;
use Plugins\DixlaseCookie\App\Services\CookieConsentStateProvider;

/**
 * Admin settings for the cookie-consent banner.
 *
 * Owns the operator-facing knobs: enable the banner, set the persistent
 * cookie lifetime, paste optional privacy / cookie policy URLs, and bump
 * the consent version to re-prompt every visitor. Audit-log viewing is
 * intentionally absent — that is a separate (paid) plugin's
 * responsibility per the free/paid split.
 */
class CookieAdminController extends Controller
{
    use AdminInterfaceTrait;
    use AdminLoggedInTrait;

    /** Upper bound for the persistent cookie lifetime (~10 years). */
    public const COOKIE_CONSENT_LIFETIME_MAX_DAYS = 3650;

    public function __construct()
    {
        $this->initialize();
        $this->initializeAfterLogin();
    }

    /**
     * Show the settings form.
     */
    public function index(): View
    {
        return view('dixlase-cookie::admin.cookie.settings.index', array_merge($this->viewParams, [
            'cookieConsentEnabled' => (bool) DixlaseCookieSetting::getValue(InjectCookieConsentBanner::ENABLED_SETTING_KEY),
            'cookieConsentLifetimeDays' => CookieConsentController::currentLifetimeDays(),
            'cookieConsentLifetimeMaxDays' => self::COOKIE_CONSENT_LIFETIME_MAX_DAYS,
            'privacyUrl' => (string) DixlaseCookieSetting::getValue(InjectCookieConsentBanner::PRIVACY_URL_SETTING_KEY, ''),
            'cookieUrl' => (string) DixlaseCookieSetting::getValue(InjectCookieConsentBanner::COOKIE_URL_SETTING_KEY, ''),
            'currentVersion' => $this->currentVersion(),
        ]));
    }

    /**
     * Persist the settings form.
     */
    public function update(UpdateCookieSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DixlaseCookieSetting::setValue(
            InjectCookieConsentBanner::ENABLED_SETTING_KEY,
            ! empty($validated['cookie_consent_enabled']) ? '1' : '0',
        );

        // Store the raw integer; CookieConsentController::currentLifetimeDays()
        // keeps applying its own floor so we do not double-clamp here.
        $lifetime = $validated['cookie_consent_lifetime_days'] ?? null;
        if ($lifetime !== null && $lifetime !== '') {
            DixlaseCookieSetting::setValue(
                CookieConsentController::LIFETIME_DAYS_SETTING_KEY,
                (string) (int) $lifetime,
            );
        }

        // Store URLs trimmed, or null to clear (getValue then yields the
        // in-code default and the banner omits the link).
        DixlaseCookieSetting::setValue(
            InjectCookieConsentBanner::PRIVACY_URL_SETTING_KEY,
            $this->normaliseUrl($validated['cookie_consent_privacy_url'] ?? null),
        );
        DixlaseCookieSetting::setValue(
            InjectCookieConsentBanner::COOKIE_URL_SETTING_KEY,
            $this->normaliseUrl($validated['cookie_consent_cookie_url'] ?? null),
        );

        return redirect()
            ->route('dixlase-cookie::admin.cookie.settings.index')
            ->with('success', __('dixlase-cookie::admin/cookie/settings/index.save_success'));
    }

    /**
     * Increment the consent version, invalidating every client cookie at
     * once so the banner re-appears for all visitors (privacy-policy
     * revision flow). No per-visitor ConsentChanged event fires here — the
     * change is recorded only when each visitor next re-consents.
     */
    public function bumpVersion(): RedirectResponse
    {
        $next = $this->currentVersion() + 1;
        DixlaseCookieSetting::setValue(
            CookieConsentStateProvider::VERSION_SETTING_KEY,
            (string) $next,
        );

        return redirect()
            ->route('dixlase-cookie::admin.cookie.settings.index')
            ->with('success', __('dixlase-cookie::admin/cookie/settings/index.bump_success', ['version' => $next]));
    }

    /**
     * Current consent version via the bound provider (single source of
     * truth for reading/clamping the version setting).
     */
    private function currentVersion(): int
    {
        return app(ConsentStateProviderInterface::class)->version();
    }

    /**
     * Trim a URL/path to a stored value, or null when empty so the row
     * is cleared rather than holding a blank string.
     */
    private function normaliseUrl(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
