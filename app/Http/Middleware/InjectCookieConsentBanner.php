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

namespace Plugins\DixlaseCookie\App\Http\Middleware;

use App\Contracts\Cookie\ConsentStateProviderInterface;
use App\Contracts\TranslationResolver;
use App\Helpers\LocaleHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Plugins\DixlaseCookie\App\Models\DixlaseCookieSetting;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Inject the cookie-consent banner into front-end HTML responses.
 *
 * The consent UI HTML is inserted just before </body> on responses
 * that satisfy ALL of the following:
 * - the response is text/html
 * - the request is not an admin-area request
 * - the cookie_consent_enabled setting is on
 *
 * The same markup serves both roles (B-3c banner + B-4 withdrawal UI):
 * it always carries a persistent "Cookie settings" trigger so a visitor
 * can re-open the editor at any time, and the panel auto-opens as a
 * banner only when the visitor has no current decision on record (the
 * provider's snapshot is empty — no cookie, no matching row, or the
 * stored decision predates the current policy version). The panel's
 * toggles are pre-filled from the current snapshot so a returning
 * visitor sees and can change their actual choices.
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

        // The banner is styled entirely by the plugin's own stylesheet
        // (served via the plugin's public assets symlink), so it renders
        // correctly on any host theme without a theme rebuild. Inject the
        // link into <head> when present; otherwise fall back to placing it
        // just before the banner markup.
        //
        // `bannerStylesheetUrl()` returns an empty string when the Vite
        // manifest is missing (fresh checkout not yet built, in-flight
        // deploy). Skip the `<link>` in that case rather than injecting
        // a broken `href=""` — the banner still shows with the fallback
        // inline layout the plugin provides.
        $stylesheetUrl = $this->bannerStylesheetUrl();
        if ($stylesheetUrl !== '') {
            $styleLink = '<link rel="stylesheet" href="'.e($stylesheetUrl).'">';
            if (str_contains($content, '</head>')) {
                $content = str_replace('</head>', $styleLink.'</head>', $content);
            } else {
                $bannerHtml = $styleLink.$bannerHtml;
            }
        }

        $response->setContent(str_replace('</body>', $bannerHtml.'</body>', $content));

        return $response;
    }

    /**
     * URL of the plugin's self-contained banner stylesheet. Built by Vite
     * from resources/src/scss/banner.scss into resources/assets/css/banner-<hash>.css
     * and served through the plugin's public assets symlink
     * (`public/assets/plugins/DixlaseCookie -> resources/assets`).
     *
     * Vite content-hashes the built filename (see vite.config.js), so we
     * cannot hard-code `css/banner.css` here — the file with that literal
     * name no longer exists. Instead read the current manifest.json,
     * which Vite regenerates on every build with the up-to-date mapping.
     * Fall back to a null-safe empty URL when the manifest is unavailable
     * (fresh checkout that has not been built yet, in-flight deploy) so
     * the middleware degrades to injecting the banner without a link tag
     * rather than crashing the response.
     */
    protected function bannerStylesheetUrl(): string
    {
        // Path comes from `config('dixlase_cookie.assets.manifest_path')`
        // so tests can point at a stub manifest under storage/framework/
        // testing/ without needing `npm run build` to have run first,
        // and without violating the root CLAUDE.md "tests must not
        // touch tracked working-tree files" rule (the real path lives
        // inside `plugins/DixlaseCookie/`, which is a tracked repo dir).
        $manifestPath = (string) config(
            'dixlase_cookie.assets.manifest_path',
            base_path('plugins/DixlaseCookie/resources/assets/manifest.json'),
        );
        if (! is_file($manifestPath)) {
            return '';
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);
        $file = $manifest['resources/src/scss/banner.scss']['file'] ?? null;
        if (! is_string($file)) {
            return '';
        }

        return asset('assets/plugins/DixlaseCookie/'.$file);
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

        // The only gate is the operator toggle: when on, the consent UI
        // is always injected (persistent re-open trigger + auto-opening
        // banner for visitors without a current decision).
        try {
            return (bool) DixlaseCookieSetting::getValue(self::ENABLED_SETTING_KEY);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Read the visitor's current consent snapshot from the provider, or
     * an empty array when none is bound / it errors.
     *
     * @return array<string, bool>
     */
    protected function currentSnapshot(): array
    {
        try {
            if (! app()->bound(ConsentStateProviderInterface::class)) {
                return [];
            }

            return app(ConsentStateProviderInterface::class)->snapshot();
        } catch (\Throwable $e) {
            return [];
        }
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
        // Render the banner (view + resolveLinks(), both of which call
        // __()) under bannerLocale(), then always restore the request's
        // locale so the rest of the response is unaffected.
        $restore = app()->getLocale();

        try {
            app()->setLocale($this->bannerLocale());

            $current = $this->currentSnapshot();

            return view('dixlase-cookie::front.cookie-consent-banner', [
                'links' => $this->resolveLinks(),
                // Auto-open as a banner only when there is no current
                // decision; otherwise the panel stays closed behind the
                // persistent "Cookie settings" trigger.
                'autoOpen' => $current === [],
                'current' => $current,
            ])->render();
        } catch (\Throwable $e) {
            return '';
        } finally {
            app()->setLocale($restore);
        }
    }

    /**
     * Locale the banner UI strings should be rendered in.
     *
     * When DixlaseMultilingual is installed it binds TranslationResolver
     * and the app locale already reflects the visitor's resolved language,
     * so we honor it (per-visitor localization). Without Multilingual,
     * core's SetFrontLocale lets the browser Accept-Language header outrank
     * the site default, which would show an English banner on an otherwise
     * Japanese, single-language site. In that case fall back to the site's
     * configured default language so the banner matches the rest of the
     * page. Any resolution failure falls back to the current app locale so
     * a hiccup never suppresses the banner.
     */
    protected function bannerLocale(): string
    {
        try {
            if (app()->bound(TranslationResolver::class)) {
                return app()->getLocale();
            }

            return LocaleHelper::getSiteDefaultLocale();
        } catch (\Throwable $e) {
            return app()->getLocale();
        }
    }

    /**
     * Build the optional policy links from operator-supplied URL
     * settings. Each link is included only when its URL is non-empty,
     * keeping the banner free of any dependency on other plugins
     * (operators paste plain URLs in the admin screen).
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
