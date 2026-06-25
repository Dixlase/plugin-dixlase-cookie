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

namespace Plugins\DixlaseCookie\App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * One row per visitor — the visitor's current consent state.
 *
 * The shape is intentionally minimal (see the migration's class
 * docblock): consent_id, categories JSON, policy_version,
 * consented_at. Audit-quality metadata (IP, User-Agent, session id)
 * is NOT recorded here on purpose — that responsibility belongs on
 * DixlaseLegal's audit table per the free/paid split documented in
 * `.backlog/dixlase-legal-cookie-split-strategy.md`.
 *
 * `consent_id` is UNIQUE: each visitor decision (accept, withdraw,
 * change a category) UPDATES that single row in place rather than
 * appending, so the table holds only current state and does not grow
 * per action. Change history is not kept here — the audit trail lives
 * in DixlaseLegal's table, fed by the ConsentChanged event. Reading
 * code resolves a visitor with `forConsentId($id)->first()`.
 *
 * @property int $id
 * @property string $consent_id
 * @property array<string, bool> $categories
 * @property int $policy_version
 * @property \Illuminate\Support\Carbon $consented_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class DixlaseCookieConsent extends Model
{
    /** @var string */
    protected $table = 'plg_dixlase_cookie_consents';

    /** @var list<string> */
    protected $fillable = [
        'consent_id',
        'categories',
        'policy_version',
        'consented_at',
    ];

    /**
     * Casts. `categories` JSON column always reads back as an
     * associative array of {category-key => bool}.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'categories' => 'array',
            'policy_version' => 'integer',
            'consented_at' => 'datetime',
        ];
    }

    /**
     * Scope: the row for a specific consent_id (the per-visitor cookie
     * value). Since consent_id is unique, `forConsentId($id)->first()`
     * yields the visitor's current decision.
     */
    public function scopeForConsentId(Builder $query, string $consentId): Builder
    {
        return $query->where('consent_id', $consentId);
    }

    /**
     * Scope: most-recent-first ordering. With the unique consent_id this
     * is a defensive no-op for per-visitor lookups, but kept so callers
     * reading across visitors get a stable, predictable order.
     */
    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('consented_at')->orderByDesc('id');
    }
}
