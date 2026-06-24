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
| プラグインのデフォルト権限設定
|--------------------------------------------------------------------------
|
| 各メニュー/機能に対するデフォルトの権限を定義します。
| 管理画面で変更された場合のみ、role_permission_overrides テーブルに差分が保存されます。
| このファイルが不要な場合は削除してください。
|
| access_roles: 編集権限（write）- この値以上の権限を持つユーザーが編集可能
| view_roles: 閲覧権限（read）- この値以上の権限を持つユーザーが閲覧可能
|
| 権限値（MemberRole enum）:
| - SUPER_ADMIN = 10 (特権管理者専用)
| - ADMIN = 9 (管理者以上)
| - EDITOR = 8 (編集者以上)
| - AUTHOR = 7 (投稿者以上)
| - CONTRIBUTOR = 6 (寄稿者以上)
| - RECEPTIONIST = 5 (受付以上)
| - GUEST = 1 (全員)
|
*/

use App\Enums\MemberRole;

return [
    'permissions' => [
        // // 一覧画面
        // 'settings.dixlase-cookie.index' => [
        //     'access_roles' => MemberRole::EDITOR->value,
        //     'view_roles' => MemberRole::EDITOR->value,
        // ],
        //
        // // 作成画面
        // 'settings.dixlase-cookie.create' => [
        //     'access_roles' => MemberRole::EDITOR->value,
        //     'view_roles' => MemberRole::EDITOR->value,
        // ],
        //
        // // 設定画面（管理者のみ）
        // 'settings.dixlase-cookie.settings' => [
        //     'access_roles' => MemberRole::ADMIN->value,
        //     'view_roles' => MemberRole::ADMIN->value,
        // ],
    ],
];