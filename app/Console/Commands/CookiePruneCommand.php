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

namespace Plugins\DixlaseCookie\App\Console\Commands;

use App\Contracts\Cookie\ConsentStateProviderInterface;
use Illuminate\Console\Command;
use Plugins\DixlaseCookie\App\Models\DixlaseCookieConsent;

/**
 * Opt-in maintenance command to keep the append-only consent log from
 * growing without bound on long-running sites.
 *
 * Two independent, audit-aware modes (run one or both):
 *
 * - --stale: delete rows whose policy_version is below the current
 *   consent version. These predate the latest operator bump and are
 *   never honoured again (the provider already treats them as "not
 *   asked"), so deleting them changes no behaviour.
 *
 * - --keep-latest: keep only the most recent row per consent_id and drop
 *   the older history. The free plugin only ever reads the latest row, so
 *   this is safe for functionality — but it discards the consent trail,
 *   so reach for it only when full history is not required (audit-grade
 *   retention belongs to DixlaseLegal).
 *
 * Nothing is deleted without an explicit mode; --dry-run reports counts
 * only. There is intentionally no admin UI for this — pruning consent
 * records has GDPR "proof of consent" implications and should be a
 * deliberate, scheduled operator action.
 */
class CookiePruneCommand extends Command
{
    /** @var string */
    protected $signature = 'dls:cookie:prune
        {--stale : Delete rows whose policy_version is below the current consent version}
        {--keep-latest : Keep only the most recent row per consent_id, deleting older history}
        {--dry-run : Report what would be deleted without deleting anything}';

    /** @var string */
    protected $description = 'Prune the cookie consent log (opt-in maintenance): drop never-honoured stale rows and/or compact history to the latest row per visitor.';

    public function handle(): int
    {
        $stale = (bool) $this->option('stale');
        $keepLatest = (bool) $this->option('keep-latest');
        $dryRun = (bool) $this->option('dry-run');

        if (! $stale && ! $keepLatest) {
            $this->error('Specify at least one mode: --stale and/or --keep-latest.');

            return self::FAILURE;
        }

        $deleted = 0;

        if ($stale) {
            $deleted += $this->pruneStale($dryRun);
        }

        if ($keepLatest) {
            $deleted += $this->pruneSupersededHistory($dryRun);
        }

        if ($dryRun) {
            $this->info('Dry run: no rows were deleted.');
        } else {
            $this->info("Pruned {$deleted} row(s).");
        }

        return self::SUCCESS;
    }

    /**
     * Delete consent rows stamped with an older policy version than the
     * current one. Returns the number of rows deleted (0 on dry run).
     */
    private function pruneStale(bool $dryRun): int
    {
        $current = app(ConsentStateProviderInterface::class)->version();

        $query = DixlaseCookieConsent::query()->where('policy_version', '<', $current);
        $count = (clone $query)->count();
        $this->line("Stale rows (policy_version < {$current}): {$count}");

        return $dryRun ? 0 : $query->delete();
    }

    /**
     * Delete every consent row except the most recent one per consent_id
     * (highest id == latest insertion in this append-only log). Returns
     * the number of rows deleted (0 on dry run).
     */
    private function pruneSupersededHistory(bool $dryRun): int
    {
        $keepIds = DixlaseCookieConsent::query()
            ->selectRaw('MAX(id) as id')
            ->groupBy('consent_id')
            ->pluck('id')
            ->all();

        if ($keepIds === []) {
            $this->line('Superseded history rows: 0');

            return 0;
        }

        $query = DixlaseCookieConsent::query()->whereNotIn('id', $keepIds);
        $count = (clone $query)->count();
        $this->line("Superseded history rows (older than latest per visitor): {$count}");

        return $dryRun ? 0 : $query->delete();
    }
}
