<?php

declare(strict_types=1);

use KraftySprouts\AdPlacr\Ads\Ad;
use KraftySprouts\AdPlacr\Ads\Ads;
use KraftySprouts\AdPlacr\Settings\Declaration;
use Teme\Contracts\Insertions\ArticleFacts;

/**
 * Test helpers, loaded by tests/bootstrap.php. They live here, not in a Pest.php, because Pest reads
 * the Pest.php of the site it runs from (teme-dev), never one in a plugin (FINDINGS F-12).
 */

/**
 * Every declared setting at its default: what Teme's settings reader answers before anyone saves.
 *
 * @return array<string, mixed>
 */
function savedDefaults(): array
{
    return array_column(Declaration::settings(), 'default', 'key');
}

/**
 * The settings with slot 1 Active, carrying some code, and these fields of it replaced.
 *
 * @param  array<string, mixed>  $over  field name without the `ad1_` prefix => value
 *
 * @return array<string, mixed>
 */
function slot1(array $over = []): array
{
    $values = savedDefaults();

    foreach ($over + ['status' => 'active', 'code' => '<ins>ad</ins>'] as $field => $value) {
        $values["ad1_{$field}"] = $value;
    }

    return $values;
}

/**
 * What Teme tells a contributor about an article.
 *
 * @param  array<string, list<string>>  $terms  term ids by taxonomy
 */
function article(string $type = 'post', string $language = 'en', array $terms = [], int $paragraphs = 8): ArticleFacts
{
    return new ArticleFacts('01ARTICLE', $type, $terms, $language, ['u1'], 400, $paragraphs);
}

/**
 * @param  array<string, mixed>  $values
 *
 * @return list<Ad>
 */
function loadAds(array $values): array
{
    return Ads::fromSettings($values, new DateTimeZone('UTC'));
}
