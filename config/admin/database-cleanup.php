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
| データベースクリーンアップ設定
|--------------------------------------------------------------------------
|
| プラグインのデータベーステーブルのクリーンアップ設定を定義します。
| 管理画面からクリーンアップ対象テーブルと保持期間を管理できます。
| このファイルが不要な場合は削除してください。
|
*/

return [
    // 'example_logs' => [
    //     // 必須: データベーステーブル名
    //     'table' => 'dixlase-cookie_logs',
    //
    //     // 必須: 日付比較に使用するカラム
    //     'date_column' => 'created_at',
    //
    //     // 必須: デフォルトの保持期間（日数、nullの場合は期限切れのみ）
    //     'default_days' => 30,
    //
    //     // 必須: 説明（翻訳キーまたはロケールキー配列）
    //     'description' => [
    //         'ja' => 'プラグインのログ記録',
    //         'en' => 'Plugin log records',
    //     ],
    //
    //     // オプション: このクリーンアップタイプの有効/無効
    //     'enabled' => true,
    //
    //     // オプション: 日付カラムの型 ('datetime' or 'timestamp')
    //     'date_column_type' => 'datetime',
    // ],

    // 'example_cache' => [
    //     'table' => 'dixlase-cookie_cache',
    //     'date_column' => 'expires_at',
    //     'default_days' => null,
    //     'description' => [
    //         'ja' => 'プラグインのキャッシュデータ',
    //         'en' => 'Plugin cache data',
    //     ],
    //     'enabled' => true,
    //     'date_column_type' => 'timestamp',
    // ],
];