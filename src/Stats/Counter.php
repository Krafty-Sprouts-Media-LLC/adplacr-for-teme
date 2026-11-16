<?php

declare(strict_types=1);

namespace KraftySprouts\AdPlacr\Stats;

use Illuminate\Support\Facades\DB;

/**
 * The plugin's own table of daily counts: adds one event, and removes days past the retention window.
 */
final class Counter
{
    /**
     * @param  'impression'|'click'  $event
     */
    public static function record(string $event, int $slot, string $version, string $day): void
    {
        $impressions = $event === 'impression' ? 1 : 0;
        $clicks = $event === 'click' ? 1 : 0;

        // One statement, so two visitors at the same moment both count.
        DB::statement(
            'insert into adplacr_daily (day, slot, version, impressions, clicks) values (?, ?, ?, ?, ?) '
            .'on conflict (day, slot, version) do update set '
            .'impressions = adplacr_daily.impressions + excluded.impressions, clicks = adplacr_daily.clicks + excluded.clicks',
            [$day, $slot, $version, $impressions, $clicks],
        );
    }

    /**
     * Removes every day before the cutoff (a `Y-m-d` date).
     */
    public static function pruneBefore(string $cutoff): int
    {
        return DB::table('adplacr_daily')->where('day', '<', $cutoff)->delete();
    }
}
