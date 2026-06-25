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

use App\Enums\ConsentCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
     * Casts. `categories` is handled by its own accessor/mutator below,
     * not a plain array cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'policy_version' => 'integer',
            'consented_at' => 'datetime',
        ];
    }

    /**
     * `categories` is STORED compactly as a JSON array of granted
     * category keys (e.g. `["necessary","analytics"]`) to keep rows
     * small, but is PRESENTED to PHP as the full {category => bool} map
     * the rest of the code and the Core contracts expect. The fixed
     * category set makes the two representations equivalent: a category
     * absent from the stored list is simply denied.
     *
     * Storing names (not numeric indices) means no fragile positional
     * contract — the category string values are already part of the
     * public API and immutable.
     */
    protected function categories(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->expandGrantedList($value),
            set: fn ($value) => json_encode($this->collapseToGrantedList($value)),
        );
    }

    /**
     * Expand the stored granted-list into a full {category => bool} map.
     * All standard categories are present (false when not granted); any
     * non-standard granted key is preserved as true.
     *
     * @return array<string, bool>
     */
    private function expandGrantedList(mixed $value): array
    {
        $list = $this->normaliseList($value);

        $map = [];
        foreach (ConsentCategory::cases() as $category) {
            $map[$category->value] = false;
        }
        foreach ($list as $key) {
            if (is_string($key)) {
                $map[$key] = true;
            }
        }

        return $map;
    }

    /**
     * Collapse an incoming {category => bool} map (or an already-granted
     * list) into a de-duplicated list of granted category keys.
     *
     * @return list<string>
     */
    private function collapseToGrantedList(mixed $value): array
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        $granted = [];
        foreach ((array) $value as $key => $val) {
            if (is_int($key)) {
                // List form: the value itself is a granted category name.
                if (is_string($val)) {
                    $granted[] = $val;
                }
            } elseif ($val) {
                // Map form: include the key when the flag is truthy.
                $granted[] = $key;
            }
        }

        return array_values(array_unique($granted));
    }

    /**
     * Decode the raw stored value into a plain array of keys.
     *
     * @return array<int, mixed>
     */
    private function normaliseList(mixed $value): array
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        return is_array($value) ? array_values($value) : [];
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
