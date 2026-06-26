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

return [
    'plugin' => [
        'name' => 'DixlaseCookie',
        'description' => 'GDPR-ready cookie consent for Dixlase. Shows a per-category consent banner (necessary / functional / analytics / marketing) with accept-all, reject-all and save-selection, plus a persistent "Cookie" trigger to re-open and withdraw consent at any time. Stores one compact row per visitor and implements the Core ConsentStateProvider contract so other plugins (e.g. SEO/Google Analytics) can gate trackers on consent. Admin screen to enable the banner, set the cookie lifetime, add privacy/cookie policy links, and bump the consent version to re-prompt everyone. Free / GPL.',
    ],
];