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

use Illuminate\Database\Eloquent\Model;

/**
 * Plugin-private settings store for DixlaseCookie.
 *
 * Mirrors the DixlaseLegal\DixlaseLegalSetting pattern: a thin
 * key/value model with static `getValue()` / `setValue()` accessors
 * the admin controllers (B-5) and the persistent-cookie reader
 * (B-3) consume. Keeps settings inside this plugin's own table so
 * Core's site_settings table stays uncluttered.
 *
 * @property int $id
 * @property string $name
 * @property string|null $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class DixlaseCookieSetting extends Model
{
    /** @var string */
    protected $table = 'plg_dixlase_cookie_settings';

    /** @var list<string> */
    protected $fillable = [
        'name',
        'value',
    ];

    /**
     * Read a setting value, falling back to `$default` when the row
     * is missing. Returns the stored string verbatim — callers that
     * need integers / booleans should cast at the call site so the
     * default value's type does not get coerced.
     */
    public static function getValue(string $name, mixed $default = null): mixed
    {
        $setting = self::query()->where('name', $name)->first();

        return $setting?->value ?? $default;
    }

    /**
     * Upsert a setting value. Stores `null` as SQL NULL so callers
     * that want "use the in-code default" can clear a row without
     * deleting it.
     */
    public static function setValue(string $name, mixed $value): void
    {
        self::query()->updateOrCreate(
            ['name' => $name],
            ['value' => $value],
        );
    }

    /**
     * Convenience: upsert a map of {key => value} in a single call.
     *
     * @param  array<string, mixed>  $settings
     */
    public static function setMany(array $settings): void
    {
        foreach ($settings as $key => $value) {
            self::setValue($key, $value);
        }
    }
}
