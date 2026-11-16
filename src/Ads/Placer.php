<?php

declare(strict_types=1);

namespace KraftySprouts\AdPlacr\Ads;

use Closure;
use DateTimeZone;
use Teme\Contracts\Insertions\ArticleFacts;
use Teme\Contracts\Insertions\Insertion;

/**
 * Turns the plugin's settings and one article into the insertions Teme should draw there: the Active ads
 * whose rules fit the article, each with the version chosen for this draw.
 */
final class Placer
{
    private const int DEFAULT_MOBILE = 782;

    private const int DEFAULT_TABLET = 1024;

    /**
     * @param  array<string, mixed>  $values  the plugin's settings group
     * @param  int  $now  Unix time, for the schedule
     * @param  Closure(int): int|null  $roll  see Chooser::choose()
     * @return list<Insertion> in slot order
     */
    public static function insertionsFor(array $values, DateTimeZone $siteZone, ArticleFacts $article, int $now, ?Closure $roll = null): array
    {
        $renderer = new Renderer(
            self::breakpoint($values['mobile_breakpoint'] ?? null, self::DEFAULT_MOBILE),
            self::breakpoint($values['tablet_breakpoint'] ?? null, self::DEFAULT_TABLET),
        );
        $insertions = [];

        foreach (Ads::fromSettings($values, $siteZone) as $ad) {
            if (! Targeting::matches($ad, $article, $now)) {
                continue;
            }

            $insertions[] = new Insertion(
                'adplacr.ad-'.$ad->slot,
                Placement::for($ad),
                $renderer->render($ad, Chooser::choose($ad, $roll)),
            );
        }

        return $insertions;
    }

    private static function breakpoint(mixed $value, int $default): int
    {
        return is_int($value) || (is_string($value) && is_numeric($value)) ? (int) $value : $default;
    }
}
