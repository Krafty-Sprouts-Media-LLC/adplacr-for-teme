<?php

declare(strict_types=1);

use KraftySprouts\AdPlacr\Ads\Renderer;

/**
 * The HTML for slot 1 with these fields, drawn with the first version.
 *
 * @param  array<string, mixed>  $over
 */
function drawn(array $over = [], int $mobile = 782, int $tablet = 1024): string
{
    $ad = loadAds(slot1($over))[0];

    return (new Renderer($mobile, $tablet))->render($ad, $ad->versions[0]);
}

it('wraps the ad in a div named for the plugin, carrying the slot and version for statistics', function (): void {
    $html = drawn(['code' => '<ins class="x">ad</ins>']);

    expect($html)->toContain('<div id="adplacr-ad-1" class="adplacr adplacr-pos-after_paragraph"')
        ->and($html)->toContain('data-ad-id="1"')
        ->and($html)->toContain('data-version-id="1a"');
});

it('puts the ad code inside one slot, exactly as written', function (): void {
    $html = drawn(['code' => '<ins class="adsbygoogle" data-x="1"></ins>']);

    expect($html)->toContain('<div class="adplacr__slot adplacr__slot--all"><ins class="adsbygoogle" data-x="1"></ins></div>');
});

it('writes two slots and switches them with CSS when there is mobile code', function (): void {
    $html = drawn(['code' => 'DESK', 'mobile_code' => 'MOB']);

    expect($html)->toContain('<div class="adplacr__slot adplacr__slot--universal">DESK</div>')
        ->and($html)->toContain('<div class="adplacr__slot adplacr__slot--mobile">MOB</div>')
        ->and($html)->toContain('@media (max-width:782px){#adplacr-ad-1 .adplacr__slot--universal{display:none!important}')
        ->and($html)->toContain('@media (min-width:783px){#adplacr-ad-1 .adplacr__slot--mobile{display:none!important}');
});

it('writes no CSS at all for a plain ad shown on every device', function (): void {
    expect(drawn(['code' => 'x']))->not->toContain('<style');
});

it('hides the ad on a device that is not chosen, by media query and not by sniffing', function (): void {
    $noMobile = drawn(['devices' => ['desktop', 'tablet']]);
    $noTablet = drawn(['devices' => ['desktop', 'mobile']]);
    $noDesktop = drawn(['devices' => ['tablet', 'mobile']]);

    expect($noMobile)->toContain('@media (max-width:782px){#adplacr-ad-1{display:none!important}}')
        ->and($noTablet)->toContain('@media (min-width:783px) and (max-width:1024px){#adplacr-ad-1{display:none!important}}')
        ->and($noDesktop)->toContain('@media (min-width:1025px){#adplacr-ad-1{display:none!important}}');
});

it('uses the breakpoints it is given, and keeps tablet above mobile', function (): void {
    $html = drawn(['devices' => ['desktop']], mobile: 600, tablet: 900);
    $inverted = drawn(['devices' => ['desktop']], mobile: 800, tablet: 500);

    expect($html)->toContain('(max-width:600px)')->and($html)->toContain('(min-width:601px) and (max-width:900px)')
        ->and($inverted)->toContain('(min-width:801px) and (max-width:801px)');
});

it('wraps the ad for alignment only when one is chosen', function (): void {
    expect(drawn(['alignment' => 'none']))->not->toContain('adplacr-align')
        ->and(drawn(['alignment' => 'center']))->toContain('<div class="adplacr-align adplacr-align--center">')
        ->and(drawn(['alignment' => 'center']))->toContain('.adplacr-align--center{justify-content:center}');
});

it('names the location in the class so a theme can style it', function (): void {
    expect(drawn(['position' => 'near_end']))->toContain('adplacr-pos-near_end');
});

it('names each slot differently so several ads never share an id or a rule', function (): void {
    $values = ['ad2_status' => 'active', 'ad2_code' => 'two'] + slot1();
    $two = loadAds($values)[1];

    expect((new Renderer(782, 1024))->render($two, $two->versions[0]))->toContain('id="adplacr-ad-2"')->toContain('data-ad-id="2"');
});
