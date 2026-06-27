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

/*
|--------------------------------------------------------------------------
| 管理画面ナビゲーション設定
|--------------------------------------------------------------------------
|
| 管理画面のサイドバーに表示されるメニュー項目を定義します。
| このファイルが不要な場合は削除してください。
|
*/

return [
    // Independent "Cookie" section: its own top-level section so the
    // plugin stands on its own with no dependency on other plugins.
    'cookie' => [
        '_insert_after' => 'front',
        'text' => 'dixlase-cookie::admin/navigation.cookie.text',
        'icon' => 'fas fa-fw fa-cookie-bite',
        'can' => 'admin',
        'children' => [
            'cookie-settings' => [
                'text' => 'dixlase-cookie::admin/navigation.cookie.cookie-settings',
                'route' => 'dixlase-cookie::admin.cookie.settings.index',
                'icon' => 'fas fa-fw fa-cog',
                'can' => 'admin',
            ],
        ],
    ],
];