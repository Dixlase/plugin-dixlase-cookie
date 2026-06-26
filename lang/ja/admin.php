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
        'description' => 'Dixlase 向けの GDPR 対応クッキー同意プラグイン。カテゴリ別（必須 / 機能 / 分析 / マーケティング）の同意バナーを表示し、すべて許可・すべて拒否・選択を保存に対応。常設の「Cookie」トリガーからいつでも再オープンして同意を撤回できます。訪問者ごとにコンパクトな 1 行で保存し、コアの ConsentStateProvider 契約を実装するため、他プラグイン（SEO / Google アナリティクス等）が同意状態に応じてトラッカーを制御できます。管理画面でバナーの有効化・Cookie 寿命・プライバシー / クッキーポリシーのリンク設定・全員への再同意要求（バージョン更新）が可能。無料 / GPL。',
    ],
];