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
 * Cookie consent record table — DELIBERATELY MINIMAL.
 *
 * Per `.backlog/dixlase-legal-cookie-split-strategy.md` the free
 * plugin records only what is necessary for the subject's own
 * lookup of their own consent. Audit-quality metadata (IP, UA,
 * session id) lives on DixlaseLegal's separate audit table, so the
 * shape here does NOT include any of those columns by design.
 *
 * Each visitor action (accept, withdraw, change a single category)
 * inserts a new row. The current state for a given `consent_id` is
 * always the most recent row — the table is append-only, an
 * implicit event log. This keeps the schema future-proof for the
 * withdrawal UI (B-4) without needing a schema change.
 *
 * Fields:
 * - `consent_id`     UUID issued at first accept; matches the
 *                     value persisted in the visitor's
 *                     `dixlase_cookie_consent_id` browser cookie so
 *                     subsequent requests can resolve their history
 * - `categories`     JSON snapshot of {category => bool} at the
 *                     time of this action; `necessary` is always
 *                     present and true
 * - `policy_version` operator-controlled `cookie_consent_version`
 *                     value at the time of this action; records
 *                     which policy iteration was being agreed to
 * - `consented_at`   wall-clock time of this action; distinct
 *                     from `created_at` so a future bulk import
 *                     can preserve the original timestamp
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plg_dixlase_cookie_consents', function (Blueprint $table) {
            $table->id();
            $table->string('consent_id', 36)->index();
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
