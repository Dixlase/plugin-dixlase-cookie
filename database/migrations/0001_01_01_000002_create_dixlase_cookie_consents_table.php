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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cookie consent record table — DELIBERATELY MINIMAL.
 *
 * Per `.backlog/dixlase-legal-cookie-split-strategy.md` the free
 * plugin records only what is necessary for the subject's own
 * lookup of their own consent. Audit-quality metadata (IP, UA,
 * session id) lives on DixlaseLegal's separate audit table, so the
 * shape here does NOT include any of those columns by design.
 *
 * One row per visitor (keyed by the unique `consent_id`). Each new
 * decision — accept, withdraw, change a category — UPDATES that single
 * row in place rather than appending, so the table holds only the
 * visitor's current state and does not grow per action. Full change
 * history is not kept here on purpose: the audit trail belongs to
 * DixlaseLegal's separate table, populated via the ConsentChanged event.
 *
 * Fields:
 * - `consent_id`     UUID issued at first accept; UNIQUE. Matches the
 *                     value persisted in the visitor's
 *                     `dixlase_cookie_consent_id` browser cookie so
 *                     subsequent requests resolve to this row
 * - `categories`     compact JSON array of the granted category keys
 *                     for the visitor's current decision (e.g.
 *                     ["necessary","analytics"]); a category absent from
 *                     the list is denied. The model presents this as the
 *                     full {category => bool} map the rest of the code
 *                     expects
 * - `policy_version` operator-controlled `cookie_consent_version`
 *                     value at the time of the decision; records which
 *                     policy iteration was agreed to
 * - `consented_at`   wall-clock time of the latest decision; distinct
 *                     from `created_at` (first consent) / `updated_at`
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plg_dixlase_cookie_consents', function (Blueprint $table) {
            $table->id();
            $table->string('consent_id', 36)->unique();
            $table->json('categories');
            $table->unsignedInteger('policy_version');
            $table->timestamp('consented_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plg_dixlase_cookie_consents');
    }
};
