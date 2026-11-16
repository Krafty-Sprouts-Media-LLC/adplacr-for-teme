<?php

declare(strict_types=1);

use KraftySprouts\AdPlacr\Ads\Ads;
use KraftySprouts\AdPlacr\Settings\Declaration;

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

function loadAds(array $values): array
{
    return Ads::fromSettings($values, new DateTimeZone('UTC'));
}

it('leaves out a Paused slot', function (): void {
    expect(loadAds(slot1(['status' => 'paused'])))->toBe([]);
});

it('leaves out an Active slot that has no ad code in any version (fails closed)', function (): void {
    expect(loadAds(slot1(['code' => '  ', 'code_b' => '', 'mobile_code' => ''])))->toBe([]);
});

it('leaves out a slot whose position is not one the plugin knows (fails closed)', function (): void {
    expect(loadAds(slot1(['position' => 'sticky_footer'])))->toBe([]);
});

it('builds version A from the code, mobile code and weight', function (): void {
    $ad = loadAds(slot1(['code' => 'A', 'mobile_code' => 'AM', 'weight_a' => 3]))[0];

    expect($ad->slot)->toBe(1)
        ->and($ad->versions)->toHaveCount(1)
        ->and($ad->versions[0]->id)->toBe('1a')
        ->and($ad->versions[0]->code)->toBe('A')
        ->and($ad->versions[0]->mobileCode)->toBe('AM')
        ->and($ad->versions[0]->weight)->toBe(3);
});

it('adds version B only when it has code, and keeps the version ids stable', function (): void {
    $ad = loadAds(slot1(['code' => 'A', 'code_b' => 'B', 'weight_b' => 2]))[0];

    expect(array_map(fn ($v) => $v->id, $ad->versions))->toBe(['1a', '1b'])
        ->and($ad->versions[1]->weight)->toBe(2);
});

it('uses version B alone when only B has code, and still calls it 1b', function (): void {
    $ad = loadAds(slot1(['code' => '', 'code_b' => 'B']))[0];

    expect(array_map(fn ($v) => $v->id, $ad->versions))->toBe(['1b']);
});

it('counts a version with only mobile code as a version', function (): void {
    $ad = loadAds(slot1(['code' => '', 'mobile_code' => 'M']))[0];

    expect($ad->versions)->toHaveCount(1)
        ->and($ad->versions[0]->mobileCode)->toBe('M');
});

it('never gives a version a weight under 1', function (): void {
    $ad = loadAds(slot1(['weight_a' => 0, 'code_b' => 'B', 'weight_b' => -4]))[0];

    expect($ad->versions[0]->weight)->toBe(1)
        ->and($ad->versions[1]->weight)->toBe(1);
});

it('shows on every device when none is chosen, and ignores a device it does not know', function (): void {
    expect(loadAds(slot1(['devices' => []]))[0]->devices)->toBe(['desktop', 'tablet', 'mobile'])
        ->and(loadAds(slot1(['devices' => ['mobile', 'tv']]))[0]->devices)->toBe(['mobile'])
        ->and(loadAds(slot1(['devices' => ['tv']]))[0]->devices)->toBe(['desktop', 'tablet', 'mobile']);
});

it('falls back to no alignment for a value it does not know', function (): void {
    expect(loadAds(slot1(['alignment' => 'center']))[0]->alignment)->toBe('center')
        ->and(loadAds(slot1(['alignment' => 'diagonal']))[0]->alignment)->toBe('none');
});

it('keeps the paragraph, repeat and heading numbers inside their limits', function (): void {
    $ad = loadAds(slot1(['paragraph' => 500, 'repeat_max' => 99, 'heading_level' => 9, 'heading_nth' => 0]))[0];

    expect($ad->paragraph)->toBe(100)
        ->and($ad->repeatMax)->toBe(10)
        ->and($ad->headingLevel)->toBe(6)
        ->and($ad->headingNth)->toBe(1);
});

it('reads content types, languages and terms from comma separated text', function (): void {
    $ad = loadAds(slot1([
        'types' => ' Post, page ,,',
        'languages' => 'EN, fr',
        'terms' => 'category:abc, tag:xyz, category:def, nonsense, :x, tag:',
    ]))[0];

    expect($ad->types)->toBe(['post', 'page'])
        ->and($ad->languages)->toBe(['en', 'fr'])
        ->and($ad->terms)->toBe(['category' => ['abc', 'def'], 'tag' => ['xyz']]);
});

it('reads the schedule in the site time zone, and ignores a date it cannot read', function (): void {
    $ads = Ads::fromSettings(slot1(['start' => '2026-12-01 09:00', 'end' => 'soon']), new DateTimeZone('America/New_York'));

    expect($ads[0]->start)->toBe((new DateTimeImmutable('2026-12-01 09:00', new DateTimeZone('America/New_York')))->getTimestamp())
        ->and($ads[0]->end)->toBeNull();
});

it('returns the ads in slot order, skipping the empty ones in between', function (): void {
    $values = [
        'ad3_status' => 'active', 'ad3_code' => 'three',
        'ad2_status' => 'active', 'ad2_code' => '',
        'ad5_status' => 'active', 'ad5_code' => 'five',
    ] + slot1();

    expect(array_map(fn ($ad) => $ad->slot, loadAds($values)))->toBe([1, 3, 5]);
});

it('copes with settings that are missing or the wrong type', function (): void {
    expect(loadAds([]))->toBe([])
        ->and(loadAds(['ad1_status' => 'active', 'ad1_code' => ['x'], 'ad1_paragraph' => 'two']))->toBe([]);
});
