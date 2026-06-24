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

namespace Plugins\DixlaseCookie\App\Http\Middleware;

use App\Contracts\Cookie\ConsentStateProviderInterface;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Plugins\DixlaseCookie\App\Models\DixlaseCookieSetting;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Inject the cookie-consent banner into front-end HTML responses.
 *
 * The banner HTML is inserted just before </body> on responses that
 * satisfy ALL of the following:
 * - the response is text/html
 * - the request is not an admin-area request
 * - the cookie_consent_enabled setting is on
 * - the visitor has no current consent on record — i.e. the consent
 *   provider's snapshot is empty (no cookie, no matching row, or the
 *   stored decision predates the current policy version). An operator
 *   version bump therefore re-shows the banner globally without us
 *   touching client storage.
 *
 * The banner is intentionally never shown to a visitor who already has
 * a current decision; re-opening it for them is the withdrawal UI's job
 * (B-4), reached from a persistent "Cookie settings" entry point.
 */
class InjectCookieConsentBanner
{
    /** Setting key toggling whether the banner is shown at all. */
    public const ENABLED_SETTING_KEY = 'cookie_consent_enabled';

    /** Setting key holding the operator-supplied privacy policy URL. */
    public const PRIVACY_URL_SETTING_KEY = 'cookie_consent_privacy_url';

    /** Setting key holding the operator-supplied cookie policy URL. */
    public const COOKIE_URL_SETTING_KEY = 'cookie_consent_cookie_url';

    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $response = $next($request);

        if (! $this->shouldInjectBanner($request, $response)) {
            return $response;
        }

        $bannerHtml = $this->renderBanner();
        if ($bannerHtml === '') {
            return $response;
        }

        $content = $response->getContent();
        if (! is_string($content) || ! str_contains($content, '</body>')) {
            return $response;
        }

        $response->setContent(str_replace('</body>', $bannerHtml.'</body>', $content));

        return $response;
    }

    /**
     * Decide whether the banner should be injected for this request.
     */
    protected function shouldInjectBanner(Request $request, SymfonyResponse $response): bool
    {
        if (! $response instanceof Response) {
            return false;
        }

        $contentType = $response->headers->get('Content-Type', '');
        if (! str_contains($contentType, 'text/html') && $contentType !== '') {
            return false;
        }

        if ($this->isAdminRequest($request)) {
            return false;
        }

        // Settings call comes first: if the operator has the banner
        // disabled, the consent-state lookup below is moot.
        try {
            $enabled = (bool) DixlaseCookieSetting::getValue(self::ENABLED_SETTING_KEY);
        } catch (\Throwable $e) {
            return false;
        }
        if (! $enabled) {
            return false;
        }

        // A non-empty snapshot means the visitor has a current decision
        // on record (matching the live policy version) — suppress the
        // banner. An empty snapshot means "not asked yet" → show it.
        try {
            if (! app()->bound(ConsentStateProviderInterface::class)) {
                return true;
            }
            $snapshot = app(ConsentStateProviderInterface::class)->snapshot();
        } catch (\Throwable $e) {
            return true;
        }

        return $snapshot === [];
    }

    /**
     * Whether the request targets the admin area, where the banner is
     * never shown.
     */
    protected function isAdminRequest(Request $request): bool
    {
        try {
            $adminUrl = \App\Helpers\AdminHelper::getAdminUrl() ?? 'admin';
        } catch (\Throwable $e) {
            $adminUrl = 'admin';
        }

        return $request->is($adminUrl) || $request->is($adminUrl.'/*');
    }

    /**
     * Render the banner partial to an HTML string.
     *
     * The partial is self-contained (no @push/@section/@stack), so
     * rendering it here after the host page is already built does not
     * disturb the page's Blade section stack.
     */
    protected function renderBanner(): string
    {
        try {
            return view('dixlase-cookie::front.cookie-consent-banner', [
                'links' => $this->resolveLinks(),
            ])->render();
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Build the optional policy links from operator-supplied URL
     * settings. Each link is included only when its URL is non-empty,
     * keeping the banner free of any dependency on DixlaseLegal /
     * DixlasePages (operators paste plain URLs in the admin screen).
     *
     * @return list<array{url: string, label: string}>
     */
    protected function resolveLinks(): array
    {
        $links = [];

        $privacyUrl = trim((string) DixlaseCookieSetting::getValue(self::PRIVACY_URL_SETTING_KEY, ''));
        if ($privacyUrl !== '') {
            $links[] = [
                'url' => $privacyUrl,
                'label' => __('dixlase-cookie::front/cookie-consent.privacy_link'),
            ];
        }

        $cookieUrl = trim((string) DixlaseCookieSetting::getValue(self::COOKIE_URL_SETTING_KEY, ''));
        if ($cookieUrl !== '') {
            $links[] = [
                'url' => $cookieUrl,
                'label' => __('dixlase-cookie::front/cookie-consent.cookie_link'),
            ];
        }

        return $links;
    }
}
