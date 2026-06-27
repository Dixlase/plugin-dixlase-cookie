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

namespace Plugins\DixlaseCookie\App\Http\Requests\Admin;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Plugins\DixlaseCookie\App\Http\Controllers\Admin\CookieAdminController;

/**
 * Validates the cookie-consent admin settings form.
 *
 * The two policy-link fields accept either an absolute URL or a
 * site-relative path (leading "/"), so operators can paste a Dixlase
 * page path without DixlaseCookie depending on any page-providing
 * plugin.
 */
class UpdateCookieSettingsRequest extends FormRequest
{
    /**
     * Access is already gated by the admin route stack
     * (auth:member + admin-only navigation); no extra check here.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'cookie_consent_enabled' => ['nullable', 'boolean'],
            'cookie_consent_lifetime_days' => [
                'nullable',
                'integer',
                'min:1',
                'max:'.CookieAdminController::COOKIE_CONSENT_LIFETIME_MAX_DAYS,
            ],
            'cookie_consent_privacy_url' => ['nullable', 'string', 'max:500', $this->urlOrPathRule()],
            'cookie_consent_cookie_url' => ['nullable', 'string', 'max:500', $this->urlOrPathRule()],
        ];
    }

    /**
     * A permissive link rule: accepts an empty value, an absolute http(s)
     * URL, or any site path (with or without a leading slash, e.g.
     * "/privacy" or "page/privacy-policy"). It rejects only other URI
     * schemes (javascript:, data:, mailto:, …) so the value stays safe to
     * render as an href.
     */
    private function urlOrPathRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || $value === '') {
                return;
            }

            // Absolute http(s) URLs are always allowed.
            if (preg_match('#^https?://#i', $value) === 1) {
                return;
            }

            // Any other explicit URI scheme is rejected; everything else is
            // treated as a site path and accepted.
            if (preg_match('#^[a-z][a-z0-9+.\-]*:#i', $value) === 1) {
                $fail(__('dixlase-cookie::admin/cookie/settings/index.url_invalid'));
            }
        };
    }
}
