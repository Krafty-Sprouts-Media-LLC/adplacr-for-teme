<?php

declare(strict_types=1);

use KraftySprouts\AdPlacr\Ads\Ad;
use KraftySprouts\AdPlacr\Ads\Ads;
use KraftySprouts\AdPlacr\Ads\Placement;
use Teme\Contracts\Insertions\Position;

/**
 * The position Teme is asked for when slot 1 has these settings.
 *
 * @param  array<string, mixed>  $over
 */
function positionFor(array $over): Position
{
    return Placement::for(adWith($over));
}

function adWith(array $over): Ad
{
    return Ads::fromSettings(slot1($over), new DateTimeZone('UTC'))[0];
}

it('puts an ad after paragraph N', function (): void {
    $p = positionFor(['position' => 'after_paragraph', 'paragraph' => 4]);

    expect($p->kind)->toBe(Position::AFTER_PARAGRAPH)->and($p->args)->toBe(['n' => 4]);
});

it('puts an ad before paragraph N by placing it after paragraph N-1', function (): void {
    $p = positionFor(['position' => 'before_paragraph', 'paragraph' => 4]);

    expect($p->kind)->toBe(Position::AFTER_PARAGRAPH)->and($p->args)->toBe(['n' => 3]);
});

it('puts an ad before paragraph 1 at the start of the article', function (): void {
    expect(positionFor(['position' => 'before_paragraph', 'paragraph' => 1])->kind)->toBe(Position::BEFORE_CONTENT);
});

it('puts an ad before or after the whole article', function (): void {
    expect(positionFor(['position' => 'before_content'])->kind)->toBe(Position::BEFORE_CONTENT)
        ->and(positionFor(['position' => 'after_content'])->kind)->toBe(Position::AFTER_CONTENT);
});

it('repeats an ad every few paragraphs up to the most repeats', function (): void {
    $p = positionFor(['position' => 'every_paragraphs', 'paragraph' => 3, 'repeat_max' => 4]);

    expect($p->kind)->toBe(Position::EVERY_PARAGRAPHS)->and($p->args)->toBe(['n' => 3, 'max' => 4]);
});

it('places an ad before or after the Nth heading of a level', function (): void {
    $before = positionFor(['position' => 'before_heading', 'heading_level' => 3, 'heading_nth' => 2]);
    $after = positionFor(['position' => 'after_heading', 'heading_level' => 2, 'heading_nth' => 1]);

    expect($before->kind)->toBe(Position::BEFORE_HEADING)->and($before->args)->toBe(['level' => 3, 'nth' => 2])
        ->and($after->kind)->toBe(Position::AFTER_HEADING)->and($after->args)->toBe(['level' => 2, 'nth' => 1]);
});

it('places an ad near the end of the article', function (): void {
    expect(positionFor(['position' => 'near_end'])->kind)->toBe(Position::NEAR_END);
});

it('has a Teme position for every location the settings offer', function (): void {
    foreach (array_keys(\KraftySprouts\AdPlacr\Settings\Declaration::POSITIONS) as $key) {
        expect(positionFor(['position' => $key]))->toBeInstanceOf(Position::class);
    }
});
