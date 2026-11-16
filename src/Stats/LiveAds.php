<?php

declare(strict_types=1);

namespace KraftySprouts\AdPlacr\Stats;

use DateTimeZone;
use Illuminate\Support\Facades\DB;
use KraftySprouts\AdPlacr\Ads\Ads;

/**
 * The plugin's own list of ad versions that may be counted: every version of every ad that can be shown,
 * and none at all while statistics are off. The tracking route reads it; a saved change to the settings
 * rewrites it.
 */
final class LiveAds
{
    /**
     * @param  array<string, mixed>  $values  the plugin's settings group
     */
    public static function sync(array $values): void
    {
        $rows = [];

        if (($values['analytics_enabled'] ?? false) === true) {
            foreach (Ads::fromSettings($values, new DateTimeZone('UTC')) as $ad) {
                foreach ($ad->versions as $version) {
                    $rows[] = ['slot' => $ad->slot, 'version' => $version->id];
                }
            }
        }

        DB::transaction(static function () use ($rows): void {
            DB::table('adplacr_live')->delete();

            if ($rows !== []) {
                DB::table('adplacr_live')->insert($rows);
            }
        });
    }

    public static function counts(int $slot, string $version): bool
    {
        return DB::table('adplacr_live')->where('slot', $slot)->where('version', $version)->exists();
    }
}
