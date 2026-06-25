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
    'heading' => 'Cookie Management Settings',

    'banner_heading' => 'Consent banner',
    'enabled_label' => 'Show the cookie consent banner',
    'enabled_help' => 'When on, the banner is shown to visitors who have not made a choice, and a "Cookie settings" trigger lets anyone change or withdraw consent at any time.',

    'lifetime_heading' => 'Cookie lifetime',
    'lifetime_label' => 'Lifetime (days)',
    'lifetime_help' => 'How long the visitor\'s choice is remembered before they are asked again. 1–:max days (default 365).',

    'links_heading' => 'Policy links',
    'links_help' => 'Optional links shown in the banner. Enter an absolute http(s) URL or a path (e.g. /privacy or legal/privacy-policy). Leave blank to hide a link.',
    'privacy_url_label' => 'Privacy Policy',
    'cookie_url_label' => 'Cookie Policy',
    'url_invalid' => 'Enter an absolute http(s) URL or a path. Schemes such as javascript: are not allowed.',

    'version_heading' => 'Re-prompt all visitors',
    'version_help' => 'Current consent version: :version. Bumping the version invalidates every stored choice so the banner re-appears for everyone — use this after a privacy policy revision.',
    'bump_button' => 'Ask everyone to re-consent',
    'bump_confirm_title' => 'Re-prompt all visitors?',
    'bump_confirm_message' => 'This increases the consent version by one. Every visitor will see the banner again on their next visit. This cannot be undone.',
    'bump_success' => 'Consent version bumped to :version. All visitors will be re-prompted.',

    'confirm_title' => 'Save cookie settings?',
    'confirm_message' => 'Apply these cookie consent settings?',
    'save_success' => 'Cookie settings saved.',
];
