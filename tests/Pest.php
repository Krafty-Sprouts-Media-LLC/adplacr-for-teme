<?php

declare(strict_types=1);

use KraftySprouts\AdPlacr\Ads\Ads;
use KraftySprouts\AdPlacr\Settings\Declaration;
use Tests\TestCase;

/*
 * Unit tests are plain PHP and need no Teme. Feature tests boot the teme-dev site through
 * Teme's own TestCase, as the handbook's example test does.
 */
pest()->extend(TestCase::class)->in('Feature');

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
 * @param  array<string, mixed>  $values
 * @return list<\KraftySprouts\AdPlacr\Ads\Ad>
 */
function loadAds(array $values): array
{
    return Ads::fromSettings($values, new DateTimeZone('UTC'));
}
