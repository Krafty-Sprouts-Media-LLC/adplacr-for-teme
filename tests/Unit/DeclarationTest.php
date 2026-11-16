<?php

declare(strict_types=1);

use KraftySprouts\AdPlacr\Settings\Declaration;

const MANIFEST_TYPES = ['text', 'textarea', 'number', 'boolean', 'select', 'multiselect', 'url', 'urls', 'email', 'secret', 'color'];

/**
 * @return array<string, array<string, mixed>> the declared settings, by key
 */
function declared(): array
{
    $byKey = [];

    foreach (Declaration::settings() as $setting) {
        $byKey[$setting['key']] = $setting;
    }

    return $byKey;
}

it('declares the four global settings of the WordPress plugin with its bounds', function (): void {
    $s = declared();

    expect($s['mobile_breakpoint'])->toMatchArray(['type' => 'number', 'default' => 782, 'min' => 200, 'max' => 1024])
        ->and($s['tablet_breakpoint'])->toMatchArray(['type' => 'number', 'default' => 1024, 'min' => 201, 'max' => 1600])
        ->and($s['analytics_enabled'])->toMatchArray(['type' => 'boolean', 'default' => false])
        ->and($s['retention_days'])->toMatchArray(['type' => 'select', 'default' => '90'])
        ->and(array_map('strval', array_keys($s['retention_days']['options'])))->toBe(['30', '60', '90', '180', '365']);
});

it('declares five ad slots, each Paused until someone turns it on', function (): void {
    $s = declared();

    foreach ([1, 2, 3, 4, 5] as $n) {
        expect($s["ad{$n}_status"])->toMatchArray(['type' => 'select', 'default' => 'paused'])
            ->and($s["ad{$n}_status"]['options'])->toBe(['paused' => 'Paused', 'active' => 'Active']);
    }

    expect($s)->not->toHaveKey('ad6_status');
});

it('gives every slot the same fields, so no slot can be set up with less', function (): void {
    $s = declared();
    $fields = fn (int $n): array => array_map(
        fn (string $key): string => substr($key, strlen("ad{$n}_")),
        array_values(array_filter(array_keys($s), fn (string $key): bool => str_starts_with($key, "ad{$n}_"))),
    );

    expect($fields(1))->toContain('name', 'status', 'position', 'paragraph', 'code', 'mobile_code', 'alignment', 'devices', 'types', 'terms', 'languages', 'start', 'end', 'code_b', 'mobile_code_b', 'weight_a', 'weight_b');

    foreach ([2, 3, 4, 5] as $n) {
        expect($fields($n))->toBe($fields(1));
    }
});

it('only uses what the manifest allows: key pattern, type list and a label and description', function (): void {
    foreach (Declaration::settings() as $setting) {
        expect($setting['key'])->toMatch('/^[a-z][a-z0-9_]{1,40}$/')
            ->and($setting['type'])->toBeIn(MANIFEST_TYPES)
            ->and($setting['label'])->not->toBe('')
            ->and($setting['description'])->not->toBe('');
    }
});

it('keeps teme-plugin.json in step with the declaration', function (): void {
    $manifest = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/teme-plugin.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest['contributes']['settings'])->toBe(Declaration::settings());
});
