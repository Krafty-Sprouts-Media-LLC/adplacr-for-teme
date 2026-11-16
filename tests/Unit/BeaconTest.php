<?php

declare(strict_types=1);

use KraftySprouts\AdPlacr\Stats\Beacon;

it('reads an impression or a click for a slot and version', function (): void {
    expect(Beacon::parse('{"event":"impression","ad":1,"version":"1a"}'))->toBe(['event' => 'impression', 'slot' => 1, 'version' => '1a'])
        ->and(Beacon::parse('{"event":"click","ad":5,"version":"5b"}'))->toBe(['event' => 'click', 'slot' => 5, 'version' => '5b']);
});

it('refuses anything that is not exactly that', function (string $body): void {
    expect(Beacon::parse($body))->toBeNull();
})->with([
    'not json' => ['hello'],
    'a list' => ['[1,2]'],
    'unknown event' => ['{"event":"view","ad":1,"version":"1a"}'],
    'slot out of range' => ['{"event":"click","ad":6,"version":"6a"}'],
    'slot zero' => ['{"event":"click","ad":0,"version":"0a"}'],
    'version of another slot' => ['{"event":"click","ad":1,"version":"2a"}'],
    'unknown version letter' => ['{"event":"click","ad":1,"version":"1c"}'],
    'ad as text' => ['{"event":"click","ad":"1","version":"1a"}'],
    'missing version' => ['{"event":"click","ad":1}'],
    'empty' => [''],
]);
