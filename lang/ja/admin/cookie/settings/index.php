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
    'heading' => 'Cookie 管理設定',

    'banner_heading' => '同意バナー',
    'enabled_label' => 'Cookie 同意バナーを表示する',
    'enabled_help' => 'ON にすると、まだ選択していない訪問者にバナーを表示します。「Cookie 設定」トリガーから、誰でもいつでも同意の変更・撤回ができます。',

    'lifetime_heading' => 'Cookie の寿命',
    'lifetime_label' => '寿命（日）',
    'lifetime_help' => '訪問者の選択を記憶し、再度確認するまでの期間です。1〜:max 日（デフォルト 365）。',

    'links_heading' => 'ポリシーリンク',
    'links_help' => 'バナーに表示する任意のリンクです。絶対 URL（http/https）またはパス（例: /privacy、/page/privacy-policy）を入力してください。空欄にするとリンクを表示しません。',
    'privacy_url_label' => 'プライバシーポリシー',
    'cookie_url_label' => 'クッキーポリシー',
    'url_invalid' => '絶対 URL（http/https）またはパスを入力してください。javascript: などのスキームは使用できません。',

    'version_heading' => '全訪問者に再同意を求める',
    'version_help' => '現在の同意バージョン: :version。バージョンを上げると保存済みの選択がすべて無効になり、全員にバナーが再表示されます。プライバシーポリシー改定後などに使用してください。',
    'bump_button' => '全員に再同意を求める',
    'bump_confirm_title' => '全訪問者に再同意を求めますか？',
    'bump_confirm_message' => '同意バージョンを 1 つ上げます。次回アクセス時にすべての訪問者へバナーが再表示されます。この操作は元に戻せません。',
    'bump_success' => '同意バージョンを :version に更新しました。全訪問者に再同意を求めます。',

    'confirm_title' => 'Cookie 設定を保存しますか？',
    'confirm_message' => 'これらの Cookie 同意設定を適用しますか？',
    'save_success' => 'Cookie 設定を保存しました。',
];
