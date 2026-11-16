<?php

declare(strict_types=1);

use KraftySprouts\AdPlacr\Ads\Placer;
use Teme\Contracts\Insertions\Insertion;
use Teme\Contracts\Insertions\Position;

/**
 * @param  array<string, mixed>  $values
 * @return list<Insertion>
 */
function placed(array $values, ?Closure $roll = null, string $type = 'post'): array
{
    return Placer::insertionsFor($values, new DateTimeZone('UTC'), article($type), 1_800_000_000, $roll);
}

it('draws an active ad as one insertion keyed by its slot', function (): void {
    $insertions = placed(slot1(['code' => '<ins>ad</ins>', 'position' => 'after_paragraph', 'paragraph' => 2]));

    expect($insertions)->toHaveCount(1)
        ->and($insertions[0]->key)->toBe('adplacr.ad-1')
        ->and($insertions[0]->at->kind)->toBe(Position::AFTER_PARAGRAPH)
        ->and($insertions[0]->at->args)->toBe(['n' => 2])
        ->and($insertions[0]->html)->toContain('<ins>ad</ins>')->toContain('id="adplacr-ad-1"');
});

it('draws nothing when no ad is set up, or all are Paused', function (): void {
    expect(placed(savedDefaults()))->toBe([])
        ->and(placed(slot1(['status' => 'paused'])))->toBe([]);
});

it('skips an ad whose rules do not fit the article', function (): void {
    expect(placed(slot1(['types' => 'page']), type: 'post'))->toBe([])
        ->and(placed(slot1(['types' => 'page']), type: 'page'))->toHaveCount(1);
});

it('draws several ads in slot order, each under its own key', function (): void {
    $values = ['ad2_status' => 'active', 'ad2_code' => 'two', 'ad2_position' => 'after_content'] + slot1();

    expect(array_map(fn (Insertion $i): string => $i->key, placed($values)))->toBe(['adplacr.ad-1', 'adplacr.ad-2']);
});

it('draws the version the roll picks', function (): void {
    $values = slot1(['code' => 'VERSION-A', 'code_b' => 'VERSION-B', 'weight_a' => 1, 'weight_b' => 1]);

    expect(placed($values, fn (int $max): int => 0)[0]->html)->toContain('VERSION-A')->toContain('data-version-id="1a"')
        ->and(placed($values, fn (int $max): int => 1)[0]->html)->toContain('VERSION-B')->toContain('data-version-id="1b"');
});

it('uses the breakpoints from the settings', function (): void {
    $values = ['mobile_breakpoint' => 640, 'tablet_breakpoint' => 900] + slot1(['mobile_code' => 'M']);

    expect(placed($values)[0]->html)->toContain('(max-width:640px)');
});

it('falls back to the default breakpoints when the settings are missing or not numbers', function (): void {
    $values = slot1(['mobile_code' => 'M']);
    unset($values['mobile_breakpoint']);

    expect(placed($values)[0]->html)->toContain('(max-width:782px)')
        ->and(placed(['mobile_breakpoint' => 'wide'] + $values)[0]->html)->toContain('(max-width:782px)');
});

it('is safe to call with a result Teme can use: every key is one Teme accepts', function (): void {
    $all = [];

    foreach ([1, 2, 3, 4, 5] as $n) {
        $all["ad{$n}_status"] = 'active';
        $all["ad{$n}_code"] = "code{$n}";
    }

    foreach (placed($all + savedDefaults()) as $insertion) {
        expect($insertion->key)->toMatch(Insertion::KEY_PATTERN);
    }
});
