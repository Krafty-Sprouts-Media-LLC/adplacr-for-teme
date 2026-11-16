<?php

declare(strict_types=1);

namespace KraftySprouts\AdPlacr\Ads;

use Closure;

/**
 * Picks the version of an ad to draw, by weight: with weights 3 and 1, A is chosen three times in four.
 */
final class Chooser
{
    /**
     * @param  Closure(int): int|null  $roll  answers a number from 0 up to, not including, its argument; real randomness when null
     */
    public static function choose(Ad $ad, ?Closure $roll = null): Version
    {
        if (count($ad->versions) === 1) {
            return $ad->versions[0];
        }

        $total = array_sum(array_map(static fn (Version $version): int => $version->weight, $ad->versions));
        $roll ??= static fn (int $max): int => random_int(0, $max - 1);

        // Any roll lands somewhere: wrapped into 0..total-1 so a careless roll still picks a version.
        $point = (($roll($total) % $total) + $total) % $total;

        foreach ($ad->versions as $version) {
            $point -= $version->weight;

            if ($point < 0) {
                return $version;
            }
        }

        return $ad->versions[array_key_last($ad->versions)];
    }
}
