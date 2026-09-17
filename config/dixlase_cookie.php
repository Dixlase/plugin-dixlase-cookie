<?php

/**
 * This file is part of DixlaseCookie.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
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
    'assets' => [
        // Path to the Vite manifest.json for this plugin's front-end
        // bundle. The banner injector middleware reads it to resolve
        // the current content-hashed CSS filename (`banner-<hash>.css`)
        // — hard-coding the filename would 404 after every rebuild
        // that changes the CSS bytes. Overridable so tests can point
        // at a stub manifest under `storage/framework/testing/` per the
        // root CLAUDE.md "tests must not touch tracked working-tree
        // files" rule, without needing the plugin to have run
        // `npm run build` first.
        'manifest_path' => base_path('plugins/DixlaseCookie/resources/assets/manifest.json'),
    ],
];