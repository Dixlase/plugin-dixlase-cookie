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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plugin-private settings table. Mirrors the DixlaseLegal layout
 * (`name` + `value`) so the operator-facing settings UI follows
 * the same pattern: a single shared key/value store the plugin's
 * admin controllers read and write through DixlaseCookieSetting.
 *
 * Operator-controlled keys this plugin will use (introduced in
 * follow-up commits B-3 / B-5):
 *
 * - `cookie_consent_enabled`         '0' | '1'
 * - `cookie_consent_version`         monotonic int, bumped to re-prompt every visitor
 * - `cookie_consent_lifetime_days`   int, persistent cookie max-age
 * - `cookie_consent_privacy_url`     operator-pasted URL to the privacy policy
 * - `cookie_consent_cookie_url`      operator-pasted URL to the cookie policy
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plg_dixlase_cookie_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plg_dixlase_cookie_settings');
    }
};
