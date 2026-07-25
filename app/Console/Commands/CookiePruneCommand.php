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

namespace Plugins\DixlaseCookie\App\Console\Commands;

use App\Contracts\Cookie\ConsentStateProviderInterface;
use Illuminate\Console\Command;
use Plugins\DixlaseCookie\App\Models\DixlaseCookieConsent;

/**
 * Opt-in maintenance command to keep the consent table tidy on
 * long-running sites.
 *
 * The table holds one row per visitor (unique consent_id, updated in
 * place), so it only grows with unique visitors — not per action. The
 * one form of accumulation worth reclaiming is abandoned stale rows:
 * visitors who consented under an older policy version and never
 * returned. Those rows are never honoured again (the provider already
 * treats an out-of-date policy_version as "not asked"), so deleting them
 * changes no behaviour.
 *
 * --dry-run reports the count only. There is intentionally no admin UI —
 * pruning consent records has GDPR "proof of consent" implications and
 * should be a deliberate, scheduled operator action.
 */
class CookiePruneCommand extends Command
{
    /** @var string */
    protected $signature = 'dls:cookie:prune
        {--dry-run : Report what would be deleted without deleting anything}';

    /** @var string */
    protected $description = 'Prune the cookie consent table (opt-in maintenance): drop abandoned rows stamped with an out-of-date policy version.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $current = app(ConsentStateProviderInterface::class)->version();

        $query = DixlaseCookieConsent::query()->where('policy_version', '<', $current);
        $count = (clone $query)->count();
        $this->line("Stale rows (policy_version < {$current}): {$count}");

        if ($dryRun) {
            $this->info('Dry run: no rows were deleted.');

            return self::SUCCESS;
        }

        $deleted = $query->delete();
        $this->info("Pruned {$deleted} row(s).");

        return self::SUCCESS;
    }
}
