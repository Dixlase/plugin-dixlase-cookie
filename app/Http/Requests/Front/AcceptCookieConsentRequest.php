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

namespace Plugins\DixlaseCookie\App\Http\Requests\Front;

use App\Enums\ConsentCategory;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a visitor's banner submission.
 *
 * Only the three optional categories are validated; `necessary` is
 * implicit and always granted, so any value the client sends for it is
 * ignored by the controller. Each optional category is an on/off flag —
 * absent means "not granted" (the controller reads them via
 * `boolean()`), so the field is `sometimes` rather than `required`,
 * which lets "reject all" submit an empty body and still record an
 * explicit denial.
 */
class AcceptCookieConsentRequest extends FormRequest
{
    /**
     * The accept endpoint is open to every visitor (consent is given
     * before authentication); authorisation is not gated here.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            ConsentCategory::Functional->value => ['sometimes', 'boolean'],
            ConsentCategory::Analytics->value => ['sometimes', 'boolean'],
            ConsentCategory::Marketing->value => ['sometimes', 'boolean'],
            // Accepted for forward-compatibility with clients that echo
            // the necessary toggle back; its value is never trusted.
            ConsentCategory::Necessary->value => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Resolve the visitor's chosen category map for persistence.
     *
     * `necessary` is forced true regardless of input; the optional
     * categories default to false when omitted so an empty "reject all"
     * body records an explicit denial rather than an absent decision.
     *
     * @return array<string, bool>
     */
    public function consentedCategories(): array
    {
        return [
            ConsentCategory::Necessary->value => true,
            ConsentCategory::Functional->value => $this->boolean(ConsentCategory::Functional->value),
            ConsentCategory::Analytics->value => $this->boolean(ConsentCategory::Analytics->value),
            ConsentCategory::Marketing->value => $this->boolean(ConsentCategory::Marketing->value),
        ];
    }
}
