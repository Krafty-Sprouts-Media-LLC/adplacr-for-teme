<?php

declare(strict_types=1);

use KraftySprouts\AdPlacr\Ads\Targeting;
use Teme\Contracts\Insertions\ArticleFacts;

/**
 * @param  array<string, list<string>>  $terms
 */
function article(string $type = 'post', string $language = 'en', array $terms = [], int $paragraphs = 8): ArticleFacts
{
    return new ArticleFacts('01ARTICLE', $type, $terms, $language, ['u1'], 400, $paragraphs);
}

function shows(array $rules, ?ArticleFacts $article = null, int $now = 1_800_000_000): bool
{
    return Targeting::matches(loadAds(slot1($rules))[0], $article ?? article(), $now);
}

it('shows an ad with no rules everywhere (empty rules fail open)', function (): void {
    expect(shows([]))->toBeTrue()
        ->and(shows([], article('page', 'fr', ['category' => ['x']])))->toBeTrue();
});

it('shows an ad only in the content types listed', function (): void {
    expect(shows(['types' => 'post, page'], article('page')))->toBeTrue()
        ->and(shows(['types' => 'post, page'], article('recipe')))->toBeFalse();
});

it('shows an ad only in the languages listed, and a regional variant counts as its language', function (): void {
    expect(shows(['languages' => 'en, fr'], article(language: 'fr')))->toBeTrue()
        ->and(shows(['languages' => 'en'], article(language: 'en-GB')))->toBeTrue()
        ->and(shows(['languages' => 'en'], article(language: 'de')))->toBeFalse();
});

it('shows an ad when the article has any listed term of a taxonomy', function (): void {
    $rules = ['terms' => 'category:a, category:b'];

    expect(shows($rules, article(terms: ['category' => ['b', 'z']])))->toBeTrue()
        ->and(shows($rules, article(terms: ['category' => ['z']])))->toBeFalse()
        ->and(shows($rules, article(terms: [])))->toBeFalse();
});

it('needs a match in every taxonomy the ad lists (any category AND any tag)', function (): void {
    $rules = ['terms' => 'category:a, tag:t'];

    expect(shows($rules, article(terms: ['category' => ['a'], 'tag' => ['t']])))->toBeTrue()
        ->and(shows($rules, article(terms: ['category' => ['a']])))->toBeFalse();
});

it('keeps an ad before its start and after its end', function (): void {
    $rules = ['start' => '2027-01-15 00:00', 'end' => '2027-01-31 00:00'];
    $at = fn (string $when): int => (new DateTimeImmutable($when, new DateTimeZone('UTC')))->getTimestamp();

    expect(shows($rules, now: $at('2027-01-14 23:59')))->toBeFalse()
        ->and(shows($rules, now: $at('2027-01-20 12:00')))->toBeTrue()
        ->and(shows($rules, now: $at('2027-02-01 00:00')))->toBeFalse();
});

it('applies every rule together', function (): void {
    $rules = ['types' => 'post', 'languages' => 'en'];

    expect(shows($rules, article('post', 'en')))->toBeTrue()
        ->and(shows($rules, article('post', 'fr')))->toBeFalse()
        ->and(shows($rules, article('page', 'en')))->toBeFalse();
});
