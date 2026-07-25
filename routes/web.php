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

use Illuminate\Support\Facades\Route;
use Plugins\DixlaseCookie\App\Http\Controllers\Front\CookieConsentController;

/*
|--------------------------------------------------------------------------
| DixlaseCookie Web Routes
|--------------------------------------------------------------------------
|
| フロントエンド用のルート定義
|
| 注意: このファイルは自動的に以下のミドルウェアが適用されます
| - web: セッション、CSRF保護
| - front.ip: フロントエンドIPアドレス制限
|
| セキュリティに関する注意:
| - front.ip ミドルウェアでIPアドレスフィルタリングを実施します
| - IPアドレスフィルタリングを実施しないとセキュリティリスクが高まります
|
*/

// Cookie consent: record a visitor's per-category decision (banner +
// withdrawal UI). Intentionally outside any front.ip restriction so
// every visitor can give or withdraw consent. CSRF protection from the
// web middleware group still applies.
Route::post('/cookie-consent/accept', [CookieConsentController::class, 'accept'])
    ->name('dixlase-cookie::cookie-consent.accept');