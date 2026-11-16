<?php

declare(strict_types=1);

namespace KraftySprouts\AdPlacr\Ads;

use Teme\Contracts\Insertions\Position;

/**
 * Turns an ad's "where it appears" setting into the Position Teme draws it at.
 */
final class Placement
{
    public static function for(Ad $ad): Position
    {
        return match ($ad->position) {
            'after_paragraph' => Position::afterParagraph($ad->paragraph),
            // Teme has no "before paragraph N"; before paragraph 1 is the start of the article.
            'before_paragraph' => $ad->paragraph === 1 ? Position::beforeContent() : Position::afterParagraph($ad->paragraph - 1),
            'before_content' => Position::beforeContent(),
            'after_content' => Position::afterContent(),
            'every_paragraphs' => Position::everyParagraphs($ad->paragraph, $ad->repeatMax),
            'before_heading' => Position::beforeHeading($ad->headingLevel, $ad->headingNth),
            'after_heading' => Position::afterHeading($ad->headingLevel, $ad->headingNth),
            'near_end' => Position::nearEnd(),
        };
    }
}
