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
 * One row per visitor consent action — append-only event log.
 *
 * The shape is intentionally minimal (see the migration's class
 * docblock): consent_id, categories JSON, policy_version,
 * consented_at. Audit-quality metadata (IP, User-Agent, session id)
 * is NOT recorded here on purpose — that responsibility belongs on
 * DixlaseLegal's audit table per the free/paid split documented in
 * `.backlog/dixlase-legal-cookie-split-strategy.md`.
 *
 * Each visitor action (accept, withdraw, change a single category)
 * inserts a NEW row sharing the same `consent_id`. The current
 * effective state for a `consent_id` is always the most recent row.
 * Reading code should always sort by `consented_at DESC` (or `id
 * DESC` as a tiebreaker) and take the first record — there is no
 * separate "current state" column to keep in sync.
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
     * Scope: rows tagged with a specific consent_id (the per-visitor
     * cookie value). Combined with an `orderByDesc('consented_at')`
     * + `first()` it yields the visitor's current effective decision.
     */
    public function scopeForConsentId(Builder $query, string $consentId): Builder
    {
        return $query->where('consent_id', $consentId);
    }

    /**
     * Scope: most-recent-first ordering by the action timestamp.
     * Tiebreaks on `id` so two actions in the same millisecond still
     * resolve to a stable ordering.
     */
    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('consented_at')->orderByDesc('id');
    }
}
