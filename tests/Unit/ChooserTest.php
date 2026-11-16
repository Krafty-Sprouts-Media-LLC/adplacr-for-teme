<?php

declare(strict_types=1);

use KraftySprouts\AdPlacr\Ads\Chooser;

/**
 * Which version is chosen for each possible roll, given the two weights.
 *
 * @return list<string> version ids, one per roll from 0 to total-1
 */
function choicesFor(int $weightA, int $weightB): array
{
    $ad = loadAds(slot1(['code_b' => 'B', 'weight_a' => $weightA, 'weight_b' => $weightB]))[0];
    $total = $weightA + $weightB;

    return array_map(fn (int $roll): string => Chooser::choose($ad, fn (int $max): int => $roll)->id, range(0, $total - 1));
}

it('returns the only version without rolling', function (): void {
    $ad = loadAds(slot1())[0];
    $rolled = false;

    $version = Chooser::choose($ad, function (int $max) use (&$rolled): int {
        $rolled = true;

        return 0;
    });

    expect($version->id)->toBe('1a')->and($rolled)->toBeFalse();
});

it('chooses by weight: 3 to 1 gives A three rolls in four', function (): void {
    expect(choicesFor(3, 1))->toBe(['1a', '1a', '1a', '1b']);
});

it('chooses evenly when the weights are equal', function (): void {
    expect(choicesFor(1, 1))->toBe(['1a', '1b']);
});

it('asks the roll for a number below the total weight', function (): void {
    $ad = loadAds(slot1(['code_b' => 'B', 'weight_a' => 2, 'weight_b' => 5]))[0];
    $asked = null;

    Chooser::choose($ad, function (int $max) use (&$asked): int {
        $asked = $max;

        return 0;
    });

    expect($asked)->toBe(7);
});

it('stays inside the versions however wild the roll is', function (): void {
    $ad = loadAds(slot1(['code_b' => 'B']))[0];

    expect(Chooser::choose($ad, fn (int $max): int => 99)->id)->toBeIn(['1a', '1b'])
        ->and(Chooser::choose($ad, fn (int $max): int => -5)->id)->toBeIn(['1a', '1b']);
});

it('uses real randomness when no roll is given, and reaches both versions', function (): void {
    $ad = loadAds(slot1(['code_b' => 'B']))[0];
    $seen = [];

    for ($i = 0; $i < 200; $i++) {
        $seen[Chooser::choose($ad)->id] = true;
    }

    expect(array_keys($seen))->toContain('1a', '1b');
});
