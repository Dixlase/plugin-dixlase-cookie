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

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| DixlaseCookie Admin Routes
|--------------------------------------------------------------------------
|
| 管理画面用のルート定義
| 
| 注意: このファイルは自動的に以下のミドルウェアが適用されます
| - web: セッション、CSRF保護
| - auth:member: 管理者認証
| - admin.ip: 管理画面IPアドレス制限
|
| セキュリティに関する注意:
| - auth:member ミドルウェアで認証を要求します
| - admin.ip ミドルウェアでIPアドレスフィルタリングを実施します
| - IPアドレスフィルタリングを実施しないとセキュリティリスクが高まります
|
*/

// 管理画面用のルート
// 例: Route::resource('dixlase-cookie', Controller::class);