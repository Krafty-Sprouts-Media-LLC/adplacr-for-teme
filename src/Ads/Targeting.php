<?php

declare(strict_types=1);

namespace KraftySprouts\AdPlacr\Ads;

use Teme\Contracts\Insertions\ArticleFacts;

/**
 * The one gate every ad passes before it is drawn: does this article fit the ad's rules?
 * A rule left empty never excludes anything, so a new ad shows everywhere until narrowed.
 */
final class Targeting
{
    public static function matches(Ad $ad, ArticleFacts $article, int $now): bool
    {
        return self::inSchedule($ad, $now)
            && ($ad->types === [] || in_array(strtolower($article->type), $ad->types, true))
            && self::inLanguage($ad, $article)
            && self::hasTerms($ad, $article);
    }

    private static function inSchedule(Ad $ad, int $now): bool
    {
        return ($ad->start === null || $now >= $ad->start) && ($ad->end === null || $now <= $ad->end);
    }

    private static function inLanguage(Ad $ad, ArticleFacts $article): bool
    {
        if ($ad->languages === []) {
            return true;
        }

        $language = strtolower($article->language);

        // "en-GB" counts as "en": an ad written for a language suits its regional variants.
        return in_array($language, $ad->languages, true) || in_array(explode('-', $language)[0], $ad->languages, true);
    }

    private static function hasTerms(Ad $ad, ArticleFacts $article): bool
    {
        // Any listed term of a taxonomy is enough, but every taxonomy the ad lists must be met.
        foreach ($ad->terms as $taxonomy => $wanted) {
            if (array_intersect($wanted, $article->termIdsByTaxonomy[$taxonomy] ?? []) === []) {
                return false;
            }
        }

        return true;
    }
}
